<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components;

use Closure;
use Sylament\Schemas\Components\Concerns\CanBeAside;
use Sylament\Schemas\Components\Concerns\CanBeCompact;
use Sylament\Schemas\Components\Concerns\CanBeDivided;
use Sylament\Schemas\Components\Concerns\CanBeSecondary;
use Sylament\Schemas\Components\Concerns\HasContentBefore;
use Sylament\Schemas\Components\Concerns\HasDescription;
use Sylament\Schemas\Components\Concerns\HasHeading;
use Sylament\Schemas\Components\Concerns\HasLabel;
use Sylament\Support\Component\Concerns\CanBeContained;
use Twig\Markup;

class Section extends Component
{
    use CanBeAside;
    use CanBeCompact;
    use CanBeContained;
    use CanBeDivided;
    use CanBeSecondary;
    use HasContentBefore;
    use HasDescription;
    use HasHeading;
    use HasLabel;

    protected string|Closure|null $template = '@SylamentSchemas/components/Section.html.twig';

    /**
     * @param string|Markup|callable():(string|Markup)|null $heading
     */
    final public function __construct(string|Markup|callable|null $heading = null)
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
