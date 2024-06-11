<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'The Semantilizer',
    'description' => 'Simplify your semantic heading structure.',
    'category' => 'fe',
    'author' => 'Raphael Thanner',
    'author_email' => 'r.thanner@zeroseven.de',
    'author_company' => 'zeroseven design studios GmbH',
    'state' => 'stable',
    'internal' => '',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 1,
    'version' => '4.1.1',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-12.4.99'
        ],
        'conflicts' => [
        ],
        'suggests' => [
            'fluid_styled_content' => ''
        ]
    ]
];
