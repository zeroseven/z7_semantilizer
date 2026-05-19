<?php

declare(strict_types=1);

namespace Zeroseven\Semantilizer\Tests\Unit\ViewHelper;

use PHPUnit\Framework\TestCase;
use Zeroseven\Semantilizer\Tests\Unit\Support\AbstractHeadlineViewHelperTestDouble;

final class AbstractHeadlineViewHelperTest extends TestCase
{
    private AbstractHeadlineViewHelperTestDouble $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = new AbstractHeadlineViewHelperTestDouble();
        $this->viewHelper->initializeArguments();
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['USER']['z7_semantilizer']);
        parent::tearDown();
    }

    /**
     * @param array<string, mixed>|string|null $edit
     * @param array{table: string, uid: int, field?: string|null}|null $expected
     *
     * @dataProvider editSetupProvider
     */
    public function testParseEditSetup(array|string|null $edit, ?array $expected): void
    {
        $this->viewHelper->withArguments(['edit' => $edit]);

        self::assertSame($expected, $this->viewHelper->exposeParseEditSetup());
    }

    /**
     * @return iterable<string, array{0: array<string, mixed>|string|null, 1: array<string, mixed>|null}>
     */
    public static function editSetupProvider(): iterable
    {
        yield 'array notation' => [
            ['table' => 'tt_content', 'uid' => 42, 'field' => 'header_type'],
            ['table' => 'tt_content', 'uid' => 42, 'field' => 'header_type'],
        ];

        yield 'string notation with field' => [
            'tt_content:99:header_type',
            ['table' => 'tt_content', 'uid' => 99, 'field' => 'header_type'],
        ];

        yield 'string notation without field' => [
            'tt_content:12',
            ['table' => 'tt_content', 'uid' => 12, 'field' => null],
        ];

        yield 'invalid string' => [
            'not-a-valid-setup',
            null,
        ];

        yield 'null' => [
            null,
            null,
        ];
    }

    /**
     * @dataProvider headlineMarkupProvider
     */
    public function testRenderHeadlineProducesSemanticMarkup(int $type, string $expectedTag, ?string $expectedAttribute): void
    {
        $html = $this->viewHelper
            ->withArguments(['content' => 'Demo title'])
            ->exposeRenderHeadline($type);

        self::assertStringContainsString('<' . $expectedTag, $html);
        self::assertStringContainsString('Demo title', $html);

        if ($expectedAttribute !== null) {
            self::assertStringContainsString($expectedAttribute, $html);
        }
    }

    /**
     * @return iterable<string, array{0: int, 1: string, 2: string|null}>
     */
    public static function headlineMarkupProvider(): iterable
    {
        yield 'h1' => [1, 'h1', null];
        yield 'h2' => [2, 'h2', null];
        yield 'h6' => [6, 'h6', null];
        yield 'non-semantic' => [0, 'div', 'data-heading="true"'];
    }

    public function testRenderHeadlineReturnsEmptyStringWithoutContent(): void
    {
        $html = $this->viewHelper
            ->withArguments(['content' => '   '])
            ->exposeRenderHeadline(2);

        self::assertSame('', $html);
    }

    public function testStoreRelationAllowsChildViewHelperToResolveType(): void
    {
        $this->viewHelper
            ->withArguments(['content' => 'Parent', 'relationId' => 'group-a'])
            ->exposeRenderHeadline(2);

        self::assertSame(2, $GLOBALS['USER']['z7_semantilizer']['temp']['relations']['group-a'] ?? null);
    }
}
