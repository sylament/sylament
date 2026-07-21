<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Concerns;

use Closure;
use Sylament\Schemas\Schema;

trait HasColumns
{
    /**
     * @var array<string|int, array<string, ?int>|int|Closure|null>|null
     */
    protected ?array $columns = null;

    /**
     * @param array<string, int|null>|int|Closure|null $columns
     */
    public function columns(array|int|Closure|null $columns = 2): static
    {
        if ($columns instanceof Closure) {
            $this->columns[] = $columns;

            return $this;
        }

        if (!is_array($columns)) {
            $columns = [
                'lg' => $columns,
            ];
        }

        $this->columns = [
            ...($this->columns ?? []),
            ...$columns,
        ];

        return $this;
    }

    public function hasCustomColumns(): bool
    {
        return $this->columns !== null;
    }

    /**
     * @return array<string,?int>|int|null
     */
    public function getColumns(?string $breakpoint = null): array|int|null
    {
        $columns = $this->getAllColumns();

        if ($breakpoint !== null) {
            return $columns[$breakpoint] ?? null;
        }

        return $columns;
    }

    /**
     * @return array<string, ?int>
     */
    public function getAllColumns(): array
    {
        if ($this instanceof Schema && ($parentComponent = $this->getParentComponent())) {
            return $parentComponent->getAllColumns();
        }

        if ($this->columns === null) {
            return [
                'default' => 1,
                'sm' => null,
                'md' => null,
                'lg' => null,
                'xl' => null,
                '2xl' => null,
            ];
        }

        $columns = [];

        foreach ($this->columns as $columnBreakpoint => $column) {
            $column = $this->evaluate($column);

            if (is_array($column)) {
                $columns = [
                    ...$columns,
                    ...$column,
                ];

                continue;
            }

            if ($columnBreakpoint === '') {
                continue;
            }

            if (!is_string($columnBreakpoint)) {
                $columns['lg'] = $column;

                continue;
            }

            $columns[$columnBreakpoint] = $column;
        }

        return $columns;
    }
}
