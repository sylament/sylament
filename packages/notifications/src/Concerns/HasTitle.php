<?php

declare(strict_types=1);

namespace Sylament\Notifications\Concerns;

use Closure;
use Symfony\Contracts\Translation\TranslatableInterface;
use Twig\Markup;

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
        $title = $this->translateText($this->evaluate($this->title));

        return $title instanceof Markup ? (string) $title : $title;
    }
}
