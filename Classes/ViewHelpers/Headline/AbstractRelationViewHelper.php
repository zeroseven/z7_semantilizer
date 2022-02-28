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
    }

    protected function renderHeadline(int $type, string $referenceId = null): string
    {
        $this->addSemantilizerData(['referenceId' => $this->arguments['of']]);

        return parent::renderHeadline($type, $referenceId);
    }
}
