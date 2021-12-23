<?php

declare(strict_types=1);

namespace Zeroseven\Semantilizer\Utility;

use TYPO3\CMS\Backend\Utility\BackendUtility;

class TsConfigUtility
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
