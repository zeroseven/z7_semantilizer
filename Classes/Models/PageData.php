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
        'doktype',
    ];

    /** @var array */
    protected $ignoreDoktypes = [
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

    public static function makeInstance($uid = null): object
    {
        $row = BackendUtility::readPageAccess((int)($uid ?: GeneralUtility::_GP('id')), true) ?: [];

        return GeneralUtility::makeInstance(__CLASS__, $row);
    }

    public function getUid(): int
    {
        return (int)$this->data['uid'];
    }

    public function getDoktype(): int
    {
        return (int)$this->data['uid'];
    }

    public function isIgnoredDoktype(): bool
    {
        return in_array($this->getDoktype(), $this->ignoreDoktypes, true);
    }

}
