<?php

declare(strict_types=1);

namespace Zeroseven\Semantilizer\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class Request extends AbstractMiddleware
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->isSemantilizerRequest($request)
        && $GLOBALS['TSFE'] instanceof TypoScriptFrontendController
        && $GLOBALS['TSFE']->set_no_cache(sprintf('Semantilizer frontend request (%s, line %d)', self::class, __LINE__));

        return $handler->handle($request);
    }
}
