<?php

declare(strict_types=1);

namespace Zeroseven\Semantilizer\ViewHelpers\Headline;

class ChildViewHelper extends AbstractRelationViewHelper
{
    public function render()
    {
        $type = empty($relationType = $this->getRelation($this->arguments['of'])) ? 0 : $relationType + 1;

        return $this->renderHeadline($type);
    }
}
