<?php

namespace Zeroseven\Semantilizer\Services;

use TYPO3\CMS\Backend\Utility\BackendUtility;

class TsConfigService
{
    public static function getTsConfig(int $uid): array
    {
        $pagesTsConfig = BackendUtility::getPagesTSconfig($uid);

        return $pagesTsConfig['tx_semantilizer.'] ?? [];
    }

    public static function key(string $key, int $uid)
    {
        $tsConfig = self::getTsConfig($uid);

        return $tsConfig[$key] ?? null;
    }
}
