<?php

declare(strict_types=1);

namespace Zeroseven\Semantilizer\ViewHelpers\Headline;

class SiblingViewHelper extends AbstractRelationViewHelper
{
    public function render(): string
    {
        if (($relation = $this->arguments['of'] ?? null) && $type = $this->getRelation($relation)) {
            return $this->renderHeadline($type);
        }

        return $this->renderHeadline(0);
    }
}
