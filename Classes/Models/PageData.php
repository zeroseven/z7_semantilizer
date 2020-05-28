<?php

namespace Zeroseven\Semantilizer\Models;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Page\PageRepository;

class PageData
{

    /** @var array */
    protected $data = [];

    /** @var array */
    protected $requiredFields = [
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

    public function __construct(array $data)
    {
        foreach ($this->requiredFields as $name) {
            if(!isset($data[$name])) {
                throw new \Exception(sprintf('Key "%s" is missing in data array', $name));
            }
        }

        $this->data = $data;
    }

    public static function makeInstance(int $uid = null, int $pageLocalisation = null): ?PageData
    {

        $pageUid = (int)($uid ?: GeneralUtility::_GP('id'));

        if ($pageLocalisation) {
            $localisation = BackendUtility::getRecordLocalization('pages', $pageUid, $pageLocalisation)[0];
            $row = BackendUtility::readPageAccess($localisation['uid'], true) ?: [];
        } else {
            $row = BackendUtility::readPageAccess($pageUid, true) ?: [];
        }


        return empty($row) ? null : GeneralUtility::makeInstance(__CLASS__, $row);
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
        return (int)$this->data['uid'];
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
