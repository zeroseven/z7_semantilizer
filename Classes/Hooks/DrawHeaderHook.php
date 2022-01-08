<?php

namespace Zeroseven\Semantilizer\Hooks;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Routing\UnableToLinkToPageException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\AbstractTemplateView;
use TYPO3\CMS\Fluid\View\StandaloneView;
use Zeroseven\Semantilizer\Utility\PermissionUtility;
use Zeroseven\Semantilizer\Utility\TsConfigUtility;

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

    /** @var string */
    protected $identifier;

    /** @var PageRenderer */
    protected $pageRenderer;

    /** int */
    protected $pageUid;

    /** int */
    protected $languageUid;

    /** @var array */
    protected $tsConfig;

    public function __construct()
    {
        $this->identifier = uniqid('js-', false);
        $this->pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $this->pageUid = (int)GeneralUtility::_GP('id');
        $this->languageUid = (int)(BackendUtility::getModuleData([], null, 'web_layout')['language'] ?? 0);
        $this->tsConfig = TsConfigUtility::getTsConfig($this->pageUid);
    }

    private function getPageData(): array
    {
        $row = $this->languageUid > 0 ?
            (BackendUtility::getRecordLocalization('pages', $this->pageUid, $this->languageUid)[0]) :
            (BackendUtility::readPageAccess($this->pageUid, true) ?: []);

        return PermissionUtility::showPage($row) ? $row : [];
    }

    private function skipSemantilizer(): bool
    {
        return

            // The page must be available
            empty($pageData = $this->getPageData())

            // Ts configuration can be loaded
            || empty($this->tsConfig)

            // The "doktype" must not be disabled
            || in_array((int)$pageData['doktype'], array_merge(self::IGNORED_DOKTYPES, GeneralUtility::intExplode(',', $this->tsConfig['disabledDoktypes'])), true)

            // The page uid must not be disabled
            || in_array($this->pageUid, GeneralUtility::intExplode(',', $this->tsConfig['disableOnPages']), true);
    }

    private function getPreviewUrl(): ?string
    {
        $id = (int)GeneralUtility::_GP('id');

        try {
            return BackendUtility::getPreviewUrl($id, '', null, '', '', $this->languageUid > 0 ? '&L=' . $this->languageUid : '');
        } catch (UnableToLinkToPageException $e) {
            return null;
        }
    }

    private function includeJavaScript(): void
    {
        // Define JavaScript parameters
        $url = GeneralUtility::quoteJSvalue($this->getPreviewUrl());
        $id = GeneralUtility::quoteJSvalue($this->identifier);
        $contentSelectors = json_encode(GeneralUtility::trimExplode(',', $this->tsConfig['contentSelectors']));

        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Z7Semantilizer/Backend/Semantilizer');
        $this->pageRenderer->addCssFile('EXT:z7_semantilizer/Resources/Public/Css/Backend/Styles.css');
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:z7_semantilizer/Resources/Private/Language/locallang.xlf', 'overview');
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:z7_semantilizer/Resources/Private/Language/locallang.xlf', 'notification');
        $this->pageRenderer->addJsFooterInlineCode(self::class, sprintf('
            require(["TYPO3/CMS/Z7Semantilizer/Backend/Semantilizer"], function(Semantilizer) {
                Semantilizer = new Semantilizer(%s, %s, %s);
            });
        ', $url, $id, $contentSelectors));
    }

    private function createView(): AbstractTemplateView
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:z7_semantilizer/Resources/Private/Templates/Backend/PageHeader.html'));
        $view->setPartialRootPaths([0 => GeneralUtility::getFileAbsFileName('EXT:z7_semantilizer/Resources/Private/Partials/Backend')]);
        $view->assignMultiple([
            'id' => $this->identifier
        ]);

        return $view;
    }

    public function render(): string
    {
        // Skip rendering
        if ($this->skipSemantilizer()) {
            return '';
        }

        $this->includeJavaScript();

        return $this->createView()->render();
    }
}
