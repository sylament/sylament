<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components;

use Closure;
use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Schemas\Schema;
use Twig\Markup;

class Group extends Component
{
    protected string|Closure|null $template = '@SylamentSchemas/partials/Group/index.html.twig';

    /**
     * @param Schema|array<Component|Action|ActionGroup|string|Markup>|Component|Action|ActionGroup|string|Markup|Closure|null $schema
     */
    public static function make(Schema|array|Component|Action|ActionGroup|string|Markup|Closure|null $schema = []): static
    {
        $group = static::newInstance();

        $group->components($schema);

        $group->configure();

        return $group;
    }
}
