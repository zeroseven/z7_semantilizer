<?php
defined('TYPO3_MODE') || die('Access denied.');

// Add styles to the backend
$GLOBALS['TBE_STYLES']['skins'][$_EXTKEY] = [
    'name' => $_EXTKEY,
    'stylesheetDirectories' => [
        'css' => 'EXT:' . $_EXTKEY . '/Resources/Public/Css/Backend/'
    ]
];
