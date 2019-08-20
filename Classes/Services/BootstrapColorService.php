<?php

namespace Zeroseven\Semantilizer\Services;

use TYPO3\CMS\Core\Messaging\FlashMessage;

class BootstrapColorService
{

    public static function getClassnameByFlashMessageState(int $state): string
    {
        $classes = [
            FlashMessage::NOTICE => 'notice',
            FlashMessage::INFO => 'info',
            FlashMessage::OK => 'success',
            FlashMessage::WARNING => 'warning',
            FlashMessage::ERROR => 'danger'
        ];

        return $classes[$state];
    }

    public static function getColorByFlashMessageState(int $state): string
    {
        $colors = [
            FlashMessage::NOTICE => '#ccc',
            FlashMessage::INFO => '#6daae0',
            FlashMessage::OK => '#79a548',
            FlashMessage::WARNING => '#e8a33d',
            FlashMessage::ERROR => '#c83c3c'
        ];

        return $colors[$state];
    }
}
