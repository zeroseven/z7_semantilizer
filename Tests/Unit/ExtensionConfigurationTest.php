<?php

declare(strict_types=1);

namespace Zeroseven\Semantilizer\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Zeroseven\Semantilizer\Middleware\Request;
use Zeroseven\Semantilizer\ViewHelpers\HeadlineViewHelper;

final class ExtensionConfigurationTest extends TestCase
{
    public function testComposerRequiresTypo3ThirteenOrFourteenAndPhp83(): void
    {
        $composer = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/composer.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        self::assertStringContainsString('13.1', $composer['require']['typo3/cms-core']);
        self::assertStringContainsString('14.0', $composer['require']['typo3/cms-core']);
        self::assertSame('^8.3', $composer['require']['php']);
    }

    public function testExtEmconfMatchesComposerConstraint(): void
    {
        $emConfFile = dirname(__DIR__, 2) . '/ext_emconf.php';
        /** @var array<string, array<string, mixed>> $EM_CONF */
        $EM_CONF = [];
        $_EXTKEY = 'z7_semantilizer';
        require $emConfFile;

        self::assertArrayHasKey('z7_semantilizer', $EM_CONF);
        /** @var array<string, mixed> $extensionConf */
        $extensionConf = $EM_CONF['z7_semantilizer'];
        self::assertSame('8.3.0-8.99.99', $extensionConf['constraints']['depends']['php']);
        self::assertSame('13.1.0-14.4.99', $extensionConf['constraints']['depends']['typo3']);
    }

    public function testMiddlewareAndViewHelperAreLoadable(): void
    {
        self::assertTrue(class_exists(Request::class));
        self::assertTrue(class_exists(HeadlineViewHelper::class));
    }

    public function testServicesYamlRegistersValidationEvent(): void
    {
        $servicesYaml = file_get_contents(dirname(__DIR__, 2) . '/Configuration/Services.yaml');
        self::assertIsString($servicesYaml);
        self::assertStringContainsString('ValidationEvent', $servicesYaml);
        self::assertStringContainsString('event.listener', $servicesYaml);
    }
}
