<?php

declare(strict_types=1);

namespace Zeroseven\Semantilizer\Tests\Unit\Support;

use Zeroseven\Semantilizer\ViewHelpers\Headline\SiblingViewHelper;

final class SiblingViewHelperTestDouble extends SiblingViewHelper
{
    /**
     * @param array<string, mixed> $arguments
     */
    public function withArguments(array $arguments): self
    {
        $this->arguments = array_merge($this->arguments, $arguments);

        return $this;
    }
}
