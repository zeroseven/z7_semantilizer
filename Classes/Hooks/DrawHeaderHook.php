<?php

namespace Zeroseven\Semantilizer\Hooks;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Routing\UnableToLinkToPageException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

class DrawHeaderHook
{

    /** @var array */
    protected const IGNORED_DOKTYPES = [
        PageRepository::DOKTYPE_LINK,
        PageRepository::DOKTYPE_SHORTCUT,
        PageRepository::DOKTYPE_BE_USER_SECTION,
        PageRepository::DOKTYPE_MOUNTPOINT,
        PageRepository::DOKTYPE_SPACER,
        PageRepository::DOKTYPE_SYSFOLDER,
        PageRepository::DOKTYPE_RECYCLER
    ];

    protected PageRenderer $pageRenderer;

    protected string $id;

    public function __construct()
    {
        $this->pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $this->id = uniqid('js-', false);
    }

    private function skipSemantilizer(): bool
    {
        return false;
    }

    private function getPreviewUrl(): ?string
    {
        $id = (int)GeneralUtility::_GP('id');

        try {
            return BackendUtility::getPreviewUrl($id);
        } catch (UnableToLinkToPageException $e) {
            return null;
        }
    }

    private function includeJavaScript(): void
    {
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Z7Semantilizer/Backend/Semantilizer');
        $this->pageRenderer->addJsFooterInlineCode(self::class, sprintf('
            require(["TYPO3/CMS/Z7Semantilizer/Backend/Semantilizer"], semantilizer => {
                window.TYPO3 = window.TYPO3 || {};
                window.TYPO3.Semantilizer = new semantilizer(%s, %s, %s);
            });
        ', GeneralUtility::quoteJSvalue($this->getPreviewUrl()), GeneralUtility::quoteJSvalue($this->id), GeneralUtility::quoteJSvalue(null)));
    }

    public function render(): string
    {
        // Skip rendering
        if ($this->skipSemantilizer()) {
            return '';
        }

        $this->includeJavaScript();

        // One or more contents are found
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:z7_semantilizer/Resources/Private/Templates/Backend/PageHeader.html'));
        $view->setPartialRootPaths([0 => GeneralUtility::getFileAbsFileName('EXT:z7_semantilizer/Resources/Private/Partials/Backend')]);
        $view->assignMultiple([
            'id' => $this->id
        ]);

        return $view->render();
    }
}
