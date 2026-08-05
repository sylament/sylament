<?php

declare(strict_types=1);

namespace Sylament\Actions;

use Closure;
use Sylament\Actions\Concerns\BelongsToGroup;
use Sylament\Actions\Concerns\BelongsToLiveComponent;
use Sylament\Actions\Concerns\BelongsToSchemaComponent;
use Sylament\Actions\Concerns\BelongsToTable;
use Sylament\Actions\Concerns\CanBeDisabled;
use Sylament\Actions\Concerns\CanBeHidden;
use Sylament\Actions\Concerns\CanBeMounted;
use Sylament\Actions\Concerns\CanBeOutlined;
use Sylament\Actions\Concerns\CanCallParentAction;
use Sylament\Actions\Concerns\CanClose;
use Sylament\Actions\Concerns\CanNotify;
use Sylament\Actions\Concerns\CanOpenModal;
use Sylament\Actions\Concerns\CanOpenUrl;
use Sylament\Actions\Concerns\CanRequireConfirmation;
use Sylament\Actions\Concerns\CanSubmitForm;
use Sylament\Actions\Concerns\HasAction;
use Sylament\Actions\Concerns\HasArguments;
use Sylament\Actions\Concerns\HasData;
use Sylament\Actions\Concerns\HasExtraModalOverlayAttributes;
use Sylament\Actions\Concerns\HasExtraModalWindowAttributes;
use Sylament\Actions\Concerns\HasGroupedIcon;
use Sylament\Actions\Concerns\HasLabel;
use Sylament\Actions\Concerns\HasLifecycleHooks;
use Sylament\Actions\Concerns\HasMountableArguments;
use Sylament\Actions\Concerns\HasName;
use Sylament\Actions\Concerns\HasParentActions;
use Sylament\Actions\Concerns\HasSchema;
use Sylament\Actions\Concerns\HasSize;
use Sylament\Actions\Concerns\InteractsWithRecord;
use Sylament\Actions\Enums\ActionStatus;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Concerns\CanAccessProperties;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\Get;
use Sylament\Schemas\State\Set;
use Sylament\Support\Components\Concerns\HasColor;
use Sylament\Support\Components\Concerns\HasExtraAttributes;
use Sylament\Support\Components\Concerns\HasIcon;
use Sylament\Support\Components\Concerns\HasIconPosition;
use Sylament\Support\Components\Concerns\HasIconSize;
use Sylament\Support\Components\Concerns\HasTooltip;
use Sylament\Support\Components\ViewComponent;
use Sylament\Support\Evaluation\ClosureParameter;
use Sylament\Support\Exceptions\Cancel;
use Sylament\Support\Exceptions\Halt;

use function Sylament\Support\filled;

class Action extends ViewComponent
{
    use BelongsToGroup;
    use BelongsToLiveComponent;
    use BelongsToSchemaComponent;
    use BelongsToTable;
    use CanAccessProperties;
    use CanBeDisabled;
    use CanBeHidden;
    use CanBeMounted;
    use CanBeOutlined;
    use CanCallParentAction;
    use CanClose;
    use CanNotify;
    use CanOpenModal;
    use CanOpenUrl;
    use CanRequireConfirmation;
    use CanSubmitForm;
    use HasAction;
    use HasArguments;
    use HasColor;
    use HasData;
    use HasExtraAttributes;
    use HasExtraModalOverlayAttributes;
    use HasExtraModalWindowAttributes;
    use HasGroupedIcon;
    use HasIcon;
    use HasIconPosition;
    use HasIconSize;
    use HasLabel;
    use HasLifecycleHooks;
    use HasMountableArguments;
    use HasName;
    use HasParentActions;
    use HasSchema;
    use HasSize;
    use HasTooltip;
    use InteractsWithRecord;

    protected string $evaluationIdentifier = 'action';

    public const string BADGE_TEMPLATE = '@SylamentActions/partials/Action/Badge.html.twig';

    public const string GROUPED_TEMPLATE = '@SylamentActions/partials/Action/Grouped.html.twig';

    public const string BUTTON_TEMPLATE = '@SylamentActions/partials/Action/Button.html.twig';

    public const string ICON_BUTTON_TEMPLATE = '@SylamentActions/partials/Action/IconButton.html.twig';

    public const string LINK_TEMPLATE = '@SylamentActions/partials/Action/Link.html.twig';

    protected string|Closure|null $defaultTemplate = self::BUTTON_TEMPLATE;

    protected ?ActionStatus $status = null;

    protected ?int $nestingIndex = null;

