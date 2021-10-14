<?php

namespace Zeroseven\Semantilizer\Hooks;

use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

class DrawHeaderHook
{

    /** @var array */
    public const IGNORED_DOKTYPES = [
        PageRepository::DOKTYPE_LINK,
        PageRepository::DOKTYPE_SHORTCUT,
        PageRepository::DOKTYPE_BE_USER_SECTION,
        PageRepository::DOKTYPE_MOUNTPOINT,
        PageRepository::DOKTYPE_SPACER,
        PageRepository::DOKTYPE_SYSFOLDER,
        PageRepository::DOKTYPE_RECYCLER
    ];

    private function skipSemantilizer(): bool
    {
        return false;
    }

    public function render(): string
    {
        // Skip rendering
        if ($this->skipSemantilizer()) {
            return '';
        }

        // One or more contents are found
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:z7_semantilizer/Resources/Private/Templates/Backend/PageHeader.html'));
        $view->setPartialRootPaths([0 => GeneralUtility::getFileAbsFileName('EXT:z7_semantilizer/Resources/Private/Partials/Backend')]);
        $view->assignMultiple([

        ]);

        return $view->render();
    }
}
