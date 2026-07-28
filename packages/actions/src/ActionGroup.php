<?php

declare(strict_types=1);

namespace Sylament\Actions;

use Sylament\Schemas\Schema;
use Sylament\Schemas\Components\Component;
use Closure;
use LogicException;
use Sylament\Actions\Concerns\BelongsToGroup;
use Sylament\Actions\Concerns\BelongsToLiveComponent;
use Sylament\Actions\Concerns\BelongsToSchemaComponent;
use Sylament\Actions\Concerns\CanBeHidden;
use Sylament\Actions\Concerns\CanBeOutlined;
use Sylament\Actions\Concerns\HasDropdown;
use Sylament\Actions\Concerns\HasGroupedIcon;
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
use Sylament\Support\Evaluation\ClosureParameter;
use Sylament\Support\Twig\ComponentAttributeBag;
use Twig\Environment;
use Twig\Markup;

use function Sylament\Support\filled;

class ActionGroup extends ViewComponent
{
    use BelongsToGroup;
    use BelongsToLiveComponent;
    use BelongsToSchemaComponent;
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

    public const string ICON_BUTTON_TEMPLATE = '@SylamentActions/components/action-group/icon-button-trigger.html.twig';

    public const string BUTTON_TEMPLATE = '@SylamentActions/components/action-group/button-trigger.html.twig';

    protected string $evaluationIdentifier = 'group';

    protected string|Closure|null $template = '@SylamentActions/components/action-group.html.twig';

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
                $action->defaultTemplate($grouped ? Action::GROUPED_TEMPLATE : Action::BUTTON_TEMPLATE);
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

    public function render(Environment $twig): ?string
    {
        if (array_any($this->getActions(), fn (Action|ActionGroup $action): bool => $action instanceof self && $action->isVisible())) {
            throw new LogicException('Submenu rendering is not supported yet: an ActionGroup cannot be rendered inside another ActionGroup.');
        }

        return parent::render($twig);
    }

    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): ?ClosureParameter
    {
        return match ($parameterName) {
            'record' => ClosureParameter::make($this->getRecord()),
            'liveComponent' => ClosureParameter::make($this->getLiveComponent()),
            'schema' => ClosureParameter::make($this->getSchemaContainer()),
            'schemaComponent', 'component' => ClosureParameter::make($this->getSchemaComponent()),
            'schemaGet', 'get' => ClosureParameter::make($this->getSchemaComponent()->makeGetUtility()), // @phpstan-ignore method.nonObject (Filament-faithful: fatals without a schema component)
            'schemaComponentState', 'state' => ClosureParameter::make($this->getSchemaComponentState()),
            'schemaState' => ClosureParameter::make($this->getSchemaState()),
            default => parent::resolveDefaultClosureDependencyForEvaluationByName($parameterName),
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

    public function getSchemaState(): mixed
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
