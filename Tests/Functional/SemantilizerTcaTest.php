<?php

declare(strict_types=1);

namespace Zeroseven\Semantilizer\Tests\Functional;

use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SemantilizerTcaTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'zeroseven/z7-semantilizer',
    ];

    public function testHeaderTypeColumnIsRegisteredInTca(): void
    {
        self::assertArrayHasKey('header_type', $GLOBALS['TCA']['tt_content']['columns']);
        self::assertSame('select', $GLOBALS['TCA']['tt_content']['columns']['header_type']['config']['type']);
    }

    public function testHeaderTypeIsAddedToHeaderPalette(): void
    {
        $palette = $GLOBALS['TCA']['tt_content']['palettes']['header']['showitem'] ?? '';

        self::assertStringContainsString('header_type', $palette);
    }
}