    protected ?Action $parentAction = null;

    public static function getDefaultName(): ?string
    {
        return null;
    }

    public static function make(?string $name = null): static
    {
        $action = static::newInstance();

        $action->name($name ?? static::getDefaultName());

        $action->configure();

        return $action;
    }

    public function isBadge(): bool
    {
        return $this->getTemplate() === static::BADGE_TEMPLATE;
    }

    public function badge(): static
    {
        return $this->template(static::BADGE_TEMPLATE);
    }

    public function button(): static
    {
        return $this->template(static::BUTTON_TEMPLATE);
    }

    public function isButton(): bool
    {
        return $this->getTemplate() === static::BUTTON_TEMPLATE;
    }

    public function grouped(): static
    {
        return $this->template(static::GROUPED_TEMPLATE);
    }

    public function iconButton(): static
    {
        return $this->template(static::ICON_BUTTON_TEMPLATE);
    }

    public function isIconButton(): bool
    {
        return $this->getTemplate() === static::ICON_BUTTON_TEMPLATE;
    }

    public function link(): static
    {
        return $this->template(static::LINK_TEMPLATE);
    }

    public function isLink(): bool
    {
        return $this->getTemplate() === static::LINK_TEMPLATE;
    }

    public function getLiveComponentCallMountedActionName(): string
    {
        return 'callMountedAction';
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function call(array $parameters = []): mixed
    {
        return $this->evaluate($this->getAction(), $parameters);
    }

    public function cancel(): void
    {
        throw new Cancel();
    }

    public function halt(): void
    {
        throw new Halt();
    }

    public function success(): void
    {
        $this->status = ActionStatus::Success;
    }

    public function failure(): void
    {
        $this->status = ActionStatus::Failure;
    }

    public function getStatus(): ActionStatus
    {
        return $this->status ?? ActionStatus::Success;
    }

    public function nestingIndex(?int $index): static
    {
        $this->nestingIndex = $index;

        return $this;
    }

    public function getNestingIndex(): ?int
    {
        return $this->nestingIndex;
    }

    public function parentAction(?self $action): static
    {
        $this->parentAction = $action;

        return $this;
    }

    public function getParentAction(): ?self
    {
        return $this->parentAction;
    }

    public function getRecord(): mixed
    {
        return $this->evaluate($this->record) ?? $this->getGroup()?->getRecord();
    }

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        $context = [];

        if (filled($recordKey = $this->resolveRecordKey($this->getRecord()))) {
            $context['recordKey'] = $recordKey;
        }

        if ($this->parentAction instanceof self) {
            return $context;
        }

        if ($this->hasTable()) {
            $context['table'] = true;
        }

        if (filled($schemaComponentKey = ($this->getSchemaContainer() ?? $this->getSchemaComponent())?->getKey())) {
            $context['schemaComponent'] = $schemaComponentKey;
        }

        return $context;
    }

    public function getClone(): static
    {
        return clone $this;
    }

    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): ?ClosureParameter
    {
        return match ($parameterName) {
            'record' => ClosureParameter::make($this->getRecord()),
            'arguments' => ClosureParameter::make($this->getArguments()),
            'data' => ClosureParameter::make($this->getData()),
            'liveComponent' => ClosureParameter::make($this->getLiveComponent()),
            'schema' => ClosureParameter::make($this->getSchemaContainer()),
            'schemaComponent', 'component' => ClosureParameter::make($this->getSchemaComponent()),
            'schemaGet', 'get' => ClosureParameter::make($this->getSchemaComponentOrFail()->makeGetUtility()->skipComponentsChildContainersWhileSearching(false)),
            'schemaSet', 'set' => ClosureParameter::make($this->getSchemaComponentOrFail()->makeSetUtility()->skipComponentsChildContainersWhileSearching(false)),
            'schemaComponentState', 'state' => ClosureParameter::make($this->getSchemaComponentState()),
            'schemaState' => ClosureParameter::make($this->getSchemaState()),
            default => parent::resolveDefaultClosureDependencyForEvaluationByName($parameterName),
        };
    }

    protected function resolveDefaultClosureDependencyForEvaluationByType(string $parameterType): ?ClosureParameter
    {
        return match ($parameterType) {
            Get::class => ClosureParameter::make($this->getSchemaComponentOrFail()->makeGetUtility()->skipComponentsChildContainersWhileSearching(false)),
            Set::class => ClosureParameter::make($this->getSchemaComponentOrFail()->makeSetUtility()->skipComponentsChildContainersWhileSearching(false)),
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
