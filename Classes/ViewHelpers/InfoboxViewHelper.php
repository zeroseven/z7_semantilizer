<?php

namespace Zeroseven\Semantilizer\ViewHelpers;

use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use Zeroseven\Semantilizer\Services\BootstrapColorService;

class InfoboxViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Be\InfoboxViewHelper
{

    /**
     * Initialize arguments.
     */
    public function initializeArguments(): void
    {
        parent::initializeArguments();

        $this->registerArgument('iconColor', 'int', 'The color of the icon');
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext): string
    {

        // Call the "original" box
        $infoBox = parent::renderStatic($arguments, $renderChildrenClosure, $renderingContext);

        // Wrap the box and add CSS to the page
        if ($arguments['iconColor'] !== null && empty($arguments['disableIcon'])) {
            $uniqueId = 'b' . md5(uniqid('box', true));
            $infoBox = sprintf('<div id="%s">%s</div>', $uniqueId, $infoBox);

            if ($color = BootstrapColorService::getColorByFlashMessageState($arguments['iconColor'])) {
                GeneralUtility::makeInstance(PageRenderer::class)->addCssInlineBlock($uniqueId, sprintf('
                    #%s .media-left .fa-circle:before {
                        color: %s;
                    }
                ', $uniqueId, $color));
            }
        }

        return $infoBox;
    }

}
