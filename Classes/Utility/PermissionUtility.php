<?php

declare(strict_types=1);

namespace Zeroseven\Semantilizer\Utility;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Type\Bitmask\Permission;

class PermissionUtility
{
    public static function getBackendUser(): ?BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'] ?? null;
    }

    public static function editContent(string $table): bool
    {
        return self::getBackendUser()->check('tables_modify', $table);
    }

    public static function showPage(array $row): bool
    {
        return !empty($row) && self::getBackendUser()->doesUserHaveAccess($row, Permission::PAGE_SHOW);
    }

    public static function visibleLanguages(Site $site): array
    {
        return $site->getAvailableLanguages(self::getBackendUser()) ?? [];
    }
}
