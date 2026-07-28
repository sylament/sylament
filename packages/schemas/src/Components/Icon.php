<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components;

use BackedEnum;
use Closure;
use Sylament\Support\Components\Concerns\HasColor;
use Sylament\Support\Components\Concerns\HasTooltip;
use Sylament\Support\Enums\IconSize;
use Twig\Markup;

use function Sylament\Support\blank;

class Icon extends Component
{
    use HasColor;
    use HasTooltip;

    protected string|Closure|null $template = '@SylamentSchemas/components/Icon.html.twig';

    protected string|BackedEnum|Markup|Closure $icon;

    protected IconSize|string|Closure|null $size = null;

    public static function make(string|BackedEnum|Markup|Closure $icon): static
    {
        $component = static::newInstance();

        $component->icon($icon);

        $component->configure();

        return $component;
    }

    public function icon(string|BackedEnum|Markup|Closure $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function getIcon(): string|BackedEnum|Markup
    {
        return $this->evaluate($this->icon);
    }

    public function size(IconSize|string|Closure|null $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function getSize(): IconSize|string|null
    {
        $size = $this->evaluate($this->size);

        if (blank($size)) {
            return null;
        }

        if (is_string($size)) {
            return IconSize::tryFrom($size) ?? $size;
        }

        return $size;
    }
}
