<?php

declare(strict_types=1);

namespace Zeroseven\Semantilizer\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

abstract class AbstractMiddleware implements MiddlewareInterface
{
    protected function isSemantilizerRequest(ServerRequestInterface $request): bool
    {
        return !empty($request->getHeader('X-Semantilizer'));
    }

    abstract public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface;
}
