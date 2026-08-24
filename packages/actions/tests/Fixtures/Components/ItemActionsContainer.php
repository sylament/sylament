<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Components;

use Sylament\Actions\Action;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Contracts\HasExtraItemActions;

/**
 * Stands in for the forms Repeater's extra item actions, so the item-state arm of
 * `Action::getSchemaState()` can be exercised without the `forms` package.
 */
class ItemActionsContainer extends Component implements HasExtraItemActions
{
    /**
     * @var array<string, Action>
     */
    protected array $extraItemActions = [];

    public static function make(string $name): static
    {
        return new static()->statePath($name);
    }

    /**
     * @param array<Action> $actions
     */
    public function extraItemActions(array $actions): static
    {
        foreach ($actions as $action) {
            $this->extraItemActions[$action->getName()] = $this->prepareAction($action);
        }

        return $this;
    }

    public function getExtraItemActions(): array
    {
        return $this->extraItemActions;
    }

    public function getItemState(string $key): array
    {
        return ['key' => $key, 'title' => "Item {$key}"];
    }
}
