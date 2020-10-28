<?php

namespace Zeroseven\Semantilizer\FixedTitle;

use Zeroseven\Semantilizer\Models\ContentCollection;

interface FixedTitleInterface
{
    public function get(array $params, $parent = null, ContentCollection $contentCollection = null): ?string;
}
