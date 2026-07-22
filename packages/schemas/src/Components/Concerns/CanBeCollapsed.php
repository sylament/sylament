<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Concerns;

use Closure;
use Sylament\Schemas\Schema;

trait CanBeCollapsed
{
    protected bool|Closure $isCollapsed = false;

    protected bool|Closure|null $isCollapsible = null;

    protected bool|Closure $shouldPersistCollapsed = false;

    public function collapsed(bool|Closure $condition = true, bool $shouldMakeComponentCollapsible = true): static
    {
        $this->isCollapsed = $condition;

        if ($shouldMakeComponentCollapsible && ($this->isCollapsible === null)) {
            $this->collapsible();
        }

        return $this;
    }

    public function isCollapsed(?Schema $schema = null): bool
    {
        return $this->evaluate($this->isCollapsed, ['item' => $schema]);
    }

    public function collapsible(bool|Closure|null $condition = true): static
    {
        $this->isCollapsible = $condition;

        return $this;
    }

    public function isCollapsible(): bool
    {
        return $this->evaluate($this->isCollapsible) ?? false;
    }

    public function persistCollapsed(bool|Closure $condition = true): static
    {
        $this->shouldPersistCollapsed = $condition;

        return $this;
    }

    public function shouldPersistCollapsed(): bool
    {
        return $this->evaluate($this->shouldPersistCollapsed);
    }
}
