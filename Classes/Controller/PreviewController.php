<?php

declare(strict_types=1);

namespace Zeroseven\Semantilizer\Controller;

use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriInterface;
use RuntimeException;
use Throwable;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\CookieScope;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Loads the frontend page server-side for the Semantilizer backend UI.
 *
 * The browser only calls this authenticated same-origin route. The controller resolves the actual frontend URL,
 * transfers the current backend session to its target domain and returns the resulting HTML. This avoids CORS while
 * preserving TYPO3 preview behavior for hidden pages, workspaces and languages.
 */
#[AsController]
final class PreviewController
{
    private const MAX_RESPONSE_SIZE = 5_000_000;

    public function __construct(
        private readonly RequestFactory $requestFactory,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly SiteFinder $siteFinder,
    ) {}

    /**
     * Proxies one frontend preview after repeating page and language access checks at the endpoint boundary.
     */
    public function fetch(ServerRequestInterface $request): ResponseInterface
    {
        $pageUid = (int)($request->getQueryParams()['pageUid'] ?? 0);
        $languageUid = max(0, (int)($request->getQueryParams()['languageUid'] ?? 0));
        $backendUser = $GLOBALS['BE_USER'] ?? null;

        if ($pageUid <= 0) {
            return $this->createErrorResponse(400, 'Invalid page identifier');
        }
        if (!$backendUser instanceof BackendUserAuthentication) {
            return $this->createErrorResponse(401, 'Backend login required');
        }

        $permissionClause = $backendUser->getPagePermsClause(Permission::PAGE_SHOW);
        if (!BackendUtility::readPageAccess($pageUid, $permissionClause)) {
            return $this->createErrorResponse(403, 'Page access denied');
        }
        if (!$backendUser->checkLanguageAccess($languageUid)) {
            return $this->createErrorResponse(403, 'Language access denied');
        }

        // Keep TYPO3's native preview semantics instead of rebuilding frontend URLs in the extension.
        $previewUri = PreviewUriBuilder::create($pageUid)->withLanguage($languageUid)->buildUri();
        if (!$previewUri instanceof UriInterface) {
            return $this->createErrorResponse(404, 'Preview URL could not be generated');
        }

        $allowedHosts = $this->getAllowedHosts($request);
        try {
            $this->assertAllowedUri($previewUri, $allowedHosts);

            // Backend session JWTs are bound to a cookie domain and path. Reissue the current session for the
            // frontend target so its TYPO3 request still recognizes the editor, workspace and preview permissions.
            $cookieScope = $this->createCookieScope($previewUri->getHost(), $request);
            $sessionCookie = new SetCookie([
                'Name' => BackendUserAuthentication::getCookieName(),
                'Value' => $backendUser->getSession()->getJwt($cookieScope),
                'Domain' => $cookieScope->domain,
                'Path' => $cookieScope->path,
                'Secure' => $previewUri->getScheme() === 'https',
                'HttpOnly' => true,
                'Discard' => true,
            ]);
            $sessionCookie->setHostOnly($cookieScope->hostOnly);
            $cookieJar = new CookieJar(false, [$sessionCookie]);
            $response = $this->requestFactory->request((string)$previewUri, 'GET', [
                'allow_redirects' => [
                    'max' => 5,
                    'strict' => true,
                    'referer' => false,
                    'on_redirect' => function (RequestInterface $redirectRequest, ResponseInterface $redirectResponse, UriInterface $redirectUri) use ($allowedHosts): void {
                        // Redirects must not turn this endpoint into an unrestricted server-side HTTP proxy.
                        $this->assertAllowedUri($redirectUri, $allowedHosts);
                    },
                ],
                'connect_timeout' => 5,
                'cookies' => $cookieJar,
                'headers' => [
                    'X-Semantilizer' => 'true',
                ],
                'http_errors' => false,
                'timeout' => 15,
            ]);
        } catch (Throwable $exception) {
            $this->logError('Frontend preview request failed', [
                'exceptionClass' => $exception::class,
                'exceptionCode' => $exception->getCode(),
                'exceptionMessage' => $exception->getMessage(),
            ]);
            return $this->createErrorResponse(502, 'Frontend preview could not be loaded');
        }

        if ($response->getStatusCode() !== 200) {
            $this->logError('Frontend preview returned an unexpected status code', ['statusCode' => $response->getStatusCode()]);
            return $this->createErrorResponse(502, 'Frontend preview returned an error');
        }
        if (!str_starts_with(strtolower($response->getHeaderLine('Content-Type')), 'text/html')) {
            $this->logError('Frontend preview returned an unexpected content type', ['contentType' => $response->getHeaderLine('Content-Type')]);
            return $this->createErrorResponse(502, 'Frontend preview did not return HTML');
        }

        $html = (string)$response->getBody();
        if (strlen($html) > self::MAX_RESPONSE_SIZE) {
            $this->logError('Frontend preview exceeded the maximum response size', ['size' => strlen($html)]);
            return $this->createErrorResponse(502, 'Frontend preview is too large');
        }

        // Intentionally return only the HTML body; frontend headers and cookies must not leak into the backend.
        return $this->responseFactory->createResponse()
            ->withHeader('Cache-Control', 'no-store')
            ->withHeader('Content-Type', 'text/html; charset=utf-8')
            ->withBody($this->streamFactory->createStream($html));
    }

