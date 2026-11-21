<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Concerns;

use Closure;
use Symfony\Contracts\Translation\TranslatableInterface;
use Twig\Markup;

trait HasLabel
{
    protected bool|Closure $isLabelHidden = false;

    protected string|Markup|TranslatableInterface|Closure|null $label = null;

    protected bool $shouldTranslateLabel = false;

    public function hiddenLabel(bool|Closure $condition = true): static
    {
        $this->isLabelHidden = $condition;

        return $this;
    }

    public function isLabelHidden(): bool
    {
        return $this->evaluate($this->isLabelHidden);
    }

    public function label(string|Markup|TranslatableInterface|Closure|null $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function translateLabel(bool $shouldTranslateLabel = true): static
    {
        $this->shouldTranslateLabel = $shouldTranslateLabel;

        return $this;
    }

    public function getLabel(): string|Markup|null
    {
        $label = $this->evaluate($this->label);

        if (is_string($label) && $this->shouldTranslateLabel) {
            return $this->getTranslator()->trans($label);
        }

        return $this->translateText($label);
    }

    public function hasCustomLabel(): bool
    {
        return $this->label !== null;
    }
}
