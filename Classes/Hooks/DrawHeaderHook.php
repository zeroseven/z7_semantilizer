<?php

namespace Zeroseven\Semantilizer\Hooks;

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Frontend\Page\PageRepository;

class DrawHeaderHook
{

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
    protected $validationEnabled = true;

    /** @var array */
    protected $notifications = [];

    /** @var string */
    private const VALIDATION_SESSION_KEY = 'semantilizer_validation';

    /** @var string */
    private const TABLE = 'tt_content';

    /** @var array */
    private const STATES = [
        'notice' => FlashMessage::NOTICE,
        'info' => FlashMessage::INFO,
        'ok' => FlashMessage::OK,
        'warning' => FlashMessage::WARNING,
        'error' => FlashMessage::ERROR
    ];

    /** @var array */
    private const ERROR_CODES = [
        'missing_h1' => 1,
        'double_h1' => 2,
        'wrong_ordered_h1' => 3,
        'unexpected_heading' => 4,
    ];

    public function __construct()
    {
        $this->uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $this->pageInfo = BackendUtility::readPageAccess((int)GeneralUtility::_GP('id'), true);
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->backendUser = GeneralUtility::makeInstance(BackendUserAuthentication::class);
        $this->contentElements = $this->collectContentElements();
        $this->validationEnabled = $this->setValidationCookie();
    }

    private function setValidationCookie(): bool
    {
        $validate = GeneralUtility::_GP(self::VALIDATION_SESSION_KEY);

        if($validate === null) {
            $sessionData = $this->backendUser->getSessionData(self::VALIDATION_SESSION_KEY, 1);
            return $sessionData === null ? $this->validationEnabled : (bool)$sessionData;
        }

        $this->backendUser->setSessionData(self::VALIDATION_SESSION_KEY, (int)$validate);

        return (bool)$validate;

    }

    private function getToggleValidationLink(): string
    {
        return $this->uriBuilder->buildUriFromRoute('web_layout', [
            self::VALIDATION_SESSION_KEY => (int)!$this->validationEnabled,
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

    private function getMoveHierarchyLink(array $row, int $move): ?string
    {
        $currentType = (int)$row['header_type'];
        $newType = $move > 0 ? $currentType - 1 : $currentType + 1;
        $lowestType = 1;
        $highestType = $lowestType;

        // Get the highest type from the tca configuration
        foreach ($GLOBALS['TCA'][self::TABLE]['columns']['header_type']['config']['items'] as $item) {
            $highestType = (int)max($highestType, $item[1]);
        }

        // Check, if the new type is valid
        if($newType < $lowestType || $newType > $highestType) {
            return null;
        }

        return BackendUtility::getLinkToDataHandlerAction(
            sprintf('&data[%s][%d][%s]=%d', self::TABLE, $row['uid'], 'header_type', $newType),
            GeneralUtility::getIndpEnv('REQUEST_URI')
        );
    }

    protected function collectContentElements(): array
    {

        // The retuning array
        $contentElements = [];

        // Get instance of the query builder
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE);

        // Get results from the database
        $results = $queryBuilder->select('uid', 'header', 'header_type')
            ->from(self::TABLE)
            ->where(
                // $queryBuilder->expr()->gt('header_type', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)),
                $queryBuilder->expr()->lt('header_layout', $queryBuilder->createNamedParameter(100, \PDO::PARAM_INT)),
                $queryBuilder->expr()->neq('header', $queryBuilder->createNamedParameter('')),
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($this->pageInfo['uid'], \PDO::PARAM_INT))
            )
            ->execute()
            ->fetchAll() ?: [];


        // Add some links and attributes to the content elements
        $i = 0;
        foreach ($results as $contentElement) {

            // Pass all data
            $contentElements[$i] = $contentElement;

            // Add some additional stuff
            $contentElements[$i]['error'] = false;
            $contentElements[$i]['editLink'] = $this->getEditRecordUrl($contentElement['uid']);
            $contentElements[$i]['moveUpLink'] = $this->getMoveHierarchyLink($contentElement, 1);
            $contentElements[$i]['moveDownLink'] = $this->getMoveHierarchyLink($contentElement, -1);

            // Convert the keys to lowerCamelCase
            //foreach ($row as $key => $value) {
                //$contentElements[$i][GeneralUtility::underscoredToLowerCamelCase($key)] = $value;
            //}

            $i++;
        }

        return $contentElements;
    }

    protected function registerNotification(string $errorCode, array $contentElements = null, string $state = 'warning'): void
    {
        $this->notifications[] = [
            'key' => self::ERROR_CODES[$errorCode],
            'state' => self::STATES[$state],
            'contentElements' => $contentElements
        ];

        if(is_array($contentElements)) {
            foreach ($contentElements as $index => $contentElement) {
                $this->contentElements[$index]['error'] = true;
            }
        }
    }

    protected function setErrorNotifications(): void
    {

        $mainHeadingContents = [];
        $unexpectedHeadingContents = [];
        $lastHeadingType = 0;

        foreach ($this->contentElements as $index => $contentElement) {

            // Get the header_type
            $type = (int)$contentElement['header_type'];

            if($type > 0) {

                // Check for the h1
                if ($type === 1) {
                    $mainHeadingContents[$index] = $contentElement;
                }

                // Check if the headlines are nested in the right way
                if ($lastHeadingType > 0 && $type > $lastHeadingType + 1) {
                    $unexpectedHeadingContents[$index] = $contentElement;
                }

                // Store the last headline type
                $lastHeadingType = $type;
            }
        }

        // Check the length of the main heading(s)
        if(count($mainHeadingContents) === 0) {
            $this->registerNotification('missing_h1', $this->contentElements);
        } elseif (count($mainHeadingContents) > 1) {
            $this->registerNotification('double_h1', $mainHeadingContents);
        } elseif ((int)array_keys($mainHeadingContents)[0] > 0) {
            $this->registerNotification('wrong_ordered_h1', [0 => $this->contentElements[0]] + $mainHeadingContents);
        }

        // Add a notification for the unexpected ones
        if(!empty($unexpectedHeadingContents)) {
            $this->registerNotification('unexpected_heading', $unexpectedHeadingContents);
        }

    }

    /**
     * @return string
     */
    public function render(): string
    {

        // Abort on some doktypes
        if(in_array((int)$this->pageInfo['doktype'], [
            PageRepository::DOKTYPE_LINK,
            PageRepository::DOKTYPE_SHORTCUT,
            PageRepository::DOKTYPE_BE_USER_SECTION,
            PageRepository::DOKTYPE_MOUNTPOINT,
            PageRepository::DOKTYPE_SPACER,
            PageRepository::DOKTYPE_SYSFOLDER,
            PageRepository::DOKTYPE_RECYCLER
        ], true)) {
            return '';
        }

        // Validate
        $this->setErrorNotifications();

        // One or more contents are found
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:z7_semantilizer/Resources/Private/Templates/Backend/PageHeader.html'));
        $view->setPartialRootPaths([0 => GeneralUtility::getFileAbsFileName('EXT:z7_semantilizer/Resources/Private/Partials/Backend')]);
        $view->assignMultiple([
            'states' => self::STATES,
            'contentElements' => $this->contentElements,
            'validationEnabled' => $this->validationEnabled,
            'notifications' => $this->notifications,
            'toggleValidationLink' => $this->getToggleValidationLink()
        ]);

        return $view->render();
    }

}
