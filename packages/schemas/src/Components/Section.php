<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components;

use Closure;
use Sylament\Schemas\Components\Concerns\HasDescription;
use Sylament\Schemas\Components\Concerns\HasHeading;
use Twig\Markup;

class Section extends Component
{
    use HasDescription;
    use HasHeading;

    protected string|Closure|null $template = '@SylamentSchemas/components/Section.html.twig';

    /**
     * @param string|Markup|callable():(string|Markup)|null $heading
     */
    public function __construct(string|Markup|callable|null $heading = null)
    {
        $this->heading($heading);
    }

    /**
     * @param string|Markup|callable():(string|Markup)|null $heading
     */
    public static function make(string|Markup|callable|null $heading = null): static
    {
        $section = new static($heading);

        $section->configure();

        return $section;
    }
}
