<?php

declare(strict_types=1);

namespace Zeroseven\Semantilizer\Events;

use JsonException;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Backend\Controller\Event\ModifyPageLayoutContentEvent;
use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheGroupException;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\AbstractTemplateView;
use TYPO3\CMS\Fluid\View\StandaloneView;

class ValidationEvent
{
    protected const IGNORED_DOKTYPES = [
        PageRepository::DOKTYPE_LINK,
        PageRepository::DOKTYPE_SHORTCUT,
        PageRepository::DOKTYPE_BE_USER_SECTION,
        PageRepository::DOKTYPE_MOUNTPOINT,
        PageRepository::DOKTYPE_SPACER,
        PageRepository::DOKTYPE_SYSFOLDER
    ];

    protected string $identifier;
    protected PageRenderer $pageRenderer;
    protected int $pageUid;
    protected int $languageUid;
    protected array $tsConfig;

    public function __construct()
    {
        $this->identifier = uniqid('js-', false);
        $this->pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $this->pageUid = (int)($_GET['id'] ?? 0);
        $this->languageUid = (int)(BackendUtility::getModuleData([], null, 'web_layout')['language'] ?? 0);
        $this->tsConfig = $this->getTsConfig();
    }

    private function getTsConfig(): array
    {
        if ($pagesTsConfig = BackendUtility::getPagesTSconfig($this->pageUid)) {
            return $pagesTsConfig['tx_semantilizer.'] ?? [];
        }

        return [];
    }

    private function getPageData(): ?array
    {
        if ($this->languageUid > 0) {
            return BackendUtility::getRecordLocalization('pages', $this->pageUid, $this->languageUid)[0] ?? null;
        }

        return BackendUtility::readPageAccess($this->pageUid, true) ?: null;
    }

    private function getPreviewUrl(): ?UriInterface
    {
        return PreviewUriBuilder::create($this->pageUid)->withLanguage($this->languageUid)->buildUri();
    }

    private function skipSemantilizer(): bool
    {
        return

            // The page must be available
            empty($pageData = $this->getPageData())

            // Ts configuration can be loaded
            || empty($this->tsConfig)

            // The "doktype" must not be disabled
            || in_array((int)($pageData['doktype'] ?? 0), array_merge(self::IGNORED_DOKTYPES, GeneralUtility::intExplode(',', ($this->tsConfig['disabledDoktypes'] ?? ''))), true)

            // The page uid must not be disabled
            || in_array($this->pageUid, GeneralUtility::intExplode(',', ($this->tsConfig['disabledPages'] ?? '')), true);
    }

    private function clearCache(): void
    {
        try {
            GeneralUtility::makeInstance(CacheManager::class)->flushCachesInGroupByTags('pages', ['pageId_' . $this->pageUid]);
        } catch (NoSuchCacheGroupException $e) {
        }
    }

    private function createView(): AbstractTemplateView
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:z7_semantilizer/Resources/Private/Templates/Backend/PageHeader.html'));
        $view->assign('identifier', $this->identifier);

        return $view;
    }

    /** @throws JsonException */
    private function render(): string
    {
        // Define JavaScript parameters
        $url = (string)$this->getPreviewUrl();
        $id = $this->identifier;
        $contentSelectors = GeneralUtility::trimExplode(',', ($this->tsConfig['contentSelectors'] ?? ''));

        // Configure page renderer
        $this->pageRenderer->addCssFile('EXT:z7_semantilizer/Resources/Public/Css/Backend/Styles.css');
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:z7_semantilizer/Resources/Private/Language/locallang.xlf', 'overview');
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:z7_semantilizer/Resources/Private/Language/locallang.xlf', 'notification');

        // Load module
        $target = JavaScriptModuleInstruction::create('@zeroseven/semantilizer/Semantilizer.js', 'Semantilizer')->instance($url, $id, ...$contentSelectors);
        $this->pageRenderer->getJavaScriptRenderer()->addJavaScriptModuleInstruction($target);

        // Clear frontend cache
        $this->clearCache();

        // Render view
        return $this->createView()->render();
    }

    /** @throws JsonException */
    public function __invoke(ModifyPageLayoutContentEvent $event): void
    {
        $this->skipSemantilizer() || $event->addHeaderContent($this->render());
    }
}
