<?php

declare(strict_types=1);

namespace Zeroseven\Semantilizer\Tests\Unit\Middleware;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Cache\CacheInstruction;
use Zeroseven\Semantilizer\Middleware\Request;

final class RequestMiddlewareTest extends TestCase
{
    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

    public function testDisablesCacheForSemantilizerRequestWhenBackendUserIsLoggedIn(): void
    {
        if (!class_exists(CacheInstruction::class)) {
            self::markTestSkipped('CacheInstruction is not available in this TYPO3 version.');
        }

        $context = $this->createMock(Context::class);
        $context->method('getPropertyFromAspect')
            ->with('backend.user', 'isLoggedIn', false)
            ->willReturn(true);
        GeneralUtility::setSingletonInstance(Context::class, $context);

        $request = (new ServerRequest('GET', '/'))
            ->withHeader('X-Semantilizer', '1');

        $handler = new RecordingRequestHandler();

        $middleware = new Request();
        $middleware->process($request, $handler);

        $cacheInstruction = $handler->request->getAttribute('frontend.cache.instruction');
        self::assertInstanceOf(CacheInstruction::class, $cacheInstruction);
        self::assertFalse($cacheInstruction->isCachingAllowed());
    }

    public function testDoesNotDisableCacheWithoutSemantilizerHeader(): void
    {
        if (!class_exists(CacheInstruction::class)) {
            self::markTestSkipped('CacheInstruction is not available in this TYPO3 version.');
        }

        $context = $this->createMock(Context::class);
        $context->expects(self::never())->method('getPropertyFromAspect');
        GeneralUtility::setSingletonInstance(Context::class, $context);

        $request = new ServerRequest('GET', '/');
        $handler = new RecordingRequestHandler();

        $middleware = new Request();
        $middleware->process($request, $handler);

        $cacheInstruction = $handler->request->getAttribute('frontend.cache.instruction');
        self::assertNull($cacheInstruction);
    }
}

final class RecordingRequestHandler implements RequestHandlerInterface
{
    public ServerRequestInterface $request;

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->request = $request;

        return new Response(200);
    }
}
