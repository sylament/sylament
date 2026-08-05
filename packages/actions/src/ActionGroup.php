<?php

declare(strict_types=1);

namespace Sylament\Actions;

use Closure;
use Sylament\Actions\Concerns\BelongsToGroup;
use Sylament\Actions\Concerns\BelongsToLiveComponent;
use Sylament\Actions\Concerns\BelongsToSchemaComponent;
use Sylament\Actions\Concerns\BelongsToTable;
use Sylament\Actions\Concerns\CanBeHidden;
use Sylament\Actions\Concerns\CanBeOutlined;
use Sylament\Actions\Concerns\HasDropdown;
use Sylament\Actions\Concerns\HasGroupedIcon;
use Sylament\Actions\Concerns\HasLabel;
use Sylament\Actions\Concerns\HasSize;
use Sylament\Actions\Concerns\InteractsWithRecord;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Concerns\CanAccessProperties;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\Get;
use Sylament\Support\Components\Concerns\HasColor;
use Sylament\Support\Components\Concerns\HasExtraAttributes;
use Sylament\Support\Components\Concerns\HasIcon;
use Sylament\Support\Components\Concerns\HasIconSize;
use Sylament\Support\Components\Concerns\HasTooltip;
use Sylament\Support\Components\ViewComponent;
use Sylament\Support\Evaluation\ClosureParameter;
use Sylament\Support\Twig\ComponentAttributeBag;
use Twig\Markup;

use function Sylament\Support\filled;

class ActionGroup extends ViewComponent
{
    use BelongsToGroup;
    use BelongsToLiveComponent;
    use BelongsToSchemaComponent;
    use BelongsToTable;
    use CanAccessProperties;
    use CanBeHidden;
    use CanBeOutlined;
    use HasColor;
    use HasDropdown;
    use HasExtraAttributes;
    use HasGroupedIcon;
    use HasIcon;
    use HasIconSize;
    use HasLabel;
    use HasSize;
    use HasTooltip;
    use InteractsWithRecord;

    public const string DEFAULT_ICON = 'heroicons:ellipsis-vertical';

    public const string ICON_BUTTON_TEMPLATE = '@SylamentActions/partials/ActionGroup/IconButtonTrigger.html.twig';

    public const string BUTTON_TEMPLATE = '@SylamentActions/partials/ActionGroup/ButtonTrigger.html.twig';

    public const string GROUPED_TEMPLATE = '@SylamentActions/partials/ActionGroup/GroupedTrigger.html.twig';

    protected string $evaluationIdentifier = 'group';

    protected string|Closure|null $template = '@SylamentActions/partials/ActionGroup/index.html.twig';

    /**
     * @var array<Action|ActionGroup>
     */
    protected array $actions;

    /**
     * @var array<string, Action>
     */
    protected array $flatActions;

    protected string|Closure|null $triggerTemplate = null;

    protected string|Closure|null $defaultTriggerTemplate = null;

    /**
     * @var array<array<mixed>|Closure>
     */
    protected array $extraDropdownAttributes = [];

    /**
     * @param array<Action|ActionGroup> $actions
     */
    public static function make(array $actions = []): static
    {
        $group = static::newInstance();

        $group->actions($actions);

        $group->configure();

        return $group;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultTriggerTemplate(static::ICON_BUTTON_TEMPLATE);
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
                $action->defaultDropdownPlacement('right-start');

                $this->flatActions = [...$this->flatActions, ...$action->getFlatActions()];
            } else {
                $this->flatActions[$action->getName()] = $action;
            }

