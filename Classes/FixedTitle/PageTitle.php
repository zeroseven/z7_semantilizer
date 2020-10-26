<?php

namespace Zeroseven\Semantilizer\FixedTitle;

use Zeroseven\Semantilizer\Models\ContentCollection;

class PageTitle implements FixedTitleInterface
{
    public function get(array $params, $parent = null, ContentCollection $contentCollection = null): ?string
    {
        return $params['page']->getTitle() ?: null;
    }
}
