<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components;

use Closure;
use Twig\Markup;

class Html extends Component
{
    protected string|Closure|null $template = '@SylamentSchemas/partials/Html/index.html.twig';

    protected string|Markup|Closure|null $content = null;

    public static function make(string|Markup|Closure|null $content): static
    {
        $html = static::newInstance();

        $html->content($content);

        $html->configure();

        return $html;
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
}
