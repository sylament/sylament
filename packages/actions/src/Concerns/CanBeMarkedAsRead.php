<?php

declare(strict_types=1);

namespace Sylament\Actions\Concerns;

use Closure;

trait CanBeMarkedAsRead
{
    protected bool|Closure $shouldMarkAsRead = false;

    protected bool|Closure $shouldMarkAsUnread = false;

    public function markAsRead(bool|Closure $condition = true): static
    {
        $this->shouldMarkAsRead = $condition;

        return $this;
    }

    public function markAsUnread(bool|Closure $condition = true): static
    {
        $this->shouldMarkAsUnread = $condition;

        return $this;
    }

    public function shouldMarkAsRead(): bool
    {
        return (bool) $this->evaluate($this->shouldMarkAsRead);
    }

    public function shouldMarkAsUnread(): bool
    {
        return (bool) $this->evaluate($this->shouldMarkAsUnread);
    }

    public function hasMarkAsRead(): bool
    {
        return $this->shouldMarkAsRead !== false;
    }

    public function hasMarkAsUnread(): bool
    {
        return $this->shouldMarkAsUnread !== false;
    }
}
