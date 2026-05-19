<?php

declare(strict_types=1);

namespace Zeroseven\Semantilizer\Tests\Functional;

use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use Zeroseven\Semantilizer\Tests\Functional\Support\FrontendSiteTrait;

final class SemantilizerFrontendTest extends FunctionalTestCase
{
    use FrontendSiteTrait;

    protected array $testExtensionsToLoad = [
        'zeroseven/z7-semantilizer',
    ];

    protected array $coreExtensionsToLoad = [
        'core',
        'frontend',
        'fluid_styled_content',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Database/semantilizer.csv');
    }

    public function testSemanticHeadlinesAreRenderedOnDemoPage(): void
    {
        $this->setUpFrontendSiteWithSemantilizerTypoScript();

        $response = $this->executeFrontendSubRequest(
            new InternalRequest('https://example.com/semantilizer-demo'),
        );

        $body = (string) $response->getBody();
        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('Chapter one', $body);
        self::assertStringContainsString('Section two', $body);
        self::assertStringContainsString('Subsection three', $body);
        self::assertStringContainsString('<h1', $body);
        self::assertStringContainsString('<h2', $body);
        self::assertStringContainsString('<h3', $body);
        self::assertStringContainsString('ce__header', $body);
    }

    public function testNonSemanticHeaderTypeRendersDivWithDataHeading(): void
    {
        $this->setUpFrontendSiteWithSemantilizerTypoScript();

        $response = $this->executeFrontendSubRequest(
            new InternalRequest('https://example.com/semantilizer-demo'),
        );

        $body = (string) $response->getBody();
        self::assertStringContainsString('Visual only', $body);
        self::assertStringContainsString('data-heading="true"', $body);
        self::assertStringContainsString('ce__header', $body);
        self::assertStringNotContainsString('<h1>Visual only</h1>', $body);
    }

    public function testHeadlineHierarchyOrderIsPreservedInMarkup(): void
    {
        $this->setUpFrontendSiteWithSemantilizerTypoScript();

        $response = $this->executeFrontendSubRequest(
            new InternalRequest('https://example.com/semantilizer-demo'),
        );

        $body = (string) $response->getBody();
        $h1Pos = strpos($body, '<h1');
        $h2Pos = strpos($body, '<h2');
        $h3Pos = strpos($body, '<h3');

        self::assertNotFalse($h1Pos);
        self::assertNotFalse($h2Pos);
        self::assertNotFalse($h3Pos);
        self::assertLessThan($h2Pos, $h1Pos);
        self::assertLessThan($h3Pos, $h2Pos);
    }
}
