<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components;

use Closure;
use Sylament\Schemas\Components\Concerns\EntanglesStateWithSingularRelationship;
use Sylament\Schemas\Components\Contracts\CanEntangleWithSingularRelationships;

class Grid extends Component implements CanEntangleWithSingularRelationships
{
    use EntanglesStateWithSingularRelationship;

    protected string|Closure|null $template = '@SylamentSchemas/partials/Grid/index.html.twig';

    /**
     * @param array<string, int|null>|int|Closure|null $columns
     */
    public static function make(array|int|Closure|null $columns = 2): static
    {
        $grid = static::newInstance();

        $grid->columns($columns);

        $grid->configure();

        return $grid;
    }
}
