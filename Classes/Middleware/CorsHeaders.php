<?php

declare(strict_types=1);

namespace Zeroseven\Semantilizer\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Middleware\VerifyHostHeader;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CorsHeaders extends AbstractMiddleware
{
    protected function urlToDomain(string $url): ?string
    {
        $parts = parse_url($url);
        if (!$parts || !isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        $domain = $parts['scheme'] . '://' . $parts['host'];

        // Include port if present and not default
        if (isset($parts['port'])) {
            $isDefaultPort = ($parts['scheme'] === 'https' && $parts['port'] === 443)
                          || ($parts['scheme'] === 'http' && $parts['port'] === 80);
            if (!$isDefaultPort) {
                $domain .= ':' . $parts['port'];
            }
        }

        return $domain;
    }

    private function isOriginAllowed(string $origin, array $serverParams): bool
    {
        // 1. Check trustedHostsPattern first (works without SiteFinder)
        $trustedHostsPattern = $GLOBALS['TYPO3_CONF_VARS']['SYS']['trustedHostsPattern'] ?? 'SERVER_NAME';
        $verifier = new VerifyHostHeader($trustedHostsPattern);

        if ($verifier->isAllowedHostHeaderValue(parse_url($origin, PHP_URL_HOST), $serverParams)) {
            return true;
        }

        // 2. Check urls of site config if SiteFinder is available
        try {
            $siteUrls = array_filter(array_map(
                fn (Site $site) => $this->urlToDomain((string)$site->getBase()),
                GeneralUtility::makeInstance(SiteFinder::class)?->getAllSites() ?? []
            ));

            if (in_array($origin, $siteUrls)) {
                return true;
            }
        } catch (\Exception $e) {
            // SiteFinder not ready yet (e.g., middleware runs very early)
            // Already checked trustedHostsPattern above
        }

        return false;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $origin = $this->urlToDomain($request->getHeaderLine('Origin'));

        // Only add CORS headers for actual semantilizer requests (not OPTIONS preflight)
        // Note: OPTIONS preflight requests need to be handled by webserver configuration
        // (nginx/Apache) as they never reach PHP middleware in most setups
        if ($origin && $this->isSemantilizerRequest($request)) {
            if ($this->isOriginAllowed($origin, $request->getServerParams())) {
                return $handler->handle($request)
                    ->withHeader('Access-Control-Allow-Origin', $origin)
                    ->withHeader('Access-Control-Allow-Credentials', 'true');
            }
        }

        return $handler->handle($request);
    }
}
