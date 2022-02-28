<?php

declare(strict_types=1);

namespace Zeroseven\Semantilizer\ViewHelpers\Headline;

class ChildViewHelper extends AbstractRelationViewHelper
{
    public function render()
    {
        $type = empty($reference = $this->getReference($this->arguments['of'])) ? 0 : $reference + 1;

        $this->renderHeadline($type);
    }
}
