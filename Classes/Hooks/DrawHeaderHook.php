<?php

namespace Zeroseven\Semantilizer\Hooks;

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use Zeroseven\Semantilizer\FixedTitle\FixedTitleInterface;
use Zeroseven\Semantilizer\Models\ContentCollection;
use Zeroseven\Semantilizer\Services\BootstrapColorService;
use Zeroseven\Semantilizer\Services\HideNotificationStateService;
use Zeroseven\Semantilizer\Services\TsConfigService;
use Zeroseven\Semantilizer\Models\ContentData;
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

    /** @var string */
    private const TABLE = 'tt_content';

    public function __construct()
    {
        $this->page = PageData::makeInstance();
        $this->tsConfig = TsConfigService::getTsConfig($this->page->getUid());
        $this->moduleData = BackendUtility::getModuleData([], null, 'web_layout');
        $this->contentCollection = GeneralUtility::makeInstance(ContentCollection::class);
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

    private function collectContentElements(): void
    {

        // Get instance of the query builder
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE);

        // Get results from the database
        $results = $queryBuilder->select('uid', 'header', 'header_type', 'cType')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->gt('header_type', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)),
                $queryBuilder->expr()->lt('header_layout', $queryBuilder->createNamedParameter(100, \PDO::PARAM_INT)),
                $queryBuilder->expr()->neq('header', $queryBuilder->createNamedParameter('')),
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($this->page->getUid(), \PDO::PARAM_INT)),
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter((int)$this->moduleData['language'], \PDO::PARAM_INT)),
                $queryBuilder->expr()->notIn('CType', array_map(function ($value) use ($queryBuilder) {
                    return $queryBuilder->createNamedParameter($value, \PDO::PARAM_STR);
                }, (array)GeneralUtility::trimExplode(',', $this->tsConfig['ignoreCTypes'])))
            )
            ->orderBy('sorting')
            ->execute()
            ->fetchAll() ?: [];

        // Add some links and attributes to the content elements
        foreach ($results as $row) {
            $contentElement = GeneralUtility::makeInstance(ContentData::class, $row);
            $this->contentCollection->append($contentElement);
        }
    }

    private function setFixedTitle(): void
    {
        $fixedTitle = null;
        $hookParameter = [
            'uid' => $this->page->getUid(),
            'sys_language_uid' => (int)$this->moduleData['language'],
            'page' => $this->page,
            'tsConfig' => $this->tsConfig
        ];

        if ($hooks = $GLOBALS['TYPO3_CONF_VARS']['EXT']['z7_semantilizer']['fixedPageTitle'] ?? null) {

            // Sort them by their keys
            ksort($hooks);

            // Loop them
            foreach ($hooks as $className) {
                if (empty($fixedTitle) && class_exists($className) && is_subclass_of($className, FixedTitleInterface::class)) {
                    if ($overrideTitle = GeneralUtility::callUserFunction($className . '->get', $hookParameter, $this)) {
                        $fixedTitle = $overrideTitle;
                    }
                }
            }
        }

        if ($fixedTitle) {
            $contentElement = GeneralUtility::makeInstance(ContentData::class, ['header' => $fixedTitle, 'headerType' => 1, '__fixed' => true]);
            $this->contentCollection->prepend($contentElement);
        }
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
        $this->collectContentElements();

        // Add prepend title
        $this->setFixedTitle();

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
