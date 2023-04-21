<?php

declare(strict_types=1);

namespace Zeroseven\Semantilizer\ViewHelpers;

use JsonException;
use Psr\Http\Message\RequestInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

class AbstractHeadlineViewHelper extends AbstractTagBasedViewHelper
{
    protected ?BackendUserAuthentication $backendUser;
    protected array $dataAttributes;

    public function __construct()
    {
        parent::__construct();

        $this->backendUser = $GLOBALS['BE_USER'] ?? null;
        $this->dataAttributes = [];
    }

    public function initializeArguments(): void
    {
        parent::initializeArguments();

        $this->registerUniversalTagAttributes();
        $this->registerArgument('content', 'string', 'Header content');
        $this->registerArgument('relationId', 'string', 'Relation identifier for child and sibling viewHelpers');
        $this->registerArgument('edit', 'string|array', 'Content edit setup (Example "{table:\'tt_content\', uid:data.uid, field:\'header_type\'}" or "tt_content:{data.uid}:header_type")');
    }

    protected function parseEditSetup(): ?array
    {
        $value = $this->arguments['edit'] ?? null;

        if (is_array($value)) {
            return [
                'table' => $value['table'] ?? null,
                'uid' => (int)($value['uid'] ?? null),
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
        [$table, $uid, $field] = ($setup = $this->parseEditSetup()) ? array_values($setup) : [null, null, null];

        // Check backend user permissions
        if (!$table || !$uid || !$this->backendUser || !$this->backendUser->check('tables_modify', $table)) {
            return null;
        }

        // Get localized uid
        try {
            if (($languageUid = GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('language', 'id'))
                && ($localizedRecord = BackendUtility::getRecordLocalization($table, $uid, $languageUid))
                && ($localizedUid = reset($localizedRecord)['uid'] ?? null)
            ) {
                $uid = (int)$localizedUid;
            }
        } catch (AspectNotFoundException $e) {
            return null;
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
        if ($field && ($GLOBALS['TCA'][$table]['columns'][$field]['exclude'] ?? null) && !$this->backendUser->check('non_exclude_fields', $table . ':' . $field)) {
            $field = null;
        }

        // Return setup array
        return array_filter([
            'table' => $table,
            'uid' => $uid,
            'field' => $field
        ]);
    }

    protected function addSemantilizerData(array $data): void
    {
        $this->dataAttributes = array_merge($this->dataAttributes, $data);
    }

    protected function storeRelation($relationId, int $type): void
    {
        if (!is_array($GLOBALS['USER']['z7_semantilizer']['temp']['relations'] ?? null)) {
            $GLOBALS['USER']['z7_semantilizer']['temp']['relations'] = [];
        }

        $GLOBALS['USER']['z7_semantilizer']['temp']['relations'][$relationId] = $type;
    }

    protected function getRelation($relationId): ?int
    {
        return $GLOBALS['USER']['z7_semantilizer']['temp']['relations'][$relationId] ?? null;
    }

    protected function renderHeadline(int $type, string $relationId = null): string
    {
        // Set content or abort if empty
        if ($content = trim((string)($this->arguments['content'] ?: $this->renderChildren()))) {
            $this->tag->setContent($content);
        } else {
            return '';
        }

        // Set header type (fallback to a "div" element)
        if (in_array($type, [1, 2, 3, 4, 5, 6], true)) {
            $this->tag->setTagName('h' . $type);
        } else {
            $this->tag->setTagName('div');
            $this->tag->addAttribute('role', 'heading');
        }

        // Store the relation for sibling and child viewHelpers
        if ($relationId !== null || $relationId = $this->arguments['relationId']) {
            $this->addSemantilizerData(['relationId' => $relationId]);
            $this->storeRelation($relationId, $type);
        }

        // Add data attributes if the user is logged in and the Semantilizer has accessed the page
        if ($this->backendUser && $GLOBALS['TYPO3_REQUEST'] instanceof RequestInterface && $GLOBALS['TYPO3_REQUEST']->getHeader('X-Semantilizer')) {
            if ($editSetup = $this->getEditSetup()) {
                $this->addSemantilizerData($editSetup);
            }

            if (!empty($this->dataAttributes)) {
                try {
                    $this->tag->addAttribute('data-semantilizer', json_encode($this->dataAttributes, JSON_THROW_ON_ERROR));
                } catch (JsonException $e) {
                }
            }
        }

        // Ciao …
        return $this->tag->render();
    }
}
