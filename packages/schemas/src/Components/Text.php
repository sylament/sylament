<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components;

use BackedEnum;
use Closure;
use Sylament\Support\Components\Concerns\HasColor;
use Sylament\Support\Components\Concerns\HasFontFamily;
use Sylament\Support\Components\Concerns\HasWeight;
use Sylament\Support\Enums\Size;
use Sylament\Support\Enums\TextSize;
use Twig\Markup;

use function Sylament\Support\blank;

class Text extends Component
{
    use HasColor;
    use HasFontFamily;
    use HasWeight;

    protected string|Closure|null $template = '@SylamentSchemas/components/Text.html.twig';

    protected string|Markup|Closure|null $content;

    protected TextSize|Size|string|Closure|null $size = null;

    final public function __construct(string|Markup|Closure|null $content)
    {
        $this->content($content);
    }

    public static function make(string|Markup|Closure|null $content): static
    {
        $text = new static($content);

        $text->configure();

        return $text;
    }

    public function content(string|Markup|Closure|null $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getContent(): string|Markup|null
    {
        return $this->evaluate($this->content);
    }

    public function size(TextSize|Size|string|Closure|null $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function getSize(): TextSize|Size|string|null
    {
        $size = $this->evaluate($this->size);

        if (blank($size)) {
            return null;
        }

        if (is_string($size)) {
            return TextSize::tryFrom($size) ?? $size;
        }

        return $size;
    }

    /**
     * The class fragment templates emit for the size: `sy-size-<value>` for the
     * `TextSize` / `Size` enum cases, a custom string as-is, null when unset — so
     * templates never type-sniff the enum-vs-string union.
     */
    public function getSizeClass(): ?string
    {
        $size = $this->getSize();

        if ($size instanceof BackedEnum) {
            return 'sy-size-' . $size->value;
        }

        return $size;
    }
}