            $this->actions[] = $action;
        }

        return $this;
    }

    /**
     * @param array<Action|ActionGroup> $actions
     * @return array<Action|ActionGroup>
     */
    protected function stampTriggerTemplates(array $actions, bool $grouped): array
    {
        foreach ($actions as $action) {
            if ($action instanceof Action) {
                $action->defaultTemplate($grouped ? Action::GROUPED_TEMPLATE : Action::BUTTON_TEMPLATE);
            } else {
                $action->defaultTriggerTemplate($grouped ? self::GROUPED_TEMPLATE : self::BUTTON_TEMPLATE);
            }
        }

        return $actions;
    }

    /**
     * @return array<Action|ActionGroup>
     */
    public function getActions(): array
    {
        return $this->stampTriggerTemplates($this->actions, $this->hasDropdown());
    }

    public function getClone(): static
    {
        $clone = clone $this;

        $clone->cloneActions();

        return $clone;
    }

    protected function cloneActions(): void
    {
        $this->actions(array_map(
            static fn (Action|ActionGroup $action): Action|ActionGroup => $action->getClone(),
            $this->actions,
        ));
    }

    /**
     * @return array<string, Action>
     */
    public function getFlatActions(): array
    {
        return $this->flatActions;
    }

    /**
     * @return array<array<Action|ActionGroup>>
     */
    public function getDropdownActionLists(): array
    {
        $actionLists = [];
        $singleActions = [];

        foreach ($this->getActions() as $action) {
            if (! $action->isVisible()) {
                continue;
            }

            if ($action instanceof self && ! $action->hasDropdown()) {
                if ($singleActions !== []) {
                    $actionLists[] = $singleActions;
                    $singleActions = [];
                }

                $actionLists[] = array_filter($action->getActions(), static fn (Action|ActionGroup $childAction): bool => $childAction->isVisible())
                        |> array_values(...)
                        |> (fn (array $x): array => $this->stampTriggerTemplates($x, true, ));
            } else {
                $singleActions[] = $action;
            }
        }

        if ($singleActions !== []) {
            $actionLists[] = $singleActions;
        }

        return $actionLists;
    }

    public function triggerTemplate(string|Closure|null $template): static
    {
        $this->triggerTemplate = $template;

        return $this;
    }

    public function defaultTriggerTemplate(string|Closure|null $template): static
    {
        $this->defaultTriggerTemplate = $template;

        return $this;
    }

    public function getTriggerTemplate(): string
    {
        return $this->evaluate($this->triggerTemplate)
            ?? $this->evaluate($this->defaultTriggerTemplate)
            ?? static::ICON_BUTTON_TEMPLATE;
    }

    /**
     * @param array<mixed>|Closure $attributes
     */
    public function extraDropdownAttributes(array|Closure $attributes, bool $merge = false): static
    {
        if ($merge) {
            $this->extraDropdownAttributes[] = $attributes;
        } else {
            $this->extraDropdownAttributes = [$attributes];
        }

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getExtraDropdownAttributes(): array
    {
        $attributeBag = new ComponentAttributeBag();

        foreach ($this->extraDropdownAttributes as $extraDropdownAttributes) {
            $attributeBag = $attributeBag->merge($this->evaluate($extraDropdownAttributes) ?: []);
        }

        return $attributeBag->getAttributes();
    }

    public function getExtraDropdownAttributeBag(): ComponentAttributeBag
    {
        return new ComponentAttributeBag($this->getExtraDropdownAttributes());
    }

    public function hasExtraDropdownAttributes(): bool
    {
        return $this->extraDropdownAttributes !== [];
    }

    public function iconButton(): static
    {
        return $this->triggerTemplate(static::ICON_BUTTON_TEMPLATE);
    }

    public function button(): static
    {
        return $this->triggerTemplate(static::BUTTON_TEMPLATE);
    }

    public function isIconButton(): bool
    {
        return $this->getTriggerTemplate() === static::ICON_BUTTON_TEMPLATE;
    }

    public function isButton(): bool
    {
        return $this->getTriggerTemplate() === static::BUTTON_TEMPLATE;
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

    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): ?ClosureParameter
    {
        return match ($parameterName) {
            'record' => ClosureParameter::make($this->getRecord()),
            'liveComponent' => ClosureParameter::make($this->getLiveComponent()),
            'schema' => ClosureParameter::make($this->getSchemaContainer()),
            'schemaComponent', 'component' => ClosureParameter::make($this->getSchemaComponent()),
            'schemaGet', 'get' => ClosureParameter::make($this->getSchemaComponentOrFail()->makeGetUtility()),
            'schemaComponentState', 'state' => ClosureParameter::make($this->getSchemaComponentState()),
            'schemaState' => ClosureParameter::make($this->getSchemaState()),
            default => parent::resolveDefaultClosureDependencyForEvaluationByName($parameterName),
        };
    }

    protected function resolveDefaultClosureDependencyForEvaluationByType(string $parameterType): ?ClosureParameter
    {
        return match ($parameterType) {
            Get::class => ClosureParameter::make($this->getSchemaComponentOrFail()->makeGetUtility()),
            default => parent::resolveDefaultClosureDependencyForEvaluationByType($parameterType),
        };
    }

    public function getSchemaComponentState(): mixed
    {
        $schemaContainer = $this->getSchemaContainer();

        while ($schemaContainer instanceof Schema) {
            $parentComponent = $schemaContainer->getParentComponent();

            if (!$parentComponent instanceof Component) {
                break;
            }

            if ($parentComponent->hasStatePath()) {
                return $parentComponent->getState();
            }

            $schemaContainer = $parentComponent->getContainer();
        }

        return $this->getSchemaComponent()?->getState();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getSchemaState(): ?array
    {
        $schemaContainer = $this->getSchemaContainer();

        while ($schemaContainer instanceof Schema) {
            if (filled($schemaContainer->getStatePath(isAbsolute: false))) {
                return $schemaContainer->getState();
            }

            $parentComponent = $schemaContainer->getParentComponent();

            if (!$parentComponent instanceof Component) {
                return $schemaContainer->getState();
            }

            $schemaContainer = $parentComponent->getContainer();
        }

        return null;
    }
}
