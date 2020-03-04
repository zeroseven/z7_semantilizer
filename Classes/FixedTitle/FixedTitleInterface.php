<?php

namespace Zeroseven\Semantilizer\FixedTitle;

use Zeroseven\Semantilizer\Hooks\DrawHeaderHook;

interface FixedTitleInterface {

    public function get(array $params, DrawHeaderHook $parent): string;

}
