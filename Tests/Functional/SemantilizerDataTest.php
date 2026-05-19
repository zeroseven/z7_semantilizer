<?php

declare(strict_types=1);

namespace Zeroseven\Semantilizer\Tests\Functional;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SemantilizerDataTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'zeroseven/z7-semantilizer',
    ];

    protected array $coreExtensionsToLoad = [
        'core',
        'frontend',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Database/semantilizer.csv');
    }

    public function testHeaderTypeFieldExistsOnContentElement(): void
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tt_content');
        $row = $connection->select(['header_type', 'header'], 'tt_content', ['uid' => 100])->fetchAssociative();

        self::assertIsArray($row);
        self::assertSame(1, (int) $row['header_type']);
        self::assertSame('Chapter one', $row['header']);
    }

    public function testSecondContentElementHasHeaderTypeTwo(): void
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tt_content');
        $row = $connection->select(['header_type'], 'tt_content', ['uid' => 101])->fetchAssociative();

        self::assertIsArray($row);
        self::assertSame(2, (int) $row['header_type']);
    }

    public function testHeaderTypeZeroIsPersistedForNonSemanticHeadlines(): void
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tt_content');
        $row = $connection->select(['header_type', 'header'], 'tt_content', ['uid' => 103])->fetchAssociative();

        self::assertIsArray($row);
        self::assertSame(0, (int) $row['header_type']);
        self::assertSame('Visual only', $row['header']);
    }

    public function testExtensionSchemaAddsHeaderTypeColumn(): void
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tt_content');
        $columns = $connection->createSchemaManager()->listTableColumns('tt_content');

        self::assertArrayHasKey('header_type', $columns);
    }
}
