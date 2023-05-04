<?php

defined('TYPO3') || die('✘');

// Include typoscript setup
call_user_func(static function () {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('z7_semantilizer', 'Configuration/TypoScript/', 'Semantic headlines');
});
