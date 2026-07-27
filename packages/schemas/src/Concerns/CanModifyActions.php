<?php

declare(strict_types=1);

namespace Sylament\Schemas\Concerns;

use Closure;
use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use TypeError;

trait CanModifyActions
{
    protected ?Closure $modifyActionsUsing = null;

    protected ?Closure $modifyActionGroupsUsing = null;

    public function modifyActionsUsing(?Closure $callback): static
    {
        $this->modifyActionsUsing = $callback;

        return $this;
    }

    public function modifyActionGroupsUsing(?Closure $callback): static
    {
        $this->modifyActionGroupsUsing = $callback;

        return $this;
    }

    public function modifyAction(Action $action): Action
    {
        if (! $this->modifyActionsUsing) {
            return $action;
        }

        $modifiedAction = ($this->modifyActionsUsing)($action) ?? $action;

        if (! $modifiedAction instanceof Action) {
            throw new TypeError(sprintf('%s::modifyActionsUsing() must return an %s instance or null.', static::class, Action::class));
        }

        return $modifiedAction;
    }

    public function modifyActionGroup(ActionGroup $actionGroup): ActionGroup
    {
        if (! $this->modifyActionGroupsUsing) {
            return $actionGroup;
        }

        $modifiedActionGroup = ($this->modifyActionGroupsUsing)($actionGroup) ?? $actionGroup;

        if (! $modifiedActionGroup instanceof ActionGroup) {
            throw new TypeError(sprintf('%s::modifyActionGroupsUsing() must return an %s instance or null.', static::class, ActionGroup::class));
        }

        return $modifiedActionGroup;
    }
}
