<?php

namespace Zeroseven\Semantilizer\FixedTitle;

use Zeroseven\Semantilizer\Models\ContentCollection;
use Zeroseven\Semantilizer\Models\Page;

class PageTitle implements FixedTitleInterface
{
    public function get(array $params, Page $parent = null, ContentCollection $contentCollection = null): ?string
    {
        return $params['page']->getTitle() ?: null;
    }
}
