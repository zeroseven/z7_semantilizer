<?php
defined('TYPO3_MODE') || die();

call_user_func(function(string $extKey) {

    // Include typoscript setup
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($extKey, 'Configuration/TypoScript/', 'Semantic headlines');

}, 'z7_semantilizer');
