<?php

namespace Zeroseven\Semantilizer\ViewHelpers;

use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use Zeroseven\Semantilizer\Services\PermissionService;

class FlagViewHelper extends AbstractViewHelper
{
    protected $escapeOutput = false;

    protected $escapeChildren = false;

    public function initializeArguments(): void
    {
        $this->registerArgument('sysLanguageUid', 'int', 'Id of language', true);
    }

    protected function getAllSiteLanguages(): array
    {
        $languages = [];
        foreach (GeneralUtility::makeInstance(SiteFinder::class)->getAllSites() ?? [] as $site) {
            foreach (PermissionService::visibleLanguages($site) as $language) {
                $languages[$language->getLanguageId()] = $language;
            }
        }

        return $languages;
    }

    protected function renderLanguageFlag(SiteLanguage $language): string
    {
        if ($language->getFlagIdentifier()) {
            return GeneralUtility::makeInstance(IconFactory::class)->getIcon(
                $language->getFlagIdentifier(),
                Icon::SIZE_SMALL
            )->render();
        }

        return sprintf('(%s)', $language->getTitle());
    }

    public function render(): string
    {
        $siteLanguages = $this->getAllSiteLanguages();
        $languageUid = (int)$this->arguments['sysLanguageUid'];

        return ($siteLanguage = $siteLanguages[$languageUid] ?? null) ? $this->renderLanguageFlag($siteLanguage) : '';
    }
}
