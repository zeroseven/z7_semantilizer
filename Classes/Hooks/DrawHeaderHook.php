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
    protected $id;

    /** @var PageRenderer */
    protected $pageRenderer;

    /** @var array */
    protected $page;

    /** @var array */
    protected $tsConfig;

    public function __construct()
    {
        $this->id = uniqid('js-', false);
        $this->pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);

        $this->initializePage();
        $this->initializeTsConfig();
    }

    private function initializeTsConfig(): void
    {
        if (($pageUid = $this->page['uid'] ?? null) && $tsConfig = TsConfigUtility::getTsConfig((int)$pageUid)) {
            $this->tsConfig = $tsConfig;
        }
    }

    private function initializePage(): void
    {
        // Get the language by module data
        $moduleData = BackendUtility::getModuleData([], null, 'web_layout');
        $languageUid = (int)$moduleData['language'];
        $pageUid = (int)GeneralUtility::_GP('id');

        // Get page data
        $row = $languageUid ?
            (BackendUtility::getRecordLocalization('pages', $pageUid, $languageUid)[0]) :
            (BackendUtility::readPageAccess($pageUid, true) ?: []);

        // Store page data in array
        $this->page = PermissionUtility::showPage($row) ? $row : [];
    }

    private function skipSemantilizer(): bool
    {
        return

            // The page must be available
            empty($this->page)

            // Ts configuration can be loaded
            || empty($this->tsConfig)

            // The "doktype" must not be disabled
            || in_array((int)$this->page['doktype'], array_merge(self::IGNORED_DOKTYPES, GeneralUtility::intExplode(',', $this->tsConfig['disabledDoktypes'])), true)

            // The page uid must not be disabled
            || in_array((int)$this->page['uid'], GeneralUtility::intExplode(',', $this->tsConfig['disableOnPages']), true);
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
        // Define JavaScript parameters
        $url = GeneralUtility::quoteJSvalue($this->getPreviewUrl());
        $id = GeneralUtility::quoteJSvalue($this->id);
        $contentSelectors = json_encode(GeneralUtility::trimExplode(',', $this->tsConfig['contentSelectors']));

        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Z7Semantilizer/Backend/Semantilizer');
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
            'id' => $this->id
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
