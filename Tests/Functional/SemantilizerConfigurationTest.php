<?php

declare(strict_types=1);

namespace Zeroseven\Semantilizer\Tests\Functional;

use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SemantilizerConfigurationTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'zeroseven/z7-semantilizer',
    ];

    public function testRequestMiddlewaresAreRegistered(): void
    {
        $middlewares = require dirname(__DIR__, 2) . '/Configuration/RequestMiddlewares.php';

        self::assertArrayHasKey('zeroseven/z7_semantilizer/request', $middlewares['frontend']);
        self::assertArrayHasKey('zeroseven/z7_semantilizer/user-ts-config', $middlewares['frontend']);
        self::assertContains('typo3/cms-frontend/tsfe', $middlewares['frontend']['zeroseven/z7_semantilizer/request']['after']);
    }

    public function testJavaScriptModuleImportIsConfigured(): void
    {
        $modules = require dirname(__DIR__, 2) . '/Configuration/JavaScriptModules.php';

        self::assertContains('backend', $modules['dependencies']);
        self::assertArrayHasKey('@zeroseven/semantilizer/', $modules['imports']);
    }
}
