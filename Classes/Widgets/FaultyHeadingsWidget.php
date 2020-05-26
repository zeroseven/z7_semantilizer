<?php
declare(strict_types=1);

namespace Zeroseven\Semantilizer\Widgets;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Dashboard\Widgets\ButtonProviderInterface;
use TYPO3\CMS\Dashboard\Widgets\ChartDataProviderInterface;
use TYPO3\CMS\Dashboard\Widgets\ListDataProviderInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;
use Zeroseven\Semantilizer\FixedTitle\FixedTitleInterface;
use Zeroseven\Semantilizer\Services\PageInfoService;
use Zeroseven\Semantilizer\Services\TsConfigService;
use Zeroseven\Semantilizer\Utilities\PageData;
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

    protected function getPages(): array
    {
        // Get instance of the query builder
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');

        // Get results from the database
        $results = $queryBuilder->select('pid', 'header', 'header_type', 'cType')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->gt('header_type', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)),
                $queryBuilder->expr()->lt('header_layout', $queryBuilder->createNamedParameter(100, \PDO::PARAM_INT)),
                $queryBuilder->expr()->neq('header', $queryBuilder->createNamedParameter('')),
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT))
            )
            ->orderBy('pid')
            ->addOrderBy('sorting')
            ->execute()
            ->fetchAll() ?: [];

        // Group content elements into pages
        $pages = [];
        foreach ($results as $i => $row) {
            foreach ($row as $key => $value) {
                $pages[$row['pid']][$i][GeneralUtility::underscoredToLowerCamelCase($key)] = $value;
            }
        }

        // Fixed title hook
        if ($fixedPageTitleHooks = $GLOBALS['TYPO3_CONF_VARS']['EXT']['z7_semantilizer']['fixedPageTitle'] ?? null) {
            ksort($fixedPageTitleHooks);
        }

        // Clean up pages by tsConfig and prepend fix titles
        foreach ($pages as $uid => $contentElements) {

            // Get tsConfig of each page
            $tsConfig = TsConfigService::getTsConfig((int)$uid);

            // Remove disabled pages
            if (($disableOnPages = $tsConfig['disableOnPages']) && in_array((int)$uid, GeneralUtility::intExplode(',', $disableOnPages), true)) {
                unset($pages[$uid]);
            }

            // Remove ignored CTypes
            if ($ignoreCTypes = GeneralUtility::trimExplode(',', $tsConfig['ignoreCTypes'] ?? null)) {
                foreach ($contentElements as $i => $contentElement) {
                    if (in_array($contentElement['ctype'], $ignoreCTypes, true)) {
                        unset($pages[$uid][$i]);
                    }
                }
            }

            // Remove empty pages
            if (count($contentElements) === 0) {
                unset($pages[$uid]);
            }

            // Set fixed Title
            if ($fixedPageTitleHooks) {
                $fixedTitle = null;

                foreach ($fixedPageTitleHooks as $className) {
                    if (empty($fixedTitle) && class_exists($className) && is_subclass_of($className, FixedTitleInterface::class)) {

                        $params = [
                            'uid' => (int)$uid,
                            'sys_language_uid' => 0,
                            'row' => PageInfoService::getPageInfo((int)$uid),
                            'tsConfig' => $tsConfig
                        ];

                        if ($fixedTitle = GeneralUtility::callUserFunction($className . '->get', $params)) {
                            $pages[$uid] = [['header' => $fixedTitle, 'headerType' => 1]] + $pages[$uid];
                        }
                    }
                }
            }
        }

        return $pages;
    }

    public function renderWidgetContent(): string
    {

        var_dump(PageData::makeInstance(1));
        $this->view->setTemplatePathAndFilename('EXT:z7_semantilizer/Resources/Private/Templates/Widget/FaultyHeadings.html');

        foreach ($this->getPages() ?? [] as $uid => $contentElements) {
            if ($status = GeneralUtility::makeInstance(ValidationUtility::class, $contentElements)->getStrongestLevel()) {
                if ($status > 0) {
                    var_dump($uid);
                }
            }
        }

        $this->view->assignMultiple([
            'options' => $this->options,
            'configuration' => $this->configuration,
        ]);

        return $this->view->render();
    }
}
