<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\Repeater;

use Closure;
use Sylament\Support\Components\Component;
use Sylament\Support\Components\Concerns\CanWrapHeader;
use Sylament\Support\Components\Concerns\HasAlignment;
use Sylament\Support\Components\Concerns\HasVerticalAlignment;
use Sylament\Support\Components\Concerns\HasWidth;
use Twig\Markup;

class TableColumn extends Component
{
    use CanWrapHeader;
    use HasAlignment;
    use HasVerticalAlignment;
    use HasWidth;

    protected string $evaluationIdentifier = 'column';

    protected string|Markup|Closure $label = '';

    protected bool|Closure $isHeaderLabelHidden = false;

    protected bool|Closure $isMarkedAsRequired = false;

    public static function make(string|Markup|Closure $label): static
    {
        $static = static::newInstance();
        $static->label($label);
        $static->configure();

        return $static;
    }

    public function label(string|Markup|Closure $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getLabel(): string|Markup
    {
        return $this->evaluate($this->label);
    }

    public function hiddenHeaderLabel(bool|Closure $condition = true): static
    {
        $this->isHeaderLabelHidden = $condition;

        return $this;
    }

    public function isHeaderLabelHidden(): bool
    {
        return $this->evaluate($this->isHeaderLabelHidden);
    }

    public function markAsRequired(bool|Closure $condition = true): static
    {
        $this->isMarkedAsRequired = $condition;

        return $this;
    }

    public function isMarkedAsRequired(): bool
    {
        return $this->evaluate($this->isMarkedAsRequired);
    }
}
