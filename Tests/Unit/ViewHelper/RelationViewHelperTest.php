<?php

declare(strict_types=1);

namespace Zeroseven\Semantilizer\Tests\Unit\ViewHelper;

use PHPUnit\Framework\TestCase;
use Zeroseven\Semantilizer\Tests\Unit\Support\ChildViewHelperTestDouble;
use Zeroseven\Semantilizer\Tests\Unit\Support\HeadlineViewHelperTestDouble;
use Zeroseven\Semantilizer\Tests\Unit\Support\SiblingViewHelperTestDouble;

final class RelationViewHelperTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['USER']['z7_semantilizer']);
        parent::tearDown();
    }

    public function testChildViewHelperIncrementsParentSemanticLevel(): void
    {
        $parent = new HeadlineViewHelperTestDouble();
        $parent->initializeArguments();
        $parent->withArguments([
            'content' => 'Parent',
            'type' => 2,
            'relationId' => 'product-1',
        ])->render();

        $child = new ChildViewHelperTestDouble();
        $child->initializeArguments();
        $html = $child->withArguments([
            'content' => 'Child item',
            'of' => 'product-1',
        ])->render();

        self::assertStringContainsString('<h3', $html);
        self::assertStringContainsString('Child item', $html);
    }

    public function testSiblingViewHelperMirrorsParentSemanticLevel(): void
    {
        $parent = new HeadlineViewHelperTestDouble();
        $parent->initializeArguments();
        $parent->withArguments([
            'content' => 'Parent',
            'type' => 4,
            'relationId' => 'mirror-me',
        ])->render();

        $sibling = new SiblingViewHelperTestDouble();
        $sibling->initializeArguments();
        $html = $sibling->withArguments([
            'content' => 'Mirrored',
            'of' => 'mirror-me',
        ])->render();

        self::assertStringContainsString('<h4', $html);
        self::assertStringContainsString('Mirrored', $html);
    }

    public function testChildViewHelperFallsBackToNonSemanticDivWithoutRelation(): void
    {
        $child = new ChildViewHelperTestDouble();
        $child->initializeArguments();
        $html = $child->withArguments([
            'content' => 'Orphan',
            'of' => 'missing-relation',
        ])->render();

        self::assertStringContainsString('data-heading="true"', $html);
        self::assertStringNotContainsString('<h1', $html);
    }
}
