<?php
namespace Zeroseven\Semantilizer\ViewHelpers;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

class SelectTypeViewHelper extends AbstractTagBasedViewHelper
{

    public function initializeArguments()
    {
        parent::initializeArguments();
        parent::registerUniversalTagAttributes();
        $this->registerArgument('selected', 'string', 'The element type', true);
        $this->registerArgument('uid', 'int', 'The id of the content element', true);
        $this->registerArgument('onchange', 'string', 'Add JavaScript for a "onchange" event', true);
    }

    public function render()
    {

        $this->tag->setTagName('select');

        // Add some attributes @see parent::registerUniversalTagAttributes()
        foreach (['class', 'dir', 'id', 'lang', 'style', 'title', 'accesskey', 'tabindex', 'onclick', 'onchange'] as $attribute) {
            if(!empty($attribute)) {
                $this->tag->addAttribute($attribute, $this->arguments[$attribute]);
            }
        }

        $this->tag->setContent($this->generateOptions());

        return $this->tag->render();
    }

    protected function getHighestType(): int
    {
        $highestType = 1;

        // Get the highest type from the tca configuration
        foreach ($GLOBALS['TCA']['tt_content']['columns']['header_type']['config']['items'] as $item) {
            $highestType = (int)max($highestType, $item[1]);
        }

        return $highestType;
    }

    protected function generateOptions(): string
    {
        $content = '';
        $requestUrl =  GeneralUtility::getIndpEnv('REQUEST_URI');

        foreach(range(1, $this->getHighestType()) as $type) {

            $actionUrl = BackendUtility::getLinkToDataHandlerAction(
                sprintf('&data[%s][%d][%s]=%d', 'tt_content', $this->arguments['uid'], 'header_type', $type),
                $requestUrl
            );

            $seleced = $type === $this->arguments['selected'] ? 'selected' : '';

            $content .= sprintf('<option %s value="%s">H%d</option>', $seleced, $actionUrl, $type);
        }

        return $content;
    }

}
