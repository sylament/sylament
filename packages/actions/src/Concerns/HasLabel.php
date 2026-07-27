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
 * repo, mirroring schemas' port), and `hiddenLabel()`/`isLabelHidden()` are
 * deferred to the iconButton variant (breadth-fill); the button-only MVP never
 * hides the label.
 */
trait HasLabel
{
    protected string|Markup|Closure|null $label = null;

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
}
