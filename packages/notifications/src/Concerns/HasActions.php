<?php

declare(strict_types=1);

namespace Sylament\Notifications\Concerns;

use Closure;
use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Support\Enums\Size;

use function Sylament\Support\wrap;

trait HasActions
{
    /**
     * @var array<Action|ActionGroup>|ActionGroup|Closure
     */
    protected array|ActionGroup|Closure $actions = [];

    /**
     * @param array<Action|ActionGroup>|ActionGroup|Closure $actions
     */
    public function actions(array|ActionGroup|Closure $actions): static
    {
        $this->actions = $actions;

        return $this;
    }

    /**
     * @return array<Action|ActionGroup>
     */
    public function getActions(): array
    {
        return array_map(
            fn (Action|ActionGroup $action): Action|ActionGroup => match (true) {
                $action instanceof Action => $action->defaultTemplate(Action::LINK_TEMPLATE)->defaultSize(Size::Small),
                $action instanceof ActionGroup => $action->defaultTriggerTemplate(ActionGroup::LINK_TEMPLATE)->defaultSize(Size::Small),
            },
            wrap($this->evaluate($this->actions)),
        );
    }
}
