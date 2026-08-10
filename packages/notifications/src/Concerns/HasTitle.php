<?php

declare(strict_types=1);

namespace Sylament\Notifications\Concerns;

use Closure;

trait HasTitle
{
    protected string|Closure|null $title = null;

    public function title(string|Closure|null $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->evaluate($this->title);
    }
}
