<?php

declare(strict_types=1);

namespace Zeroseven\Semantilizer\ViewHelpers\Headline;

class SiblingViewHelper extends AbstractRelationViewHelper
{
    public function render()
    {
        $type = $this->getReference($this->arguments['of']) ?: 0;

        return $this->renderHeadline($type);
    }
}
