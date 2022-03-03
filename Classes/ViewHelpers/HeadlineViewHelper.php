<?php

declare(strict_types=1);

namespace Zeroseven\Semantilizer\ViewHelpers;

class HeadlineViewHelper extends AbstractHeadlineViewHelper
{
    public function initializeArguments(): void
    {
        parent::initializeArguments();

        $this->registerArgument('type', 'int', 'Header type (1,2,3,4,5,6)');
    }

    public function render(): string
    {
        // Relation id fallback
        if (empty($relationId = $this->arguments['relationId']) && ($editSetup = $this->parseEditSetup())) {
            $relationId = $editSetup['table'] . ':' . $editSetup['uid'];
        }

        return $this->renderHeadline((int)$this->arguments['type'], (string)$relationId);
    }
}
