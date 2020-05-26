<?php

namespace Zeroseven\Semantilizer\Utilities;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Zeroseven\Semantilizer\Models\ContentCollection;

class ValidationUtility
{

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

    public function __construct(ContentCollection $contentCollection)
    {
        $mainHeadingContents = [];
        $unexpectedHeadingContents = [];
        $lastHeadingType = 0;
        $contentElements = $contentCollection->getElements();
        $firstKey = $contentCollection->getFirstKey();

        foreach ($contentElements as $contentElement) {
            if ($contentElement->getHeaderType() > 0) {

                // Check for the h1
                if ($contentElement->getHeaderType() === 1) {
                    $mainHeadingContents[$contentElement->getUid()] = $contentElement;
                }

                // Check if the headlines are nested in the right way
                if ($lastHeadingType > 0 && $contentElement->getHeaderType() > $lastHeadingType + 1) {
                    $unexpectedHeadingContents[$contentElement->getUid()] = $contentElement;
                }

                // Store the last headline type
                $lastHeadingType = $contentElement->getHeaderType();
            }
        }

        // Check the length of the main heading(s)
        if (count($mainHeadingContents) === 0) {
            $fix = $contentCollection->count() ? [$firstKey => 1] : null;
            $this->addNotification('missing_h1', [$contentCollection->getFirstElement()], $fix, $contentCollection->count() ? 'error' : 'info');
        } elseif (count($mainHeadingContents) > 1) {
            $fix = [];
            foreach ($contentElements as $contentElement) {
                if ($contentElement->getHeaderType() === 1 && $contentElement->getUid() !== $firstKey) {
                    $fix[$contentElement->getUid()] = 2;
                }
            }
            $this->addNotification('double_h1', $mainHeadingContents, $fix);
        } elseif (array_key_first($mainHeadingContents) !== $firstKey) {
            $fix[$contentCollection->getFirstKey()] = 1;
            foreach ($contentElements as $contentElement) {
                if ($contentElement->getHeaderType() === 1) {
                    $fix[$contentElement->getUid()] = 2;
                }
            }
            $this->addNotification('wrong_ordered_h1', [$contentCollection->getFirstElement()] + $mainHeadingContents, $fix);
        }

        // Add a notification for the unexpected ones
        if (!empty($unexpectedHeadingContents)) {
            $this->addNotification('unexpected_heading', $unexpectedHeadingContents);
        }
    }

    public function getNotifications(): array
    {
        return $this->notifications;
    }

    protected function addNotification(string $errorCode, array $contentElements = null, array $fix = null, string $state = 'warning'): void
    {

        foreach ($contentElements ?? [] as $contentElement) {
            $contentElement->setError(true);
        }

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

    public function getStrongestLevel(): int
    {
        return $this->strongestLevel;
    }

    protected function setStrongestLevel(int $level): int
    {
        return $this->strongestLevel = max($level, $this->getStrongestLevel());
    }

    public function getAffectedContentElements(): ContentCollection
    {
        $contentCollection = GeneralUtility::makeInstance(ContentCollection::class);

        foreach ($this->getNotifications() as $notification) {
            foreach ($notification['contentElements'] as $contentElement) {
                $contentCollection->append($contentElement);
            }
        }

        return $contentCollection;
    }


}
