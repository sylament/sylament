<?php

declare(strict_types=1);

namespace Sylament\Tables\Table\Concerns;

use Sylament\Actions\Action;
use Sylament\Tables\Columns\Column;

trait HasColumns
{
    /**
     * @var array<string, Column>
     */
    protected array $columns = [];

    /**
     * @param array<Column> $columns
     */
    public function columns(array $columns): static
    {
        $this->columns = [];

        foreach ($columns as $column) {
            $column->table($this);

            $this->columns[$column->getName()] = $column;
        }

        foreach ($this->columns as $column) {
            $action = $column->getAction();

            if (!$action instanceof Action) {
                continue;
            }

            $this->cacheAction($action->table($this));
        }

        return $this;
    }

    /**
     * @return array<string, Column>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getColumn(string $name): ?Column
    {
        return $this->getColumns()[$name] ?? null;
    }
}
