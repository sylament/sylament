<?php

declare(strict_types=1);

namespace Sylament\Actions\Concerns;

use Sylament\Tables\Table;

trait BelongsToTable
{
    protected ?Table $table = null;

    public function table(?Table $table): static
    {
        $this->table = $table;

        return $this;
    }

    public function getTable(): ?Table
    {
        return $this->table ?? $this->getGroup()?->getTable();
    }

    public function hasTable(): bool
    {
        return $this->getTable() !== null;
    }
}
