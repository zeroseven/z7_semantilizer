<?php

declare(strict_types=1);

namespace Zeroseven\Semantilizer\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class CacheControl extends AbstractMiddleware
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Disable cache on some conditions
        try {
            $this->isSemantilizerRequest($request)
            && GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('backend.user', 'isLoggedIn', false)
            && $GLOBALS['TSFE'] instanceof TypoScriptFrontendController
            && $GLOBALS['TSFE']->set_no_cache(sprintf('Semantilizer frontend request (%s, line %d)', self::class, __LINE__));
        } catch (AspectNotFoundException) {
        }

        // Go your way …
        return $handler->handle($request);
    }
}
