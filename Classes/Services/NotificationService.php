<?php

namespace Zeroseven\Semantilizer\Services;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class NotificationService
{

    /** @var array */
    protected $contentElements = [];

    /** @var array */
    protected $notifications = [];

    /** @var int */
    protected $strongestLevel = FlashMessage::NOTICE;

    /** @var array */
    protected const ERROR_CODES = [
        'missing_h1' => 1,
        'double_h1' => 2,
        'wrong_ordered_h1' => 3,
        'unexpected_heading' => 4,
    ];

    /** @var array */
    protected const STATES = [
        'notice' => FlashMessage::NOTICE,
        'info' => FlashMessage::INFO,
        'ok' => FlashMessage::OK,
        'warning' => FlashMessage::WARNING,
        'error' => FlashMessage::ERROR
    ];

    public function __construct(array $contentElements)
    {
        $this->validate($contentElements);
    }

    public function getNotifications(): array
    {
        return $this->notifications;
    }

    protected function addNotification(string $errorCode, array $contentElements = null, array $fix = null, string $state = 'warning'): void
    {

        $this->notifications[] = [
            'key' => self::ERROR_CODES[$errorCode],
            'state' => self::STATES[$state],
            'contentElements' => $contentElements,
            'fixLink' => !is_array($fix) ? null : BackendUtility::getLinkToDataHandlerAction(
                implode(',', array_map(function ($type, $uid) {
                    return sprintf('&data[tt_content][%d][header_type]=%d', $uid, $type);
                }, $fix, array_keys($fix))),
                GeneralUtility::getIndpEnv('REQUEST_URI')
            )
        ];

        // Set the strongest notification
        $this->setStrongestLevel(self::STATES[$state]);
    }

    public function getStrongestLevel()
    {
        return $this->strongestLevel;
    }

    protected function setStrongestLevel($level)
    {
        $this->strongestLevel = max($level, $this->getStrongestLevel());
    }

    public function getStates(): array
    {
        return self::STATES;
    }

    protected function validate(array $contentElements): void
    {

        $mainHeadingContents = [];
        $unexpectedHeadingContents = [];
        $lastHeadingType = 0;
        $firstKey = array_key_first($contentElements);

        foreach ($contentElements as $index => $contentElement) {

            // Get the header_type
            $type = (int)$contentElement['headerType'];

            if ($type > 0) {

                // Check for the h1
                if ($type === 1) {
                    $mainHeadingContents[$index] = $contentElement;
                }

                // Check if the headlines are nested in the right way
                if ($lastHeadingType > 0 && $type > $lastHeadingType + 1) {
                    $unexpectedHeadingContents[$index] = $contentElement;
                }

                // Store the last headline type
                $lastHeadingType = $type;
            }
        }

        // Check the length of the main heading(s)
        if (count($mainHeadingContents) === 0) {
            $fix = [$firstKey => 1];
            $this->addNotification('missing_h1', $contentElements, $fix,count($contentElements) ? 'error' : 'info');
        } elseif (count($mainHeadingContents) > 1) {
            $fix = [];
            foreach($contentElements as $uid => $row) {
                if($uid !== array_key_first($contentElements) && (int)$row['headerType'] === 1 ) {
                    $fix[$uid] = 2;
                }
            }
            $this->addNotification('double_h1', $mainHeadingContents, $fix);
        } elseif (array_key_first($mainHeadingContents) !== $firstKey) {
            $fix[array_key_first($contentElements)] = 1;
            foreach($contentElements as $uid => $row) {
                if((int)$row['headerType'] === 1 ) {
                    $fix[$uid] = 2;
                }
            }
            $this->addNotification('wrong_ordered_h1', [$firstKey => $contentElements[$firstKey]] + $mainHeadingContents, $fix);
        }

        // Add a notification for the unexpected ones
        if (!empty($unexpectedHeadingContents)) {
            $fix = [];
            foreach ($unexpectedHeadingContents as $uid => $row) {
                $fix[$uid] = 2;
            }
            $this->addNotification('unexpected_heading', $unexpectedHeadingContents, $fix);
        }
    }

}
