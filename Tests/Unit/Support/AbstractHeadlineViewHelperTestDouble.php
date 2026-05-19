<?php

declare(strict_types=1);

namespace Zeroseven\Semantilizer\Tests\Unit\Support;

use Zeroseven\Semantilizer\ViewHelpers\AbstractHeadlineViewHelper;

final class AbstractHeadlineViewHelperTestDouble extends AbstractHeadlineViewHelper
{
    /**
     * @param array<string, mixed> $arguments
     */
    public function withArguments(array $arguments): self
    {
        $this->arguments = array_merge($this->arguments, $arguments);

        return $this;
    }

    /**
     * @return array{table?: string, uid?: int, field?: string|null}|null
     */
    public function exposeParseEditSetup(): ?array
    {
        return $this->parseEditSetup();
    }

    public function exposeRenderHeadline(int $type, ?string $relationId = null): string
    {
        return $this->renderHeadline($type, $relationId);
    }
}
