<?php

defined('TYPO3') || die('✘');

call_user_func(static function () {

    // Add page ts configuration
    TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig("@import 'EXT:z7_semantilizer/Configuration/PageTs/TceForm.tsconfig'");
});

// Register hook
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/db_layout.php']['drawHeaderHook']['z7_semantilizer'] = \Zeroseven\Semantilizer\Hooks\DrawHeaderHook::class . '->render';
