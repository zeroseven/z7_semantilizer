<?php

declare(strict_types=1);

namespace Zeroseven\SemantilizerDev\Command;

use Doctrine\DBAL\ParameterType;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Configuration\Exception\SiteConfigurationWriteException;
use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Demo seed for DDEV — values match Build/seed-config.yaml.
 */
final class BootstrapDemoCommand extends Command
{
    private const MARKER = 'z7_semantilizer_dev';
    private const DEMO_PAGE_SLUG = 'semantilizer-demo';
    private const DEFAULT_SITE_BASE = 'https://z7-semantilizer.ddev.site';
    private const DEMO_CTYPE = 'text';

    /** @var list<string> */
    private const DEMO_STATIC_INCLUDES = [
        'EXT:fluid_styled_content/Configuration/TypoScript/',
        'EXT:fluid_styled_content/Configuration/TypoScript/Styling/',
        'EXT:z7_semantilizer/Configuration/TypoScript/',
    ];

    public function __construct(
        private readonly SiteFinder $siteFinder,
        private readonly ConnectionPool $connectionPool,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Create Semantilizer demo page with semantic headline examples.');
        $this->addOption(
            'force',
            'f',
            InputOption::VALUE_NONE,
            'Replace existing demo content elements on the demo page',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $sites = $this->siteFinder->getAllSites(false);
        if ($sites === []) {
            $this->ensureSiteConfigurationExists($io);
            $sites = $this->siteFinder->getAllSites(false);
        }
        if ($sites === []) {
            $io->error('No site configuration found. Run `ddev init` first.');

            return Command::FAILURE;
        }

        $site = array_values($sites)[0];
        $rootPageId = $site->getRootPageId();

        Bootstrap::initializeBackendAuthentication();

        $this->consolidateRootTypoScript($rootPageId, $io);

        $demoPid = $this->getOrCreatePage(
            $rootPageId,
            self::DEMO_PAGE_SLUG,
            'Semantilizer demo',
            $io,
        );
        if ($demoPid === null) {
            return Command::FAILURE;
        }

        if (!$this->seedDemoContent($demoPid, (bool)$input->getOption('force'), $io)) {
            return Command::FAILURE;
        }

        GeneralUtility::makeInstance(CacheManager::class)->flushCaches();

        $base = rtrim((string)$site->getBase(), '/');
        $io->success('Done. Open: ' . $base . '/' . ltrim(self::DEMO_PAGE_SLUG, '/'));

        return Command::SUCCESS;
    }

    private function ensureSiteConfigurationExists(SymfonyStyle $io): void
    {
        $rootPageId = $this->findFirstSiteRootPageId();
        if ($rootPageId === null) {
            return;
        }

        $siteConfiguration = GeneralUtility::makeInstance(SiteConfiguration::class);
        try {
            $siteConfiguration->createNewBasicSite('main', $rootPageId, self::DEFAULT_SITE_BASE);
            $io->note('Created missing site configuration at config/sites/main/ (root page uid ' . $rootPageId . ').');
        } catch (SiteConfigurationWriteException $e) {
            $io->warning('Could not auto-create site configuration: ' . $e->getMessage());
        }
    }

    private function findFirstSiteRootPageId(): ?int
    {
        $q = $this->connectionPool->getQueryBuilderForTable('pages');
        $uid = $q->select('uid')
            ->from('pages')
            ->where(
                $q->expr()->eq('deleted', $q->createNamedParameter(0, ParameterType::INTEGER)),
                $q->expr()->eq('is_siteroot', $q->createNamedParameter(1, ParameterType::INTEGER)),
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchOne();

        return $uid !== false ? (int)$uid : null;
    }

    private function consolidateRootTypoScript(int $rootPageId, SymfonyStyle $io): void
    {
        if ($this->countRootTemplatesOnRootPage($rootPageId) === 0) {
            $this->createRootTemplate($rootPageId, $io);

            return;
        }

        $primary = $this->fetchPrimaryRootSysTemplateRow($rootPageId);
        if ($primary === null) {
            return;
        }

        $includes = $this->mergeStaticIncludes((string)$primary['include_static_file'], self::DEMO_STATIC_INCLUDES);
        $config = (string)$primary['config'];
        if (!$this->configRendersContentElements($config)) {
            $config = $this->getMinimalFrontendPageTypoScript();
        }

        $dh = $this->newDataHandler();
        $dh->start([
            'sys_template' => [
                (string)$primary['uid'] => [
                    'title' => 'Semantilizer demo (FSC + Semantilizer)',
                    'description' => self::MARKER,
                    'include_static_file' => $includes,
                    'config' => $config,
                ],
            ],
        ], []);
        $dh->process_datamap();
        $this->logDhErrors($dh, $io);
        $io->writeln('<info>Updated root TypoScript template uid ' . $primary['uid'] . ' (FSC + Semantilizer).</info>');

        $this->removeDuplicateBootstrapRootTemplates($rootPageId, $primary['uid'], $io);
    }

    /**
     * @param list<string> $requiredIncludes
     */
    private function mergeStaticIncludes(string $currentIncludes, array $requiredIncludes): string
    {
        $parts = array_values(array_unique(array_filter(array_map('trim', [
            ...explode(',', $currentIncludes),
            ...$requiredIncludes,
        ]))));

        return implode(',', $parts);
    }

    private function configRendersContentElements(string $config): bool
    {
        if (!$this->stringDefinesPageObject($config)) {
            return false;
        }

        return (bool)preg_match('/\b10\s*=\s*CONTENT\b/i', $config)
            || (bool)preg_match('/table\s*=\s*tt_content/i', $config);
    }

    private function removeDuplicateBootstrapRootTemplates(int $rootPageId, int $keepUid, SymfonyStyle $io): void
    {
        $q = $this->connectionPool->getQueryBuilderForTable('sys_template');
        $q->getRestrictions()->removeAll();
        $uids = $q->select('uid')
            ->from('sys_template')
            ->where(
                $q->expr()->eq('pid', $q->createNamedParameter($rootPageId, ParameterType::INTEGER)),
                $q->expr()->eq('deleted', $q->createNamedParameter(0, ParameterType::INTEGER)),
                $q->expr()->eq('root', $q->createNamedParameter(1, ParameterType::INTEGER)),
                $q->expr()->eq('description', $q->createNamedParameter(self::MARKER)),
                $q->expr()->neq('uid', $q->createNamedParameter($keepUid, ParameterType::INTEGER)),
            )
            ->executeQuery()
            ->fetchFirstColumn();

        if ($uids === []) {
            return;
        }

        $cmd = [];
        foreach ($uids as $uid) {
            $cmd['sys_template'][(string)$uid] = ['delete' => 1];
        }
        $dh = $this->newDataHandler();
        $dh->start([], $cmd);
        $dh->process_cmdmap();
        $this->logDhErrors($dh, $io);
        $io->writeln('<info>Removed duplicate bootstrap TypoScript template(s): ' . implode(', ', $uids) . '.</info>');
    }

    private function getMinimalFrontendPageTypoScript(): string
    {
        return <<<'EOT'
page = PAGE
page {
    typeNum = 0
    10 = CONTENT
    10 {
        table = tt_content
        select {
            orderBy = sorting
            where = {#colPos}=0
        }
    }
}
EOT;
    }

    private function createRootTemplate(int $rootPageId, SymfonyStyle $io): void
    {
        $dh = $this->newDataHandler();
        $newId = StringUtility::getUniqueId('NEW');
        $dh->start([
            'sys_template' => [
                $newId => [
                    'pid' => $rootPageId,
                    'title' => 'Semantilizer demo (FSC + Semantilizer)',
                    'description' => self::MARKER,
                    'root' => 1,
                    'sorting' => 0,
                    'clear' => 3,
                    'include_static_file' => implode(',', self::DEMO_STATIC_INCLUDES),
                    'constants' => '',
                    'config' => $this->getMinimalFrontendPageTypoScript(),
                ],
            ],
        ], []);
        $dh->process_datamap();
        $this->logDhErrors($dh, $io);
        $io->writeln('<info>Created root TypoScript template.</info>');
    }

    private function countRootTemplatesOnRootPage(int $rootPageId): int
    {
        $q = $this->connectionPool->getQueryBuilderForTable('sys_template');
        $q->getRestrictions()->removeAll();

        return (int)$q->count('uid')
            ->from('sys_template')
            ->where(
                $q->expr()->eq('pid', $q->createNamedParameter($rootPageId, ParameterType::INTEGER)),
                $q->expr()->eq('deleted', $q->createNamedParameter(0, ParameterType::INTEGER)),
                $q->expr()->eq('root', $q->createNamedParameter(1, ParameterType::INTEGER)),
            )
            ->executeQuery()
            ->fetchOne();
    }

    /**
     * @return array{uid: int, config: string, include_static_file: string}|null
     */
    private function fetchPrimaryRootSysTemplateRow(int $rootPageId): ?array
    {
        $q = $this->connectionPool->getQueryBuilderForTable('sys_template');
        $q->getRestrictions()->removeAll();
        $row = $q->select('uid', 'config', 'include_static_file')
            ->from('sys_template')
            ->where(
                $q->expr()->eq('pid', $q->createNamedParameter($rootPageId, ParameterType::INTEGER)),
                $q->expr()->eq('deleted', $q->createNamedParameter(0, ParameterType::INTEGER)),
                $q->expr()->eq('root', $q->createNamedParameter(1, ParameterType::INTEGER)),
            )
            ->orderBy('sorting', 'DESC')
            ->addOrderBy('uid', 'DESC')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        if ($row === false) {
            return null;
        }

        return [
            'uid' => (int)$row['uid'],
            'config' => (string)$row['config'],
            'include_static_file' => (string)$row['include_static_file'],
        ];
    }

    private function stringDefinesPageObject(string $config): bool
    {
        return (bool)preg_match('/^\s*page\s*=\s*PAGE\b/mi', $config);
    }

    private function getOrCreatePage(
        int $rootPageId,
        string $slug,
        string $title,
        SymfonyStyle $io,
    ): ?int {
        $existing = $this->findPageUidBySlug($slug);
        if ($existing !== null) {
            return $existing;
        }

        $dh = $this->newDataHandler();
        $newId = StringUtility::getUniqueId('NEW');
        $dh->start([
            'pages' => [
                $newId => [
                    'pid' => $rootPageId,
                    'title' => $title,
                    'slug' => $this->canonicalPageSlugForDatabase($slug),
                    'doktype' => 1,
                    'hidden' => 0,
                    'description' => self::MARKER,
                ],
            ],
        ], []);
        $dh->process_datamap();
        $this->logDhErrors($dh, $io);
        $uid = (int)($dh->substNEWwithIDs[$newId] ?? 0);
        if ($uid === 0) {
            $io->error('Could not create page: ' . $title);

            return null;
        }
        $io->writeln('<info>Created page "' . $title . '" (uid ' . $uid . ').</info>');

        return $uid;
    }

    private function findPageUidBySlug(string $slug): ?int
    {
        $candidates = array_values(array_unique(array_filter([
            $slug,
            trim($slug),
            ltrim(trim($slug), '/'),
            $this->canonicalPageSlugForDatabase($slug),
        ])));
        $q = $this->connectionPool->getQueryBuilderForTable('pages');
        $or = [];
        foreach ($candidates as $candidate) {
            $or[] = $q->expr()->eq('slug', $q->createNamedParameter($candidate));
        }
        $uid = $q->select('uid')
            ->from('pages')
            ->where(
                $q->expr()->or(...$or),
                $q->expr()->eq('deleted', $q->createNamedParameter(0, ParameterType::INTEGER)),
            )
            ->orderBy('uid', 'ASC')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchOne();

        return $uid !== false ? (int)$uid : null;
    }

    private function canonicalPageSlugForDatabase(string $slug): string
    {
        $t = trim($slug);

        return $t === '' ? $t : '/' . ltrim($t, '/');
    }

    private function seedDemoContent(int $pageId, bool $force, SymfonyStyle $io): bool
    {
        $existingCount = $this->countDemoContentElements($pageId);
        if ($existingCount > 0 && !$force) {
            $io->note(
                'Demo page already has ' . $existingCount . ' text element(s). Use --force to replace.',
            );

            return true;
        }

        if ($force && $existingCount > 0) {
            $this->deleteDemoContentOnPage($pageId, $io);
        }

        $elements = [
            [
                'header' => 'Chapter: Product overview',
                'header_type' => 1,
                'bodytext' => '<p>Top-level semantic headline (h1 via header_type).</p>',
            ],
            [
                'header' => 'Section: Features',
                'header_type' => 2,
                'bodytext' => '<p>Second-level headline (h2).</p>',
            ],
            [
                'header' => 'Subsection: Details',
                'header_type' => 3,
                'bodytext' => '<p>Third-level headline (h3) — check hierarchy in the Page module.</p>',
            ],
        ];

        $data = ['tt_content' => []];
        foreach ($elements as $index => $element) {
            $newId = StringUtility::getUniqueId('NEW');
            $data['tt_content'][$newId] = [
                'pid' => $pageId,
                'CType' => self::DEMO_CTYPE,
                'colPos' => 0,
                'sorting' => ($index + 1) * 10,
                'header' => $element['header'],
                'header_type' => $element['header_type'],
                'header_layout' => 2,
                'bodytext' => $element['bodytext'],
            ];
        }

        $dh = $this->newDataHandler();
        $dh->start($data, []);
        $dh->process_datamap();
        $this->logDhErrors($dh, $io);
        $io->writeln('<info>Created ' . count($elements) . ' demo text elements with semantic headlines.</info>');

        return true;
    }

    private function countDemoContentElements(int $pageId): int
    {
        $q = $this->connectionPool->getQueryBuilderForTable('tt_content');

        return (int)$q->count('uid')
            ->from('tt_content')
            ->where(
                $q->expr()->eq('pid', $q->createNamedParameter($pageId, ParameterType::INTEGER)),
                $q->expr()->eq('deleted', $q->createNamedParameter(0, ParameterType::INTEGER)),
                $q->expr()->eq('CType', $q->createNamedParameter(self::DEMO_CTYPE)),
            )
            ->executeQuery()
            ->fetchOne();
    }

    private function deleteDemoContentOnPage(int $pageId, SymfonyStyle $io): void
    {
        $q = $this->connectionPool->getQueryBuilderForTable('tt_content');
        $uids = $q->select('uid')
            ->from('tt_content')
            ->where(
                $q->expr()->eq('pid', $q->createNamedParameter($pageId, ParameterType::INTEGER)),
                $q->expr()->eq('CType', $q->createNamedParameter(self::DEMO_CTYPE)),
            )
            ->executeQuery()
            ->fetchFirstColumn();

        if ($uids === []) {
            return;
        }

        $cmd = [];
        foreach ($uids as $uid) {
            $cmd['tt_content'][(string)$uid] = ['delete' => 1];
        }
        $dh = $this->newDataHandler();
        $dh->start([], $cmd);
        $dh->process_cmdmap();
        $this->logDhErrors($dh, $io);
        $io->writeln('<info>Removed existing demo content element(s) on page uid ' . $pageId . '.</info>');
    }

    private function newDataHandler(): DataHandler
    {
        $dh = GeneralUtility::makeInstance(DataHandler::class);
        $dh->admin = true;
        $dh->bypassWorkspaceRestrictions = true;
        $dh->dontProcessTransformations = true;

        return $dh;
    }

    private function logDhErrors(DataHandler $dh, SymfonyStyle $io): void
    {
        foreach ($dh->errorLog as $msg) {
            $io->warning((string)$msg);
        }
    }
}
