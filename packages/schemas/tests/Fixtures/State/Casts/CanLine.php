<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\State\Casts;

final class CanLine
{
    private ?bool $publish = null;

    public function canPublish(): ?bool
    {
        return $this->publish;
    }

    public function setPublish(?bool $publish): void
    {
        $this->publish = $publish;
    }
}
