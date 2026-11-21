<?php

declare(strict_types=1);

namespace Sylament\Actions\Concerns;

use Closure;
use Symfony\Contracts\Translation\TranslatableInterface;
use Twig\Markup;

use function Symfony\Component\String\u;

trait HasLabel
{
    protected string|Markup|TranslatableInterface|Closure|null $label = null;

    protected bool|Closure $isLabelHidden = false;

    protected bool $shouldTranslateLabel = false;

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
        $label = $this->evaluate($this->label)
            ?? (string) u($this->getName())->before('.')->kebab()->replace('-', ' ')->title();

        if (is_string($label) && $this->shouldTranslateLabel) {
            return $this->getTranslator()->trans($label);
        }

        return $this->translateText($label);
    }

    public function hiddenLabel(bool|Closure $condition = true): static
    {
        $this->isLabelHidden = $condition;

        return $this;
    }

    public function isLabelHidden(): bool
    {
        return (bool) $this->evaluate($this->isLabelHidden);
    }

    public function hasLabelHidden(): bool
    {
        return $this->isLabelHidden !== false;
    }
}
