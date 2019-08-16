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

    /** @var string */
    private const validationSessionKey = 'semantilizer_validation';

    /** @var string */
    private const table = 'tt_content';

    /** @var array */
    private const states = [
        'notice' => FlashMessage::NOTICE,
        'info' => FlashMessage::INFO,
        'ok' => FlashMessage::OK,
        'warning' => FlashMessage::WARNING,
        'error' => FlashMessage::ERROR
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
        $validate = GeneralUtility::_GP(self::validationSessionKey);

        if($validate === null) {
            $sessionData = $this->backendUser->getSessionData(self::validationSessionKey, 1);
            return $sessionData === null ? $this->validationEnabled : (bool)$sessionData;
        }

        $this->backendUser->setSessionData(self::validationSessionKey, (int)$validate);

        return (bool)$validate;

    }

    private function getToggleValidationLink(): string
    {
        return $this->uriBuilder->buildUriFromRoute('web_layout', [
            self::validationSessionKey => (int)!$this->validationEnabled,
            'id' => $this->pageInfo['uid'],
            'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
        ]);
    }

    private function getEditRecordUrl(int $uid, string $table = null): string
    {
        return $this->uriBuilder->buildUriFromRoute('record_edit', [
            'edit' => [
                $table ?: self::table => [
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
        foreach ($GLOBALS['TCA'][self::table]['columns']['header_type']['config']['items'] as $item) {
            $highestType = (int)max($highestType, $item[1]);
        }

        // Check, if the new type is valid
        if($newType < $lowestType || $newType > $highestType) {
            return null;
        }

        return BackendUtility::getLinkToDataHandlerAction(
            sprintf('&data[%s][%d][%s]=%d', self::table, $row['uid'], 'header_type', $newType),
            GeneralUtility::getIndpEnv('REQUEST_URI')
        );
    }

    protected function collectContentElements(): array
    {

        // Get instance of the query builder
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::table);

        // Get results from the database
        return $queryBuilder->select('uid', 'header', 'header_type')
            ->from(self::table)
            ->where(
                // $queryBuilder->expr()->gt('header_type', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)),
                $queryBuilder->expr()->lt('header_layout', $queryBuilder->createNamedParameter(100, \PDO::PARAM_INT)),
                $queryBuilder->expr()->neq('header', $queryBuilder->createNamedParameter('')),
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($this->pageInfo['uid'], \PDO::PARAM_INT))
            )
            ->execute()
            ->fetchAll() ?: [];

        // Convert the keys to lowerCamelCase
        //foreach ($results as $index => $row) {
        //    foreach ($row as $key => $value) {
        //        $contentElements[$index][GeneralUtility::underscoredToLowerCamelCase($key)] = $value;
        //    }
        //}
        //
        //return $contentElements;
    }

    /**
     * @return string
     */
    public function render(): string
    {

        // Add edit links
        foreach ($this->contentElements as $index => $contentElement) {
            $this->contentElements[$index]['editLink'] = $this->getEditRecordUrl($contentElement['uid']);
            $this->contentElements[$index]['moveUpLink'] = $this->getMoveHierarchyLink($contentElement, 1);
            $this->contentElements[$index]['moveDownLink'] = $this->getMoveHierarchyLink($contentElement, -1);
        }

        // One or more contents are found
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:z7_semantilizer/Resources/Private/Templates/Backend/PageHeader.html'));
        $view->assignMultiple([
            'state' => self::states['notice'],
            'contentElements' => $this->contentElements,
            'validationEnabled' => $this->validationEnabled,
            'toggleValidationLink' => $this->getToggleValidationLink()
        ]);

        return $view->render();
    }

}
