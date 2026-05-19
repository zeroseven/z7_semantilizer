<?php

declare(strict_types=1);

namespace Zeroseven\Semantilizer\Tests\Unit\ViewHelper;

use PHPUnit\Framework\TestCase;
use Zeroseven\Semantilizer\Tests\Unit\Support\HeadlineViewHelperTestDouble;

final class HeadlineViewHelperTest extends TestCase
{
    private HeadlineViewHelperTestDouble $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = new HeadlineViewHelperTestDouble();
        $this->viewHelper->initializeArguments();
    }

    public function testRenderUsesHeaderTypeAsTagName(): void
    {
        $html = $this->viewHelper
            ->withArguments([
                'content' => 'Product line',
                'type' => 3,
            ])
            ->render();

        self::assertStringContainsString('<h3', $html);
        self::assertStringContainsString('Product line', $html);
    }

    public function testRenderDerivesRelationIdFromEditSetupWhenNotGiven(): void
    {
        $html = $this->viewHelper
            ->withArguments([
                'content' => 'Linked headline',
                'type' => 2,
                'edit' => 'tt_content:77:header_type',
            ])
            ->render();

        self::assertStringContainsString('<h2', $html);
        self::assertSame(2, $GLOBALS['USER']['z7_semantilizer']['temp']['relations']['tt_content:77'] ?? null);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['USER']['z7_semantilizer']);
        parent::tearDown();
    }
}
