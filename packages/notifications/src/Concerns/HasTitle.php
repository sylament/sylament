<?php

declare(strict_types=1);

namespace Sylament\Notifications\Concerns;

use Closure;
use Symfony\Contracts\Translation\TranslatableInterface;

trait HasTitle
{
    protected string|TranslatableInterface|Closure|null $title = null;

    public function title(string|TranslatableInterface|Closure|null $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->translateText($this->evaluate($this->title));
    }
}
