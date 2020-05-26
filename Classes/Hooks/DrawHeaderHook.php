<?php

namespace Zeroseven\Semantilizer\Hooks;

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use Zeroseven\Semantilizer\Models\ContentCollection;
use Zeroseven\Semantilizer\Services\BootstrapColorService;
use Zeroseven\Semantilizer\Services\HideNotificationStateService;
use Zeroseven\Semantilizer\Services\TsConfigService;
use Zeroseven\Semantilizer\Utilities\CollectContentUtility;
use Zeroseven\Semantilizer\Models\PageData;
use Zeroseven\Semantilizer\Utilities\ValidationUtility;

class DrawHeaderHook
{

    /** @var array */
    private $tsConfig;

    /** @var PageData */
    private $page;

    /** @var array */
    private $moduleData;

    /** @var ContentCollection */
    private $contentCollection;

    /** @var bool */
    private $hideNotifications;

    /** @var string */
    private const VALIDATION_PARAMETER = 'semantilizer_hide_notifications';

    public function __construct()
    {
        $this->page = PageData::makeInstance();
        $this->tsConfig = TsConfigService::getTsConfig($this->page->getUid());
        $this->moduleData = BackendUtility::getModuleData([], null, 'web_layout');
        $this->hideNotifications = $this->setValidationCookie();
    }

    private function setValidationCookie(): bool
    {

        $validate = GeneralUtility::_GP(self::VALIDATION_PARAMETER);

        if ($validate === null) {
            return HideNotificationStateService::getState();
        }

        return HideNotificationStateService::setState((bool)$validate);
    }

    private function getToggleValidationLink(): string
    {
        return GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute('web_layout', [
            self::VALIDATION_PARAMETER => (int)!$this->hideNotifications,
            'id' => $this->page->getUid(),
        ]);
    }

    private function skipSemantilizer(): bool
    {

        // Skip on some doktypes
        if ($this->page->isIgnoredDoktype()) {
            return true;
        }

        // Check the TSconfig
        if ($disableOnPages = $this->tsConfig['disableOnPages']) {
            return in_array($this->page->getUid(), GeneralUtility::intExplode(',', $disableOnPages), true);
        }

        return false;
    }

    public function render(): string
    {

        // Skip rendering
        if ($this->skipSemantilizer()) {
            return '';
        }

        // Collect the content elements
        $collectContentUtility = GeneralUtility::makeInstance(CollectContentUtility::class, $this->page->getUid(), (int)$this->moduleData['language'], $this->tsConfig, $this->page);
        $this->contentCollection = $collectContentUtility->getCollection();

        // Validate
        $validationUtility = GeneralUtility::makeInstance(ValidationUtility::class, $this->contentCollection);

        // Set error state
        foreach ($validationUtility->getAffectedContentElements() as $affected) {
            $this->contentCollection->overrideElement($affected);
        }

        // One or more contents are found
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:z7_semantilizer/Resources/Private/Templates/Backend/PageHeader.html'));
        $view->setPartialRootPaths([0 => GeneralUtility::getFileAbsFileName('EXT:z7_semantilizer/Resources/Private/Partials/Backend')]);
        $view->assignMultiple([
            'strongestNotificationLevel' => $validationUtility->getStrongestLevel(),
            'notifications' => $validationUtility->getNotifications(),
            'strongestNotificationClassname' => BootstrapColorService::getClassnameByFlashMessageState($validationUtility->getStrongestLevel()),
            'contentElements' => $this->contentCollection->getElements(),
            'hideNotifications' => $this->hideNotifications,
            'toggleValidationLink' => $this->getToggleValidationLink()
        ]);

        return $view->render();
    }

}
