<?php

return [
    'frontend' => [
        'zeroseven/z7_semantilizer/request' => [
            'target' => \Zeroseven\Semantilizer\Middleware\Request::class,
            'after' => [
                'typo3/cms-frontend/tsfe'
            ]
        ]
    ]
];
