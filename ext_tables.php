<?php
defined('TYPO3_MODE') || die('Access denied.');


call_user_func(function () {

    if (TYPO3_MODE === 'BE') {

        // Add JavaScript to the backend
        $pageRenderer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\PageRenderer::class);
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Z7Semantilizer/Backend/Semantilizer');

        // Add language translations to the backend
        $pageRenderer->addInlineLanguageLabelFile('EXT:z7_semantilizer/Resources/Private/Language/locallang.xlf');
    }


});

// Add styles to the backend
$GLOBALS['TBE_STYLES']['skins']['z7_semantilizer'] = [
    'name' => 'z7_semantilizer',
    'stylesheetDirectories' => [
        'css' => 'EXT:z7_semantilizer/Resources/Public/Css/Backend/'
    ]
];
