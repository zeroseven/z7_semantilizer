<?php

defined('TYPO3') || die('✘');

call_user_func(static function () {
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
                'items' => [
                    [
                        'label' => 'LLL:EXT:z7_semantilizer/Resources/Private/Language/locallang_db.xlf:tt_content.header_type.semantic',
                        'value' => '--div--'
                    ],
                    [
                        'label' => 'LLL:EXT:z7_semantilizer/Resources/Private/Language/locallang_db.xlf:tt_content.header_type.1',
                        'value' => 1
                    ],
                    [
                        'label' => 'LLL:EXT:z7_semantilizer/Resources/Private/Language/locallang_db.xlf:tt_content.header_type.2',
                        'value' => 2
                    ],
                    [
                        'label' => 'LLL:EXT:z7_semantilizer/Resources/Private/Language/locallang_db.xlf:tt_content.header_type.3',
                        'value' => 3
                    ],
                    [
                        'label' => 'LLL:EXT:z7_semantilizer/Resources/Private/Language/locallang_db.xlf:tt_content.header_type.4',
                        'value' => 4
                    ],
                    [
                        'label' => 'LLL:EXT:z7_semantilizer/Resources/Private/Language/locallang_db.xlf:tt_content.header_type.5',
                        'value' => 5
                    ],
                    [
                        'label' => 'LLL:EXT:z7_semantilizer/Resources/Private/Language/locallang_db.xlf:tt_content.header_type.6',
                        'value' => 6
                    ],
                    [
                        'label' => 'LLL:EXT:z7_semantilizer/Resources/Private/Language/locallang_db.xlf:tt_content.header_type.no_semantic',
                        'value' => '--div--'
                    ],
                    [
                        'label' => 'LLL:EXT:z7_semantilizer/Resources/Private/Language/locallang_db.xlf:tt_content.header_type.0',
                        'value' => 0
                    ]
                ]
            ]
        ]
    ]);

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette('tt_content', 'header', 'header_type', 'after:header_layout');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette('tt_content', 'headers', 'header_type', 'after:header_layout');
});
