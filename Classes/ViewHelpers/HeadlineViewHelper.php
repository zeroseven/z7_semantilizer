<?php

declare(strict_types=1);

namespace Zeroseven\Semantilizer\ViewHelpers;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class HeadlineViewHelper extends AbstractHeadlineViewHelper
{
    public function initializeArguments(): void
    {
        parent::initializeArguments();

        $this->registerArgument('type', 'int', 'Header type (1,2,3,4,5,6)');
        $this->registerArgument('edit', 'string|array', 'Content edit setup (Example "{table:\'tt_content\', uid:data.uid, field:\'header_type\'}" or "tt_content:{data.uid}:header_type")');
    }

    protected function parseEditSetup(): ?array
    {
        $value = $this->arguments['edit'] ?? null;

        if (is_array($value)) {
            return [
                'table' => $value['table'] ?? null,
                'uid' => (int)$value['uid'] ?? null,
                'field' => $value['field'] ?? null
            ];
        }

        if (is_string($value) && preg_match('/^(\w+):(\d+)(?::(\w+))?$/', $value, $matches)) {
            return [
                'table' => $matches[1],
                'uid' => (int)$matches[2],
                'field' => $matches[3] ?? null
            ];
        }

        return null;
    }

    protected function getEditSetup(): ?array
    {
        // Get values and assign them to the variables
        list($table, $uid, $field) = ($setup = $this->parseEditSetup()) ? array_values($setup) : [];

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

    public function render(): string
    {
        if ($editSetup = $this->getEditSetup()) {
            $this->addSemantilizerData($editSetup);
        }

        if (empty($referenceId = $this->arguments['referenceId']) && $editSetup) {
            $referenceId = $editSetup['table'] . ':' . $editSetup['uid'];
        }

        return $this->renderHeadline((int)$this->arguments['type'], (string)$referenceId);
    }
}
