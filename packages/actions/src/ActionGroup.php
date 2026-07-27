<?php

declare(strict_types=1);

namespace Sylament\Actions;

use Sylament\Actions\Concerns\BelongsToGroup;
use Sylament\Actions\Concerns\BelongsToLiveComponent;
use Sylament\Actions\Concerns\CanBeHidden;
use Sylament\Actions\Concerns\HasDropdown;
use Sylament\Actions\Concerns\HasLabel;
use Sylament\Actions\Concerns\HasSize;
use Sylament\Actions\Concerns\InteractsWithRecord;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Support\Components\Concerns\HasColor;
use Sylament\Support\Components\Concerns\HasExtraAttributes;
use Sylament\Support\Components\Concerns\HasIcon;
use Sylament\Support\Components\Concerns\HasIconSize;
use Sylament\Support\Components\Concerns\HasTooltip;
use Sylament\Support\Components\ViewComponent;
use Twig\Markup;

class ActionGroup extends ViewComponent
{
    use BelongsToGroup;
    use BelongsToLiveComponent;
    use CanBeHidden;
    use HasColor;
    use HasDropdown;
    use HasExtraAttributes;
    use HasIcon;
    use HasIconSize;
    use HasLabel;
    use HasSize;
    use HasTooltip;
    use InteractsWithRecord;

    public const string DEFAULT_ICON = 'heroicons:ellipsis-vertical';

    protected string $evaluationIdentifier = 'group';

    /**
     * @var array<Action|ActionGroup>
     */
    protected array $actions = [];

    /**
     * @var array<string, Action>
     */
    protected array $flatActions = [];

    /**
     * @param array<Action|ActionGroup> $actions
     */
    final public function __construct(array $actions)
    {
        $this->actions($actions);
    }

    /**
     * @param array<Action|ActionGroup> $actions
     */
    public static function make(array $actions = []): static
    {
        $group = new static($actions);

        $group->configure();

        return $group;
    }

    /**
     * @param array<Action|ActionGroup> $actions
     */
    public function actions(array $actions): static
    {
        $this->actions = [];
        $this->flatActions = [];

        foreach ($actions as $action) {
            $action->group($this);

            if ($action instanceof self) {
                $this->flatActions = [...$this->flatActions, ...$action->getFlatActions()];
            } else {
                $this->flatActions[$action->getName()] = $action;
            }

            $this->actions[] = $action;
        }

        return $this;
    }

    /**
     * @return array<Action|ActionGroup>
     */
    public function getActions(): array
    {
        $grouped = $this->hasDropdown();

        foreach ($this->actions as $action) {
            if ($action instanceof Action) {
                $action->defaultTemplate($grouped ? Action::GROUPED_TEMPLATE : null);
            }
        }

        return $this->actions;
    }

    /**
     * @return array<string, Action>
     */
    public function getFlatActions(): array
    {
        return $this->flatActions;
    }

    public function isHidden(): bool
    {
        if ($this->evaluate($this->isHidden)) {
            return true;
        }

        if (! $this->evaluate($this->isVisible)) {
            return true;
        }

        return array_all($this->actions, fn (Action|ActionGroup $action): bool => !$action->isVisible());
    }

    public function liveComponent(?InteractsWithActions $liveComponent = null): static
    {
        $this->liveComponent = $liveComponent;

        foreach ($this->actions as $action) {
            $action->liveComponent($liveComponent);
        }

        return $this;
    }

    public function getRecord(): mixed
    {
        return $this->evaluate($this->record) ?? $this->getGroup()?->getRecord();
    }

    public function getLabel(): string|Markup|null
    {
        return $this->evaluate($this->label) ?? 'Actions';
    }
}