    /**
     * Builds the SSRF allow-list from configured site hosts and the current host used by relative site bases.
     *
     * @return list<string>
     */
    private function getAllowedHosts(ServerRequestInterface $request): array
    {
        $hosts = array_filter(array_map(
            static fn (Site $site): string => strtolower($site->getBase()->getHost()),
            $this->siteFinder->getAllSites(),
        ));
        $normalizedParams = $request->getAttribute('normalizedParams');
        if ($normalizedParams instanceof NormalizedParams) {
            $hosts[] = strtolower($normalizedParams->getRequestHostOnly());
        }

        return array_values(array_unique($hosts));
    }

    /** @param list<string> $allowedHosts */
    private function assertAllowedUri(UriInterface $uri, array $allowedHosts): void
    {
        if (!in_array(strtolower($uri->getScheme()), ['http', 'https'], true)
            || !in_array(strtolower($uri->getHost()), $allowedHosts, true)
        ) {
            throw new RuntimeException('Preview URL is not part of a configured TYPO3 site');
        }
    }

    /**
     * Reproduces TYPO3's backend cookie scope for the frontend target instead of the current backend host.
     */
    private function createCookieScope(string $targetHost, ServerRequestInterface $request): CookieScope
    {
        $normalizedParams = $request->getAttribute('normalizedParams');
        $sitePath = $normalizedParams instanceof NormalizedParams ? $normalizedParams->getSitePath() : '/';
        $cookieDomain = ($GLOBALS['TYPO3_CONF_VARS']['BE']['cookieDomain'] ?? '')
            ?: ($GLOBALS['TYPO3_CONF_VARS']['SYS']['cookieDomain'] ?? '');

        if ($cookieDomain === '') {
            return new CookieScope($targetHost, true, $sitePath);
        }
        if ($cookieDomain[0] === '/') {
            $matches = [];
            if (@preg_match($cookieDomain, $targetHost, $matches) !== 1) {
                return new CookieScope($targetHost, true, $sitePath);
            }
            $cookieDomain = $matches[0];
        }

        return new CookieScope(trim($cookieDomain, '.'), false, '/');
    }

    private function createErrorResponse(int $statusCode, string $message): ResponseInterface
    {
        return $this->responseFactory->createResponse($statusCode)
            ->withHeader('Cache-Control', 'no-store')
            ->withHeader('Content-Type', 'text/plain; charset=utf-8')
            ->withBody($this->streamFactory->createStream($message));
    }

    private function logError(string $message, array $context = []): void
    {
        GeneralUtility::makeInstance(LogManager::class)->getLogger(self::class)->error($message, $context);
    }
}
