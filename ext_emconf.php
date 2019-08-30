<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'The semantilizer',
    'description' => 'Gives more semantic control for the headlines of the content elements.',
    'category' => 'fe',
    'author' => 'Raphael Thanner',
    'author_email' => 'r.thanner@zeroseven.de',
    'state' => 'stable',
    'internal' => '',
    'uploadfolder' => false,
    'createDirs' => '',
    'clearCacheOnLoad' => 1,
    'version' => '0.3.0',
    'constraints' => [
        'depends' => [
            'typo3' => '9.5.0-9.5.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
            'fluid_styled_content' => ''
        ],
    ],
];
