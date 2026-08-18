<?php

return [
    'frontend' => [
        'zeroseven/z7_semantilizer/request' => [
            'target' => \Zeroseven\Semantilizer\Middleware\Request::class,
            'after' => [
                'typo3/cms-frontend/tsfe'
            ]
        ],
        'zeroseven/z7_semantilizer/user-ts-config' => [
            'target' => \Zeroseven\Semantilizer\Middleware\UserTsConfig::class,
            'before' => [
                'typo3/cms-frontend/site'
            ]
        ]
    ]
];
