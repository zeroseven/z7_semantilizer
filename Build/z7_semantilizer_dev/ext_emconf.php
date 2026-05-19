<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'z7_semantilizer development bootstrap',
    'description' => 'Local demo seed for DDEV only — not for TER.',
    'category' => 'development',
    'author' => 'zeroseven design studios GmbH',
    'author_email' => 'typo3@zeroseven.de',
    'state' => 'excludeFromUpdates',
    'version' => '0.0.1',
    'constraints' => [
        'depends' => [
            'typo3' => '13.1.0-14.4.99',
        ],
    ],
];
