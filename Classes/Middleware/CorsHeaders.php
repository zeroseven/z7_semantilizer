<?php

declare(strict_types=1);

namespace Zeroseven\Semantilizer\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CorsHeaders extends AbstractMiddleware
{
    protected function urlToDomain(string $url): ?string
    {
        if (empty($url)) {
            return null;
        }

        return parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $isPreflight = $request->getMethod() === 'OPTIONS';

        if ($isPreflight || $this->isSemantilizerRequest($request)) {
            $origin = $this->urlToDomain($request->getHeaderLine('Origin'));
            $allowedUrls = array_filter(array_map(fn(Site $site) => $this->urlToDomain((string)$site->getBase()),
                GeneralUtility::makeInstance(SiteFinder::class)?->getAllSites() ?? []));

            if ($origin && in_array($origin, $allowedUrls)) {
                return ($isPreflight ? new Response(null, 204) : $handler->handle($request))
                    ->withHeader('Access-Control-Allow-Origin', $origin)
                    ->withHeader('Access-Control-Allow-Methods', 'GET, OPTIONS')
                    ->withHeader('Access-Control-Allow-Headers', 'X-Semantilizer')
                    ->withHeader('Access-Control-Allow-Credentials', 'true');
            }
        }

        // Go your way …
        return $handler->handle($request);
    }
}
