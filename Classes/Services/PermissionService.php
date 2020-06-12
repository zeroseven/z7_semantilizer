<?php

namespace Zeroseven\Semantilizer\Services;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Type\Bitmask\Permission;

class PermissionService
{

    public static function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    public static function editContent(): bool
    {
        return self::getBackendUser()->check('tables_modify', 'tt_content');
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
