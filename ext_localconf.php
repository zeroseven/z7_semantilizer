<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(function (string $_EXTKEY) {

    // Add page ts configuration
    TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig("@import 'EXT:$_EXTKEY/Configuration/PageTs/TceForm.tsconfig'");

}, $_EXTKEY);

// Register hook
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/db_layout.php']['drawHeaderHook'][$_EXTKEY] = \Zeroseven\Semantilizer\Hooks\DrawHeaderHook::class . '->render';
