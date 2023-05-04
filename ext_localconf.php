<?php

defined('TYPO3') || die('✘');

call_user_func(static function () {
    TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig("@import 'EXT:z7_semantilizer/Configuration/PageTs/TceForm.tsconfig'");
});
