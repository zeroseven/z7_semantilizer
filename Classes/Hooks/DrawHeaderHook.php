<?php

namespace Zeroseven\Semantilizer\Hooks;

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Frontend\Page\PageRepository;
use Zeroseven\Semantilizer\FixedTitle\FixedTitleInterface;
use Zeroseven\Semantilizer\Services\BootstrapColorService;
use Zeroseven\Semantilizer\Services\HideNotificationStateService;
use Zeroseven\Semantilizer\Services\PageInfoService;
use Zeroseven\Semantilizer\Services\TsConfigService;
use Zeroseven\Semantilizer\Utilities\ValidationUtility;

class DrawHeaderHook
{

    /** @var array */
    private $tsConfig;

    /** @var array */
    private $pageInfo;

    /** @var array */
    private $modulData;

    /** @var array */
    private $contentElements;

    /** @var UriBuilder */
    private $uriBuilder;

    /** @var bool */
    private $hideNotifications;

    /** @var array */
    public $ignoreDoktypes = [
        PageRepository::DOKTYPE_LINK,
        PageRepository::DOKTYPE_SHORTCUT,
        PageRepository::DOKTYPE_BE_USER_SECTION,
        PageRepository::DOKTYPE_MOUNTPOINT,
        PageRepository::DOKTYPE_SPACER,
        PageRepository::DOKTYPE_SYSFOLDER,
        PageRepository::DOKTYPE_RECYCLER
    ];

    /** @var string */
    private const VALIDATION_PARAMETER = 'semantilizer_hide_notifications';

    /** @var string */
    private const TABLE = 'tt_content';

    public function __construct()
    {
        $this->pageInfo = PageInfoService::getPageInfo();
        $this->tsConfig = TsConfigService::getTsConfig((int)$this->pageInfo['uid']);
        $this->modulData = BackendUtility::getModuleData([], null, 'web_layout');
        $this->uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
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
        return $this->uriBuilder->buildUriFromRoute('web_layout', [
            self::VALIDATION_PARAMETER => (int)!$this->hideNotifications,
            'id' => $this->pageInfo['uid'],
        ]);
    }

    private function getEditRecordUrl(int $uid, string $table = null): string
    {
        return $this->uriBuilder->buildUriFromRoute('record_edit', [
            'edit' => [
                $table ?: self::TABLE => [
                    $uid => 'edit'
                ]
            ],
            'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
        ]);
    }

    private function collectContentElements(): void
    {
        // The retuning array
        $contentElements = [];

        // Get instance of the query builder
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE);

        // Get results from the database
        $results = $queryBuilder->select('uid', 'header', 'header_type')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->gt('header_type', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)),
                $queryBuilder->expr()->lt('header_layout', $queryBuilder->createNamedParameter(100, \PDO::PARAM_INT)),
                $queryBuilder->expr()->neq('header', $queryBuilder->createNamedParameter('')),
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($this->pageInfo['uid'], \PDO::PARAM_INT)),
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter((int)$this->modulData['language'], \PDO::PARAM_INT)),
                $queryBuilder->expr()->notIn('CType', array_map(function ($value) use ($queryBuilder) {
                    return $queryBuilder->createNamedParameter($value, \PDO::PARAM_STR);
                }, (array)GeneralUtility::trimExplode(',', $this->tsConfig['ignoreCTypes'])))
            )
            ->orderBy('sorting')
            ->execute()
            ->fetchAll() ?: [];

        // Add some links and attributes to the content elements
        foreach ($results as $i => $row) {

            $uid = $row['uid'];

            // Add some additional stuff
            $contentElements[$uid]['error'] = false;
            $contentElements[$uid]['editLink'] = $this->getEditRecordUrl($uid);

            // Convert the keys to lowerCamelCase
            foreach ($row as $key => $value) {
                $contentElements[$uid][GeneralUtility::underscoredToLowerCamelCase($key)] = $value;
            }
        }

        $this->contentElements = $contentElements;
    }

    private function setFixedTitle(): void
    {
        $fixedTitle = null;
        $hookParameter = [
            'uid' => (int)GeneralUtility::_GP('id'),
            'sys_language_uid' => (int)$this->modulData['language'],
            'row' => $this->pageInfo,
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
            $this->contentElements = [['header' => $fixedTitle, 'headerType' => 1]] + $this->contentElements;
        }
    }

    private function skipSemantilizer(): bool
    {

        // Skip on some doktypes
        if (in_array((int)$this->pageInfo['doktype'], $this->ignoreDoktypes, true)) {
            return true;
        }

        // Check the TSconfig
        if ($disableOnPages = $this->tsConfig['disableOnPages']) {
            return in_array((int)$this->pageInfo['uid'], GeneralUtility::intExplode(',', $disableOnPages), true);
        }

        return false;
    }

    public function render(): string
    {

        // Abort on some doktypes
        if ($this->skipSemantilizer()) {
            return '';
        }

        // Collect the content elements
        $this->collectContentElements();

        // Add prepend title
        $this->setFixedTitle();

        // Validate
        $validationUtility = GeneralUtility::makeInstance(ValidationUtility::class, $this->contentElements);

        // Set error state
        foreach ($validationUtility->getAffectedContentElements() as $affected) {
            $this->contentElements[$affected]['error'] = true;
        }

        // One or more contents are found
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:z7_semantilizer/Resources/Private/Templates/Backend/PageHeader.html'));
        $view->setPartialRootPaths([0 => GeneralUtility::getFileAbsFileName('EXT:z7_semantilizer/Resources/Private/Partials/Backend')]);
        $view->assignMultiple([
            'strongestNotificationLevel' => $validationUtility->getStrongestLevel(),
            'notifications' => $validationUtility->getNotifications(),
            'strongestNotificationClassname' => BootstrapColorService::getClassnameByFlashMessageState($validationUtility->getStrongestLevel()),
            'contentElements' => $this->contentElements,
            'hideNotifications' => $this->hideNotifications,
            'toggleValidationLink' => $this->getToggleValidationLink()
        ]);

        return $view->render();
    }

}
