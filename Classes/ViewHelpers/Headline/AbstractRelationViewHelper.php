<?php

declare(strict_types=1);

namespace Zeroseven\Semantilizer\ViewHelpers\Headline;

use Zeroseven\Semantilizer\ViewHelpers\AbstractHeadlineViewHelper;

class AbstractRelationViewHelper extends AbstractHeadlineViewHelper
{
    public function initializeArguments(): void
    {
        parent::initializeArguments();

        $this->registerArgument('of', 'string', 'Relation id', true);
    }

    protected function renderHeadline(int $type, string $relationId = null): string
    {
        $this->addSemantilizerData(['relatedTo' => $this->arguments['of']]);

        return parent::renderHeadline($type, $relationId);
    }
}
