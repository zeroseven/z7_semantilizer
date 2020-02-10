<?php

namespace Zeroseven\Semantilizer\Hooks;

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Frontend\Page\PageRepository;
use Zeroseven\Semantilizer\Services\BootstrapColorService;
use Zeroseven\Semantilizer\Services\HideNotificationStateService;
use Zeroseven\Semantilizer\Services\ValidationService;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class DrawHeaderHook
{

    /** @var array */
    protected $tsconfig = [];

    /** @var array */
    protected $pageInfo;

    /** @var UriBuilder */
    protected $uriBuilder;

    /** @var IconFactory */
    protected $iconFactory;

    /** @var array */
    protected $contentElements = [];

    /**  @var BackendUserAuthentication */
    protected $backendUser;

    /** @var bool */
    protected $hideNotifications = true;

    /** @var array */
    protected $modulData = [];

    /** @var string */
    private const VALIDATION_PARAMETER = 'semantilizer_hide_notifications';


    /** @var string */
    private const TABLE = 'tt_content';

    public function __construct()
    {
        $this->uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $this->pageInfo = BackendUtility::readPageAccess((int)GeneralUtility::_GP('id'), true);
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->hideNotifications = $this->setValidationCookie();
        $this->tsconfig = $this->getTsConfig();
        $this->modulData = BackendUtility::getModuleData([], null, 'web_layout');
    }

    private function simulateFrontend(): TypoScriptFrontendController
    {

        $pageUid = (int)GeneralUtility::_GP('id');

        // Get the translated page uid
        if($this->modulData['language']) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');

            // Remove 'AND (hidden = 0)' from the query
            $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);

            // Create query
            $pageUid = (int)$queryBuilder
                ->select('uid')
                ->from('pages')
                ->where($queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq('l10n_parent', $queryBuilder->createNamedParameter($pageUid, \PDO::PARAM_INT)),
                    $queryBuilder->expr()->in('sys_language_uid', $queryBuilder->createNamedParameter($this->modulData['language'], Connection::PARAM_INT))
                ))
                ->setMaxResults(1)
                ->execute()
                ->fetchColumn(0) ?: $pageUid;
        }

        // Hi Kasper Skaarhoj, if you read this:
        // I'm not sure anymore. This is really the best way to render typoscript by this hook?
        $TSFE = GeneralUtility::makeInstance(TypoScriptFrontendController::class, $GLOBALS['TYPO3_CONF_VARS'], $pageUid, (int)GeneralUtility::_GP('type'));
        $TSFE->set_no_cache();
        $TSFE->initFEuser();
        $TSFE->determineId();
        $TSFE->fetch_the_id();
        $TSFE->checkAlternativeIdMethods();
        $TSFE->newCObj();
        $TSFE->settingLanguage();
        $TSFE->settingLocale();

        return $TSFE;
    }

    private function getTsConfig(string $key = 'tx_semantilizer'): ?array
    {
        $pageId = (int)$this->pageInfo['uid'];
        $pagesTsConfig = BackendUtility::getPagesTSconfig($pageId);

        return $pagesTsConfig[$key . '.'];
    }

    private function setValidationCookie(): bool
    {

        $validate = GeneralUtility::_GP(self::VALIDATION_PARAMETER);

        if($validate === null) {
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

    protected function collectContentElements(): void
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
                $queryBuilder->expr()->notIn('CType', array_map(function($value) use ($queryBuilder) {return $queryBuilder->createNamedParameter($value, \PDO::PARAM_STR);}, (array)GeneralUtility::trimExplode(',', $this->tsconfig['ignoreCTypes'])))
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

    protected function prependTitle(): void
    {
        if(($renderType = $this->tsconfig['fixedPageTitle']) && ($config = $this->tsconfig['fixedPageTitle.']) && ($title = $this->simulateFrontend()->cObj->cObjGetSingle($renderType, $config))) {
            $this->contentElements = [['header' => $title, 'headerType' => 1]] + $this->contentElements;
        }
    }

    protected function skipSemantilzer(): bool
    {

        // Skip on some doktypes
        if(in_array((int)$this->pageInfo['doktype'], [
            PageRepository::DOKTYPE_LINK,
            PageRepository::DOKTYPE_SHORTCUT,
            PageRepository::DOKTYPE_BE_USER_SECTION,
            PageRepository::DOKTYPE_MOUNTPOINT,
            PageRepository::DOKTYPE_SPACER,
            PageRepository::DOKTYPE_SYSFOLDER,
            PageRepository::DOKTYPE_RECYCLER
        ], true)) {
            return true;
        }

        // Check the TSconfig
        if ($disableOnPages = $this->tsconfig['disableOnPages']) {
            return in_array((int)$this->pageInfo['uid'], GeneralUtility::intExplode(',', $disableOnPages), true);
        }

        return false;
    }

    /**
     * @return string
     */
    public function render(): string
    {

        // Abort on some doktypes
        if($this->skipSemantilzer()) {
            return '';
        }

        // Collect the content elements
        $this->collectContentElements();

        // Add prepend title
        $this->prependTitle();

        // Validate
        $validationService = GeneralUtility::makeInstance(ValidationService::class, $this->contentElements);

        // Set error state
        foreach ($validationService->getAffectedContentElements() as $affected) {
            $this->contentElements[$affected]['error'] = true;
        }

        // One or more contents are found
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:z7_semantilizer/Resources/Private/Templates/Backend/PageHeader.html'));
        $view->setPartialRootPaths([0 => GeneralUtility::getFileAbsFileName('EXT:z7_semantilizer/Resources/Private/Partials/Backend')]);
        $view->assignMultiple([
            'strongestNotificationLevel' => $validationService->getStrongestLevel(),
            'notifications' => $validationService->getNotifications(),
            'strongestNotificationClassname' => BootstrapColorService::getClassnameByFlashMessageState($validationService->getStrongestLevel()),
            'contentElements' => $this->contentElements,
            'hideNotifications' => $this->hideNotifications,
            'toggleValidationLink' => $this->getToggleValidationLink()
        ]);

        return $view->render();
    }
}
