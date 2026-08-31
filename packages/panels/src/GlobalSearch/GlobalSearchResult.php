<?php

declare(strict_types=1);

namespace Sylament\Panels\GlobalSearch;

use Sylament\Actions\Action;
use Sylament\Support\Enums\Size;
use Twig\Markup;

class GlobalSearchResult
{
    /**
     * @var array<Action>
     */
    public readonly array $actions;

    /**
     * @param array<string, string>|list<string> $details
     * @param array<Action> $actions
     */
    public function __construct(
        public readonly string|Markup $title,
        public readonly string $url,
        public readonly array $details = [],
        array $actions = [],
    ) {
        $this->actions = array_map(
            fn (Action $action): Action => $action
                ->defaultTemplate(Action::LINK_TEMPLATE)
                ->defaultSize(Size::Small),
            $actions,
        );
    }

    /**
     * @return array<Action>
     */
    public function getVisibleActions(): array
    {
        return array_filter($this->actions, fn (Action $action): bool => $action->isVisible());
    }

    public function hasDetailLabels(): bool
    {
        return $this->details !== [] && ! array_is_list($this->details);
    }
}
