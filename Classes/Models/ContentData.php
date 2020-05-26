<?php

namespace Zeroseven\Semantilizer\Models;

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ContentData
{

    /** @var array */
    protected $data = [];

    /** @var array */
    protected $requiredFields = [
        'uid',
        'header',
        'header_type',
        'cType',
    ];

    public function __construct(array $data)
    {
        foreach ($this->requiredFields as $name) {
            if (!isset($data[$name])) {
                throw new \Exception(sprintf('Key "%s" is missing in data array', $name));
            }
        }

        $this->data = $data;
    }

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

    public function getCTye(): string
    {
        return $this->data['cType'];
    }

    public function isError(): bool
    {
        return $this->data['__error'] ?? false;
    }

    public function setError(bool $error)
    {
        $this->data['__error'] = $error;
    }

    public function isFixed(): bool
    {
        return $this->data['__fixed'];
    }

    public function setFixed(bool $fixed)
    {
        $this->data['__fixed'] = $fixed;
    }

    public function getEditLink(): string
    {
        return GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute('record_edit', [
            'edit' => [
                'tt_content' => [
                    $this->getUid() => 'edit'
                ]
            ],
            'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
        ]);
    }
}
