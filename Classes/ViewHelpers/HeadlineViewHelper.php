<?php

declare(strict_types=1);

namespace Zeroseven\Semantilizer\ViewHelpers;

use Psr\Http\Message\RequestInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

class HeadlineViewHelper extends AbstractTagBasedViewHelper
{
    /** @var BackendUserAuthentication|null */
    private $backendUser;

    public function __construct()
    {
        parent::__construct();

        $this->backendUser = $GLOBALS['BE_USER'] ?? null;
    }

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        parent::registerUniversalTagAttributes();

        $this->registerArgument('type', 'int', 'Header type (1,2,3,4,5,6)');
        $this->registerArgument('content', 'string', 'Header content');
        $this->registerArgument('edit', 'string|array', 'Content edit setup (Example "{table:\'tt_content\', uid:data.uid, field:\'header_type\'}" or "tt_content:{data.uid}:header_type")');
    }

    protected function getEditSetup(): ?array
    {
        $value = $this->arguments['edit'];
        $table = null;
        $uid = null;
        $field = null;

        // Get setup
        if (is_array($value)) {
            $table = $value['table'] ?? null;
            $uid = (int)$value['uid'] ?? null;
            $field = $value['field'] ?? null;
        } elseif (is_string($value) && preg_match('/^(\w+):(\d+)(?::(\w+))?$/', $value, $matches)) {
            $table = $matches[1];
            $uid = (int)$matches[2];
            $field = $matches[3] ?? null;
        }

        // Check backend user permissions
        if (!$table || !$uid || !$this->backendUser->check('tables_modify', $table)) {
            return null;
        }

        // Get localized uid
        if (($languageUid = GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('language', 'id'))
            && ($localizedRecord = BackendUtility::getRecordLocalization($table, $uid, $languageUid))
            && ($localizedUid = reset($localizedRecord)['uid'] ?? null)
        ) {
            $uid = (int)$localizedUid;
        }

        // Check content permission
        if ($table === 'tt_content') {
            $typeField = $GLOBALS['TCA'][$table]['ctrl']['type'];
            $type = BackendUtility::getRecord($table, $uid, $typeField);

            if (empty($type) || !$this->backendUser->check('explicit_allowdeny', $table . ':' . $typeField . ':' . reset($type) . ':ALLOW')) {
                return null;
            }
        }

        // Check field permissions
        if ($field && ($GLOBALS['TCA'][$table]['columns'][$field]['exclude'] ?? false) && !$this->backendUser->check('non_exclude_fields', $table . ':' . $field)) {
            $field = null;
        }

        // Return setup array
        return array_filter([
            'table' => $table,
            'uid' => $uid,
            'field' => $field
        ]);
    }

    protected function addSemantilizerData(): void
    {
        if ($this->backendUser
            && $GLOBALS['TYPO3_REQUEST'] instanceof RequestInterface
            && !empty($GLOBALS['TYPO3_REQUEST']->getHeader('X-Semantilizer'))
            && ($editSetup = $this->getEditSetup())
        ) {
            $this->tag->addAttribute('data-semantilizer', json_encode($editSetup));
        }
    }

    public function render(): string
    {
        // Set content or remove abort
        if ($content = $this->arguments['content'] ?: $this->renderChildren()) {
            $this->tag->setContent($content);
        } else {
            return '';
        }

        // Set header type (fallback to a "div")
        if (($type = (int)$this->arguments['type']) && in_array($type, [1, 2, 3, 4, 5, 6], true)) {
            $this->tag->setTagName('h' . $type);
            $this->addSemantilizerData();
        } else {
            $this->tag->setTagName('div');
            $this->tag->addAttribute('role', 'heading');
        }

        return $this->tag->render();
    }
}
