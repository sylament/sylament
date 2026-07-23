<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\Concerns;

use Closure;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Text;
use Twig\Markup;

use function Sylament\Support\blank;

trait HasHelperText
{
    public function helperText(string|Markup|Closure|null $text): static
    {
        $this->belowContent(function (Component $component) use ($text): ?Text {
            $content = $component->evaluate($text);

            if (blank($content)) {
                return null;
            }

            return Text::make($content);
        });

        return $this;
    }
}
