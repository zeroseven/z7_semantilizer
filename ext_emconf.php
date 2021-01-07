<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'The semantilizer',
    'description' => 'Gives more semantic control for the headlines of the content elements.',
    'category' => 'fe',
    'author' => 'zeroseven design studios GmbH',
    'author_email' => 'typo3@zeroseven.de',
    'state' => 'stable',
    'internal' => '',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 1,
    'version' => '3.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '11.0.0-11.5.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
            'fluid_styled_content' => '',
            'dashboard' => ''
        ],
    ],
];
