<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(function (string $extKey) {

    // Add page ts configuration
    TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig("@import 'EXT:$extKey/Configuration/PageTs/TceForm.tsconfig'");

}, 'z7_semantilizer');

// Register hook
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/db_layout.php']['drawHeaderHook']['z7_semantilizer'] = \Zeroseven\Semantilizer\Hooks\DrawHeaderHook::class . '->render';
