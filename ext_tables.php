<?php
defined('TYPO3') or die();

call_user_func(static function () {
    // Hook to add config to PageRenderer in backend
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-preProcess'][] =
        \Zeroseven\Semantilizer\Hooks\PageRendererRenderPreProcess::class . '->addPageRendererConfiguration';
});

// Add styles to the backend
$GLOBALS['TBE_STYLES']['skins']['z7_semantilizer'] = [
    'name' => 'z7_semantilizer',
    'stylesheetDirectories' => [
        'css' => 'EXT:z7_semantilizer/Resources/Public/Css/Backend/'
    ]
];
