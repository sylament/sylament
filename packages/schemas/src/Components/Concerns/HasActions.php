<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Concerns;

use Closure;
use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;

use function Sylament\Support\blank;
use function Sylament\Support\wrap;

trait HasActions
{
    /**
     * @var array<string, Action>|null
     */
    protected ?array $cachedActions = null;

    /**
     * @var array<Action|Closure>
     */
    protected array $actions = [];

    protected ?Action $action = null;

    /**
     * @param array<Action|Closure> $actions
     */
    public function registerActions(array $actions): static
    {
        $this->actions = [
            ...$this->actions,
            ...$actions,
        ];

        return $this;
    }

    public function action(?Action $action): static
    {
        $this->action = $action;

        if ($action instanceof Action) {
            $this->registerActions([$action]);
        }

        return $this;
    }

    public function getAction(?string $name = null): ?Action
    {
        if (blank($name)) {
            return $this->action;
        }

        return $this->getActions()[$name] ?? null;
    }

    /**
     * @return array<string, Action>
     */
    public function getActions(): array
    {
        return $this->cachedActions ??= $this->cacheActions();
    }

    /**
     * @return array<string, Action>
     */
    public function cacheActions(): array
    {
        $this->cachedActions = [];

        foreach ($this->getDefaultActions() as $defaultAction) {
            foreach (wrap($this->evaluate($defaultAction)) as $action) {
                $this->cachedActions[$action->getName()] = $this->prepareAction($action);
            }
        }

        foreach ($this->actions as $registeredAction) {
            foreach (wrap($this->evaluate($registeredAction)) as $action) {
                $this->cachedActions[$action->getName()] = $this->prepareAction($action);
            }
        }

        return $this->cachedActions;
    }

    /**
     * @return array<Action|Closure>
     */
    public function getDefaultActions(): array
    {
        return [];
    }

    public function prepareAction(Action $action): Action
    {
        return $action->schemaComponent($this);
    }

    public function prepareActionGroup(ActionGroup $group): ActionGroup
    {
        return $group->schemaComponent($this);
    }

    public function hasAction(string $name): bool
    {
        return array_key_exists($name, $this->getActions());
    }
}
