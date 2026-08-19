<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components;

use Closure;
use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Schemas\Components\Concerns\EntanglesStateWithSingularRelationship;
use Sylament\Schemas\Components\Contracts\CanEntangleWithSingularRelationships;
use Sylament\Support\Components\Concerns\HasAlignment;
use Sylament\Support\Components\Concerns\HasFromBreakpoint;
use Sylament\Support\Components\Concerns\HasVerticalAlignment;
use Sylament\Support\Enums\VerticalAlignment;
use Twig\Markup;

class Flex extends Component implements CanEntangleWithSingularRelationships
{
    use EntanglesStateWithSingularRelationship;
    use HasAlignment;
    use HasFromBreakpoint;
    use HasVerticalAlignment;

    protected string|Closure|null $template = '@SylamentSchemas/partials/Flex/index.html.twig';

    /**
     * @param array<Component|Action|ActionGroup|string|Markup>|Closure $schema
     */
    public static function make(array|Closure $schema): static
    {
        $flex = static::newInstance();

        $flex->components($schema);

        $flex->configure();

        return $flex;
    }

    /**
     * @return array<string, mixed>
     */
    public function getExtraTemplateData(): array
    {
        $verticalAlignment = $this->getVerticalAlignment();
        $children = [];

        foreach ($this->getChildSchema()?->getComponents(withHidden: false) ?? [] as $child) {
            $children[] = [
                'component' => $child,
                'isAction' => ($child instanceof Action) || ($child instanceof ActionGroup),
                'grows' => $child instanceof Component && $child->canGrow(),
            ];
        }

        return [
            'children' => $children,
            'fromBreakpoint' => $this->getFromBreakpoint() ?? 'default',
            'verticalAlignmentClass' => $verticalAlignment instanceof VerticalAlignment
                ? "sy-vertical-align-{$verticalAlignment->value}"
                : $verticalAlignment,
            'alignmentClass' => $this->getAlignmentClass(),
            'isDense' => $this->isDense(),
        ];
    }
}
