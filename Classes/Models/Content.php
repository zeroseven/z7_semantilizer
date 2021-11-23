<?php

namespace Zeroseven\Semantilizer\Models;

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Zeroseven\Semantilizer\Services\PermissionService;

class Content extends AbstractDataModel
{

    /** @var array */
    public const REQUIRED_FIELDS = [
        'uid',
        'header',
        'header_type',
        'cType',
    ];

    public function getUid(): int
    {
        return (int)$this->data['uid'];
    }

    public function getHeader(): string
    {
        return $this->data['header'];
    }

    public function getHeaderType(): int
    {
        return (int)$this->data['header_type'];
    }

    public function getCType(): string
    {
        return $this->data['cType'];
    }

    public function isError(): bool
    {
        return (bool)($this->data['__error'] ?? false);
    }

    public function setError(bool $error)
    {
        $this->data['__error'] = $error;
    }

    public function isFixed(): bool
    {
        return (bool)($this->data['__fixed'] ?? false);
    }

    public function setFixed(bool $fixed)
    {
        $this->data['__fixed'] = $fixed;
    }

    public function getEditLink(): string
    {
        return PermissionService::editContent() ? GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute('record_edit', [
            'edit' => [
                'tt_content' => [
                    $this->getUid() => 'edit'
                ]
            ],
            'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
        ]) : '#no-access';
    }
}
