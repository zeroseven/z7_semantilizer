<?php
defined('TYPO3_MODE') || die();

call_user_func(static function () {

    // Add fields to tt_content
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('tt_content', [
        'header_type' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:z7_semantilizer/Resources/Private/Language/locallang_db.xlf:tt_content.header_type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'default' => 2,
                'range' => [
                    'lower' => 0,
                    'upper' => 6,
                ],
                // Todo: Create something like the "hightesType" in the plugin typoscript setupt and create the list automatically
                'items' => [
                    ['LLL:EXT:z7_semantilizer/Resources/Private/Language/locallang_db.xlf:tt_content.header_type.1', 1],
                    ['LLL:EXT:z7_semantilizer/Resources/Private/Language/locallang_db.xlf:tt_content.header_type.2', 2],
                    ['LLL:EXT:z7_semantilizer/Resources/Private/Language/locallang_db.xlf:tt_content.header_type.3', 3],
                    ['LLL:EXT:z7_semantilizer/Resources/Private/Language/locallang_db.xlf:tt_content.header_type.0', 0]
                ]
            ]
        ]
    ]);

    // Add field to the palettes
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette('tt_content', 'header','header_type', 'after:header_layout');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette('tt_content', 'headers','header_type', 'after:header_layout');

});

// Change the label of the field "header_layout"
$GLOBALS['TCA']['tt_content']['columns']['header_layout']['label'] = 'LLL:EXT:z7_semantilizer/Resources/Private/Language/locallang_db.xlf:tt_content.header_layout';
