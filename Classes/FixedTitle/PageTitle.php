<?php

namespace Zeroseven\Semantilizer\FixedTitle;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use Zeroseven\Semantilizer\Hooks\DrawHeaderHook;

class PageTitle implements FixedTitleInterface
{
    protected const TABLE = 'pages';

    protected const FIELD = 'title';

    public function get(array $params, DrawHeaderHook $parent): string
    {

        if ($params['sys_language_uid']) {
            $row = BackendUtility::getRecordLocalization(self::TABLE, (int)$params['uid'], (int)$params['sys_language_uid'])[0];
        } else {
            $row = BackendUtility::getRecord(self::TABLE, (int)$params['uid'], self::FIELD);
        }

        return $row[self::FIELD] ?? '';
    }
}
