<?php

namespace Zeroseven\Semantilizer\Services;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PageInfoService
{

    public static function getPageInfo(int $uid = null): array
    {
        return BackendUtility::readPageAccess((int)($uid ?: GeneralUtility::_GP('id')), true) ?: [];
    }

    public static function key(string $key, int $uid)
    {
        $pageInfo = self::getPageInfo($uid);

        return $pageInfo[$key] ?? null;
    }
}
