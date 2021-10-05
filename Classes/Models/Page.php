<?php

namespace Zeroseven\Semantilizer\Models;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Zeroseven\Semantilizer\Services\PermissionService;

class Page extends AbstractDataModel
{

    /** @var array */
    public const REQUIRED_FIELDS = [
        'uid',
        'title',
        'doktype',
        'sys_language_uid',
        'l10n_parent'
    ];

    /** @var array */
    public const IGNORED_DOKTYPES = [
        PageRepository::DOKTYPE_LINK,
        PageRepository::DOKTYPE_SHORTCUT,
        PageRepository::DOKTYPE_BE_USER_SECTION,
        PageRepository::DOKTYPE_MOUNTPOINT,
        PageRepository::DOKTYPE_SPACER,
        PageRepository::DOKTYPE_SYSFOLDER,
        PageRepository::DOKTYPE_RECYCLER
    ];

    public static function makeInstance(int $uid = null, int $pageLocalisation = null): ?Page
    {

        /** @var int */
        $pageUid = (int)($uid ?: GeneralUtility::_GP('id'));

        if ($pageLocalisation) {
            $row = BackendUtility::getRecordLocalization('pages', $pageUid, $pageLocalisation)[0];
        } else {
            $row = BackendUtility::readPageAccess($pageUid, true) ?: [];
        }

        return PermissionService::showPage($row) ? GeneralUtility::makeInstance(__CLASS__, $row) : null;
    }

    public function getUid(): int
    {
        return (int)$this->data['uid'];
    }

    public function getTitle(): string
    {
        return $this->data['title'];
    }

    public function getDoktype(): int
    {
        return (int)$this->data['doktype'];
    }

    public function getSysLanguageUid(): int
    {
        return (int)$this->data['sys_language_uid'];
    }

    public function getL10nParent(): int
    {
        return (int)$this->data['l10n_parent'];
    }

    public function isIgnoredDoktype(): bool
    {
        return in_array($this->getDoktype(), self::IGNORED_DOKTYPES, true);
    }
}
