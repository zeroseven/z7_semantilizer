<?php
namespace Zeroseven\Semantilizer\ViewHelpers\Link;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

class FixViewHelper extends AbstractTagBasedViewHelper
{

    public function initializeArguments(): void
    {
        parent::initializeArguments();

        $this->registerArgument('key', 'int', 'The error key', true);
        $this->registerArgument('contentElements', 'array', 'The affected content elements', true);

        $this->registerUniversalTagAttributes();
    }

    public function render(): string
    {

        $this->tag->setTagName('a');

        // Add some attributes @see parent::registerUniversalTagAttributes()
        foreach (['class', 'dir', 'id', 'lang', 'style', 'title', 'accesskey', 'tabindex', 'onclick', 'onchange'] as $attribute) {
            if(!empty($this->arguments[$attribute])) {
                $this->tag->addAttribute($attribute, $this->arguments[$attribute]);
            }
        }

        $this->tag->setContent($this->renderChildren());

        return $this->tag->render();
    }
}
