<?php

namespace Zeroseven\Semantilizer\Services;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class FrontendSimulatorService
{
    public static function simulate(int $pageUid, int $language = null): TypoScriptFrontendController
    {

        // Get the translated page uid
        if($language) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');

            // Remove 'AND (hidden = 0)' from the query
            $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);

            // Create query
            $pageUid = (int)$queryBuilder
                ->select('uid')
                ->from('pages')
                ->where($queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq('l10n_parent', $queryBuilder->createNamedParameter($pageUid, \PDO::PARAM_INT)),
                    $queryBuilder->expr()->in('sys_language_uid', $queryBuilder->createNamedParameter($language, Connection::PARAM_INT))
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
}
