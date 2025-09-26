<?php

declare(strict_types=1);

namespace Zeroseven\Semantilizer\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

abstract class AbstractMiddleware implements MiddlewareInterface
{
    protected function isSemantilizerRequest(ServerRequestInterface $request, bool $requireLogin = null): bool
    {
        $headerExists = !empty($request->getHeader('X-Semantilizer'));

        try {
            return $headerExists && (!$requireLogin || GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('backend.user', 'isLoggedIn', false));
        } catch (AspectNotFoundException) {
            return false;
        }
    }

    abstract public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface;
}
