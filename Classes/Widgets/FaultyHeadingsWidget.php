<?php
declare(strict_types=1);

namespace Zeroseven\Semantilizer\Widgets;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Dashboard\Widgets\ButtonProviderInterface;
use TYPO3\CMS\Dashboard\Widgets\ChartDataProviderInterface;
use TYPO3\CMS\Dashboard\Widgets\ListDataProviderInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;
use Zeroseven\Semantilizer\Services\BootstrapColorService;
use Zeroseven\Semantilizer\Utilities\CollectContentUtility;
use Zeroseven\Semantilizer\Services\TsConfigService;
use Zeroseven\Semantilizer\Models\PageData;
use Zeroseven\Semantilizer\Utilities\ValidationUtility;

class FaultyHeadingsWidget implements WidgetInterface
{

    /** @var WidgetConfigurationInterface */
    private $configuration;

    /** @var ChartDataProviderInterface */
    private $dataProvider;

    /** @var StandaloneView */
    private $view;

    /** @var ButtonProviderInterface|null */
    private $buttonProvider;

    /** @var array */
    private $options;

    public function __construct(WidgetConfigurationInterface $configuration, ListDataProviderInterface $dataProvider = null, StandaloneView $view, $buttonProvider = null, array $options = [])
    {
        $this->configuration = $configuration;
        $this->view = $view;
        $this->options = $options;
        $this->buttonProvider = $buttonProvider;
        $this->dataProvider = $dataProvider;
    }

    protected function validatePage(int $pageId): ?array
    {
        // Load the tsConfig
        $tsConfig = TsConfigService::getTsConfig($pageId);

        // Semantilizer is disabled on given page
        if (($disableOnPages = $tsConfig['disableOnPages']) && in_array($pageId, GeneralUtility::intExplode(',', $disableOnPages), true)) {
            return null;
        }

        // Page object
        $page = PageData::makeInstance($pageId);

        // Skip some doktypes
        if ($page->isIgnoredDoktype()) {
            return null;
        }

        // Get content elements
        $collectContentUtility = GeneralUtility::makeInstance(CollectContentUtility::class, $pageId, 0, $tsConfig, $page);
        $contentCollection = $collectContentUtility->getCollection();

        // Validate
        $validationUtility = GeneralUtility::makeInstance(ValidationUtility::class, $contentCollection);

        // Set error state
        foreach ($validationUtility->getAffectedContentElements() as $affected) {
            $this->contentCollection->overrideElement($affected);
        }

        if(($status = $validationUtility->getStrongestLevel()) > FlashMessage::OK) {
            return [
                'page' => $page,
                'status' => $status,
                'color' => BootstrapColorService::getColorByFlashMessageState($status)
            ];
        }

        return null;
    }

    protected function getPages(): array
    {
        // Get instance of the query builder
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');

        // Get results from the database
        $pageUids = $queryBuilder->select('pid')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->gt('header_type', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)),
                $queryBuilder->expr()->lt('header_layout', $queryBuilder->createNamedParameter(100, \PDO::PARAM_INT)),
                $queryBuilder->expr()->neq('header', $queryBuilder->createNamedParameter('')),
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT))
            )
            ->orderBy('tstamp')
            ->addOrderBy('sorting')
            ->groupBy('pid')
            ->execute()
            ->fetchAll() ?: [];

        // Create pages
        $pages = [];
        foreach ($pageUids as $row) {

            // Get the uid of the page
            $uid = (int)$row['pid'];

            if($page = $this->validatePage($uid)) {
                $pages[] = $page;
            }
        }

        return $pages;
    }

    public function renderWidgetContent(): string
    {

        $this->view->setTemplatePathAndFilename('EXT:z7_semantilizer/Resources/Private/Templates/Widget/FaultyHeadings.html');

        var_dump($this->getPages());

        $this->view->assignMultiple([
            'options' => $this->options,
            'configuration' => $this->configuration,
        ]);

        return $this->view->render();
    }
}
