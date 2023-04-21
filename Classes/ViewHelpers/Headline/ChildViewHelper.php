<?php

declare(strict_types=1);

namespace Zeroseven\Semantilizer\ViewHelpers\Headline;

class ChildViewHelper extends AbstractRelationViewHelper
{
    public function render(): string
    {
        $type = $relationType = $this->getRelation($this->arguments['of']) === null ? 0 : $relationType + 1;

        return $this->renderHeadline($type);
    }
}
