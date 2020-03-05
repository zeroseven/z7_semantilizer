<?php

namespace Zeroseven\Semantilizer\ViewHelpers;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

class SelectTypeViewHelper extends AbstractTagBasedViewHelper
{

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerUniversalTagAttributes();

        // Register some arguments
        $this->registerArgument('selected', 'string', 'The element type', true);
        $this->registerArgument('uid', 'int', 'The id of the content element', true);
        $this->registerArgument('section', 'string', 'An anchor to a section');

        // Apply arguments
        $this->registerTagAttribute('onchange', 'string', 'Add JavaScript for a "onchange" event');
    }

    public function render(): string
    {

        $this->tag->setTagName('select');
        $this->tag->setContent($this->generateOptions());

        return $this->tag->render();
    }

    protected function getHeaderTypes(): array
    {
        $headerTypes = [];

        // Get the highest type from the tca configuration
        foreach ($GLOBALS['TCA']['tt_content']['columns']['header_type']['config']['items'] as $item) {
            $headerTypes[$item[1]] = true;
        }

        // Get the items by the TCEFORM
        $pagesTsConfig = BackendUtility::getPagesTSconfig(GeneralUtility::_GP('id'));
        $headerTypeConfig = $pagesTsConfig['TCEFORM.']['tt_content.']['header_type.'] ?: [];

        // Remove Items
        if ($removeItems = $headerTypeConfig['removeItems']) {
            foreach (GeneralUtility::intExplode(',', $removeItems) as $key) {
                if ($headerTypes[$key]) {
                    unset($headerTypes[$key]);
                }
            }
        }

        // Add items
        if ($addItems = $headerTypeConfig['addItems.']) {
            foreach ($addItems as $key => $value) {
                $headerTypes[$key] = true;
            }
        }

        ksort($headerTypes);

        return array_keys($headerTypes);
    }

    protected function generateOptions(): string
    {
        $content = '';
        $requestUrl = GeneralUtility::getIndpEnv('REQUEST_URI') . ($this->arguments['section'] ? sprintf('#%s',
                htmlspecialchars($this->arguments['section'])) : '');
        $types = $this->getHeaderTypes();

        foreach ($types as $key => $type) {

            if ($type > 0) {
                $selected = $type === $this->arguments['selected'] ? 'selected' : '';

                $actionUrl = $selected ? '' : BackendUtility::getLinkToDataHandlerAction(
                    sprintf('&data[%s][%d][%s]=%d', 'tt_content', $this->arguments['uid'], 'header_type', $type),
                    $requestUrl
                );

                $content .= sprintf('<option %s value="%s">H%d</option>', $selected, $actionUrl, $type);
            }
        }

        if (!in_array((int)$this->arguments['selected'], $types, true)) {
            $content .= '<option selected value="">⚠️</option>';
        }

        return $content;
    }

}
