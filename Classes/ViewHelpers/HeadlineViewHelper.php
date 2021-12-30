<?php

declare(strict_types=1);

namespace Zeroseven\Semantilizer\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;
use Zeroseven\Semantilizer\Utility\PermissionUtility;

class HeadlineViewHelper extends AbstractTagBasedViewHelper
{
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
        $setup = [];
        $value = $this->arguments['edit'];

        if (is_array($value)) {
            $setup['table'] = $value['table'] ?? null;
            $setup['uid'] = $value['uid'] ?? null;
            $setup['field'] = $value['field'] ?? null;
        } elseif (is_string($value) && preg_match('/^(\w+):(\d+)(?::(\w+))?$/', $value, $matches)) {
            $setup['table'] = $matches[1];
            $setup['uid'] = $matches[2];
            $setup['field'] = $matches[3] ?? null;
        }

        return !empty($return = array_filter($setup)) && isset($return['table'], $return['uid']) ? $return : null;
    }

    protected function addSemantilizerData(): void
    {
        if (PermissionUtility::getBackendUser() && ($editSetup = $this->getEditSetup()) && PermissionUtility::editContent($editSetup['table'] ?? null)) {
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
        } else {
            $this->tag->setTagName('div');
            $this->tag->addAttribute('role', 'heading');
        }

        // Add some data attributes to edit content in backend
        $this->addSemantilizerData();

        return $this->tag->render();
    }
}
