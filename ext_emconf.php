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
    'version' => '4.4.0',
    'constraints' => [
        'depends' => [
            'php' => '8.3.0-8.99.99',
            'typo3' => '13.1.0-14.4.99'
        ],
        'conflicts' => [
        ],
        'suggests' => [
            'fluid_styled_content' => ''
        ]
    ]
];
