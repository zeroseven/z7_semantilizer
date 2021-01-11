<?php

declare(strict_types=1);

namespace Zeroseven\Semantilizer\Hooks;

use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Page\PageRenderer;

class PageRendererRenderPreProcess
{
    public function addPageRendererConfiguration(array $params, PageRenderer $pageRenderer): void
    {
        if (ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isBackend()) {
            $pageRenderer->loadRequireJsModule('TYPO3/CMS/Z7Semantilizer/Backend/Semantilizer');
            $pageRenderer->addInlineLanguageLabelFile('EXT:z7_semantilizer/Resources/Private/Language/locallang.xlf');
        }
    }
}
