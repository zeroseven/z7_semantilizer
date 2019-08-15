<?php

namespace Zeroseven\Semantilizer\Hooks;

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\Buttons\LinkButton;
use TYPO3\CMS\Core\Imaging\Icon;
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

    /** @var string */
    private const table = 'tt_content';

    /** @var array */
    private const state = [
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
        $this->contentElements = $this->collectContentElements();
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
        $lowestType = 1;
        $highestType = $lowestType;

        foreach ($GLOBALS['TCA'][self::table]['columns']['header_type']['config']['items'] as $item) {
            $highestType = (int)max($highestType, $item[1]);
        }

        if($move > 0) {
            $newType = $currentType - 1;
            if($newType < $lowestType) {
                return null;
            }
        } else {
            $newType = $currentType + 1;
            if($newType > $highestType) {
                return null;
            }
        }

        return BackendUtility::getLinkToDataHandlerAction(
            sprintf('&data[%s][%d][%s]=%d', self::table, $row['uid'], 'header_type', $newType),
            GeneralUtility::getIndpEnv('REQUEST_URI')
        );


//        return GeneralUtility::makeInstance(LinkButton::class)
//            // TODO: ->setTitle(htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:z7_responsive_carousel/Resources/Private/Language/locallang_be.xlf:responsivecarousel.controls.hide')))
//            ->setClasses($moveIcon . ' ' . $disabledClass)
//            ->setHref(BackendUtility::getLinkToDataHandlerAction(
//                sprintf('&data[%s][%d][%s]=%d', self::table, $row['uid'], 'header_type', $newType),
//                GeneralUtility::getIndpEnv('REQUEST_URI')
//            ))
//            ->setIcon($this->iconFactory->getIcon($moveIcon, Icon::SIZE_SMALL))
//            ->render();
    }

    protected function collectContentElements(): array
    {

        // The returning array
        $contentElements = [];

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
//        foreach ($results as $index => $row) {
//            foreach ($row as $key => $value) {
//                $contentElements[$index][GeneralUtility::underscoredToLowerCamelCase($key)] = $value;
//            }
//        }
//
//        return $contentElements;
    }

    /**
     * Returns the language service
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
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
            'state' => self::state,
            'contentElements' => $this->contentElements
        ]);

        return $view->render();
    }

}
