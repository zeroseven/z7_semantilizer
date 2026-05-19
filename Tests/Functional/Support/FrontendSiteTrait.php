<?php

declare(strict_types=1);

namespace Zeroseven\Semantilizer\Tests\Functional\Support;

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

trait FrontendSiteTrait
{
    protected function setUpFrontendSiteWithSemantilizerTypoScript(): void
    {
        $this->setUpFrontendRootPage(1, [
            'EXT:fluid_styled_content/Configuration/TypoScript/',
            'EXT:z7_semantilizer/Configuration/TypoScript/',
        ]);

        $this->addTypoScriptToTemplateRecord(
            1,
            <<<'EOT'
page = PAGE
page {
    typeNum = 0
    10 = CONTENT
    10 {
        table = tt_content
        select {
            orderBy = sorting
            where = {#colPos}=0
        }
    }
}
EOT,
        );

        $this->writeFrontendSiteConfiguration();
    }

    protected function writeFrontendSiteConfiguration(): void
    {
        $sitePath = Environment::getConfigPath() . '/sites/main';
        if (!is_dir($sitePath)) {
            GeneralUtility::mkdir_deep($sitePath);
        }

        GeneralUtility::writeFile(
            $sitePath . '/config.yaml',
            <<<'YAML'
base: 'https://example.com/'
rootPageId: 1
languages:
  -
    title: English
    enabled: true
    languageId: 0
    base: /
    locale: en_US.UTF-8
    navigationTitle: English
    flag: us
YAML,
        );

        GeneralUtility::makeInstance(CacheManager::class)->flushCachesInGroup('system');
    }
}
