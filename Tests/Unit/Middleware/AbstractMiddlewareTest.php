<?php

declare(strict_types=1);

namespace Zeroseven\Semantilizer\Tests\Unit\Middleware;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zeroseven\Semantilizer\Middleware\AbstractMiddleware;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;

final class AbstractMiddlewareTest extends TestCase
{
    public function testIsSemantilizerRequestDetectsHeader(): void
    {
        $middleware = new class extends AbstractMiddleware {
            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                return new Response();
            }

            public function check(ServerRequestInterface $request): bool
            {
                return $this->isSemantilizerRequest($request);
            }
        };

        $withHeader = (new ServerRequest('GET', '/'))->withHeader('X-Semantilizer', '1');
        $withoutHeader = new ServerRequest('GET', '/');

        self::assertTrue($middleware->check($withHeader));
        self::assertFalse($middleware->check($withoutHeader));
    }
}
