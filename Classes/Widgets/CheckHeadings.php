<?php
declare(strict_types=1);

namespace Zeroseven\Semantilizer\Widgets;

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Database\Connection;
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

class CheckHeadings implements WidgetInterface
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

    /** @var string */
    private $moduleLink;

    public function __construct(WidgetConfigurationInterface $configuration, ListDataProviderInterface $dataProvider = null, StandaloneView $view, $buttonProvider = null, array $options = [])
    {
        $this->configuration = $configuration;
        $this->view = $view;
        $this->buttonProvider = $buttonProvider;
        $this->options = $options;
        $this->dataProvider = $dataProvider;
        $this->moduleLink = GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute('dashboard');
    }

    protected function validatePage(int $uid, int $language = null): ?array
    {

        // Get the page or stop here
        if (!$page = PageData::makeInstance($uid, $language)) {
            return null;
        }

        // Load the tsConfig
        $tsConfig = TsConfigService::getTsConfig($page->getL10nParent() ?: $page->getUid());

        // Check if the Semantilizer is disabled on given page
        if (($disableOnPages = $tsConfig['disableOnPages'] ?? null) && in_array($page->getL10nParent() ?: $page->getUid(), GeneralUtility::intExplode(',', $disableOnPages), true)) {
            return null;
        }

        // Get content elements
        $collectContentUtility = GeneralUtility::makeInstance(CollectContentUtility::class, $page, $tsConfig);
        $contentCollection = $collectContentUtility->getCollection();

        // Stop, if no content elements found
        if ($contentCollection->count() === 0) {
            return null;
        }

        // Start validation
        $validationUtility = GeneralUtility::makeInstance(ValidationUtility::class, $contentCollection, $this->moduleLink);

        // Set error state
        foreach ($validationUtility->getAffectedContentElements() as $affected) {
            $contentCollection->overrideElement($affected);
        }

        // Return data
        if (($status = $validationUtility->getStrongestLevel()) > FlashMessage::OK) {
            return [
                'page' => $page,
                'status' => $status,
                'notifications' => $validationUtility->getNotifications()
            ];
        }

        return null;
    }

    protected function findErrors(): array
    {

        // Return list of affected pages
        $affectedPages = [];

        // Get instance of the query builder
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');

        // Get results from the database
        $pages = $queryBuilder->select('uid', 'l10n_parent', 'sys_language_uid')
            ->from('pages')
            ->where($queryBuilder->expr()->notIn('doktype', $queryBuilder->createNamedParameter(PageData::IGNORED_DOKTYPES, Connection::PARAM_INT_ARRAY)))
            ->orderBy('SYS_LASTCHANGED')
            ->execute()
            ->fetchAll() ?: [];

        // Create pages
        foreach ($pages as $row) {

            // Get the uid of the page
            if (($page = $this->validatePage((int)$row['uid'])) && count($affectedPages) < 5) {
                $affectedPages[] = $page;
            }
        }

        return $affectedPages;
    }

    public function renderWidgetContent(): string
    {

        $this->view->setTemplatePathAndFilename('EXT:z7_semantilizer/Resources/Private/Templates/Widget/CheckHeadings.html');

        $this->view->assignMultiple([
            'errors' => $this->findErrors(),
            'options' => $this->options,
            'configuration' => $this->configuration,
        ]);

        return $this->view->render();
    }
}
