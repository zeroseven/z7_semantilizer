<?php

declare(strict_types=1);

return [
    'dependencies' => ['core', 'backend'],
    'imports' => [
        '@zeroseven/semantilizer/' => [
            'path' => 'EXT:z7_semantilizer/Resources/Public/JavaScript/Backend/',
            'exclude' => [
                'EXT:z7_semantilizer/Resources/Public/JavaScript/Backend/TYPO3/',
            ],
        ],
    ],
];
