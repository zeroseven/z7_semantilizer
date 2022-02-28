<?php

declare(strict_types=1);

namespace Zeroseven\Semantilizer\ViewHelpers\Headline;

use Zeroseven\Semantilizer\ViewHelpers\AbstractHeadlineViewHelper;

class AbstractRelationViewHelper extends AbstractHeadlineViewHelper
{
    public function initializeArguments(): void
    {
        parent::initializeArguments();

        $this->registerArgument('of', 'string', 'Reference id', true);

        $this->addSemantilizerData(['relation', $this->arguments['of']]);
    }
}
