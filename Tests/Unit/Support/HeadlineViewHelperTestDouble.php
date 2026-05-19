<?php

declare(strict_types=1);

namespace Zeroseven\Semantilizer\Tests\Unit\Support;

use Zeroseven\Semantilizer\ViewHelpers\HeadlineViewHelper;

final class HeadlineViewHelperTestDouble extends HeadlineViewHelper
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
