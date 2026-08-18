<?php

declare(strict_types=1);

use Zeroseven\Semantilizer\Controller\PreviewController;

return [
    'semantilizer_preview' => [
        'path' => '/semantilizer/preview',
        'target' => PreviewController::class . '::fetch',
        'methods' => ['GET'],
        'inheritAccessFromModule' => 'web_layout',
    ],
];
