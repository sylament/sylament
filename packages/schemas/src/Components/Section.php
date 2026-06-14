<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components;

use Closure;
use Sylament\Schemas\Components\Concerns\HasDescription;
use Sylament\Schemas\Components\Concerns\HasHeading;

class Section extends Component
{
    use HasDescription;
    use HasHeading;

    protected string|Closure|null $template = '@SylamentSchemas/components/Section.html.twig';

    /**
     * @param string|callable():string|null $heading
     */
    public function __construct(string|callable|null $heading = null)
    {
        $this->heading($heading);
    }

    /**
     * @param string|callable():string|null $heading
     */
    public static function make(string|callable|null $heading = null): static
    {
        $section = new static($heading);

        $section->configure();

        return $section;
    }
}
