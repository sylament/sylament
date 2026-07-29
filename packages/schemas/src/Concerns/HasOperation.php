<?php

declare(strict_types=1);

namespace Sylament\Schemas\Concerns;

use function Sylament\Support\filled;

trait HasOperation
{
    protected ?string $operation = null;

    public function operation(?string $operation): static
    {
        $this->operation = $operation;

        return $this;
    }

    public function getOperation(): string
    {
        if (filled($this->operation)) {
            return $this->operation;
        }

        if (($parentComponent = $this->getParentComponent()) !== null) {
            return $parentComponent->getContainer()->getOperation();
        }

        return $this->getLiveComponent()::class;
    }
}
