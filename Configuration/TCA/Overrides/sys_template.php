<?php
defined('TYPO3_MODE') || die();

call_user_func(function(string $_EXTKEY) {

    // Include typoscript setup
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript/', 'Semantic headlines');

}, 'z7_semantilizer');
