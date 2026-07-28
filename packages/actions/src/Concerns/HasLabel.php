<?php

declare(strict_types=1);

namespace Sylament\Actions\Concerns;

use Closure;
use Twig\Markup;

use function Symfony\Component\String\u;

/**
 * Ports Filament's actions `HasLabel`. The name-derived default uses
 * `before('.')` (the segment before the first dot), matching Filament's own
 * actions `HasLabel` — which itself differs from `Field`'s `afterLast('.')`. Filament's
 * `translateLabel()`/`$shouldTranslateLabel` is dropped (no translator in the
 * repo, mirroring schemas' port). A hidden label renders as an `aria-label`
 * on the trigger root instead of visible text (Filament's `labelSrOnly`).
 */
trait HasLabel
{
    protected string|Markup|Closure|null $label = null;

    protected bool|Closure $isLabelHidden = false;

    public function label(string|Markup|Closure|null $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getLabel(): string|Markup|null
    {
        return $this->evaluate($this->label)
            ?? (string) u($this->getName())->before('.')->kebab()->replace('-', ' ')->title();
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
