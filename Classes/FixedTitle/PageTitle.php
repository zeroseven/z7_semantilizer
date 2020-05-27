<?php

namespace Zeroseven\Semantilizer\FixedTitle;

use TYPO3\CMS\Backend\Utility\BackendUtility;

class PageTitle implements FixedTitleInterface
{
    protected const TABLE = 'pages';

    protected const FIELD = 'title';

    public function get(array $params): string
    {

        if ($params['sys_language_uid']) {
            $row = BackendUtility::getRecordLocalization(self::TABLE, $params['uid'], $params['sys_language_uid'])[0];
        } else {
            $row = BackendUtility::getRecord(self::TABLE, $params['uid'], self::FIELD);
        }

        return $row[self::FIELD] ?? '';
    }
}
