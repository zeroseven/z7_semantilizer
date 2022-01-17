<?php

defined('TYPO3') || die('✘');

call_user_func(static function () {

    // Include typoscript setup
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('z7_semantilizer', 'Configuration/TypoScript/', 'Semantic headlines');
});
