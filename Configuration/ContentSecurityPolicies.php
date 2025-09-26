<?php
declare(strict_types=1);

use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Directive;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Mutation;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationCollection;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationMode;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Scope;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\UriValue;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Type\Map;
use TYPO3\CMS\Core\Utility\GeneralUtility;

return Map::fromEntries([
    Scope::backend(),
    new MutationCollection(
        ...array_map(function (Site $site) {
            return new Mutation(
                MutationMode::Extend,
                Directive::ConnectSrc,
                new UriValue(rtrim((string)$site->getBase(), '/')),
            );
        }, GeneralUtility::makeInstance(SiteFinder::class)?->getAllSites() ?? [])
    )
]);
