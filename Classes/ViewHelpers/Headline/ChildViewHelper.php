<?php

declare(strict_types=1);

namespace Zeroseven\Semantilizer\ViewHelpers\Headline;

class ChildViewHelper extends AbstractRelationViewHelper
{
    public function render(): string
    {
        if (($relation = $this->arguments['of'] ?? null) && $type = $this->getRelation($relation)) {
            return $this->renderHeadline($type + 1);
        }

        return $this->renderHeadline(0);
    }
}
