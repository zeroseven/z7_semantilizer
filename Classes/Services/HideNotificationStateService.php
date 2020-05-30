<?php

namespace Zeroseven\Semantilizer\Services;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class HideNotificationStateService
{

    private const TABLE = 'be_users';

    private const FIELD = 'semantilizer_hide_notifications';

    protected static function getBackendUserId(): int
    {
        return (int)PermissionService::getBackendUser()->user['uid'];
    }

    public static function getState(): bool
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE);

        return $queryBuilder
            ->select(self::FIELD)
            ->from(self::TABLE)
            ->where($queryBuilder->expr()->eq('uid', self::getBackendUserId()))
            ->setMaxResults(1)
            ->execute()
            ->fetchColumn(0) ?: false;
    }

    public static function setState(bool $value): bool
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE);

        $queryBuilder
            ->update(self::TABLE)
            ->where($queryBuilder->expr()->eq('uid', self::getBackendUserId()))
            ->set(self::FIELD, (int)$value)
            ->execute();

        return $value;
    }

    public static function enable(): bool
    {
        return self::setState(true);
    }

    public static function disable(): bool
    {
        return self::setState(false);
    }

}
