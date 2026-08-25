<?php

declare(strict_types=1);

namespace Sylament\Actions;

use BackedEnum;
use Closure;
use Doctrine\ORM\QueryBuilder;
use Sylament\Actions\Concerns\BelongsToGroup;
use Sylament\Actions\Concerns\BelongsToLiveComponent;
use Sylament\Actions\Concerns\BelongsToSchemaComponent;
use Sylament\Actions\Concerns\BelongsToTable;
use Sylament\Actions\Concerns\CanBeDisabled;
use Sylament\Actions\Concerns\CanBeHidden;
use Sylament\Actions\Concerns\CanBeMarkedAsRead;
use Sylament\Actions\Concerns\CanBeMounted;
use Sylament\Actions\Concerns\CanBeOutlined;
use Sylament\Actions\Concerns\CanCallParentAction;
use Sylament\Actions\Concerns\CanClose;
use Sylament\Actions\Concerns\CanDeselectRecordsAfterCompletion;
use Sylament\Actions\Concerns\CanDispatchEvent;
use Sylament\Actions\Concerns\CanFetchSelectedRecords;
use Sylament\Actions\Concerns\CanNotify;
use Sylament\Actions\Concerns\CanOpenModal;
use Sylament\Actions\Concerns\CanOpenUrl;
use Sylament\Actions\Concerns\CanRedirect;
use Sylament\Actions\Concerns\CanRequireConfirmation;
use Sylament\Actions\Concerns\CanSubmitForm;
use Sylament\Actions\Concerns\CanUseDatabaseTransactions;
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
use Sylament\Actions\Concerns\HasWizard;
use Sylament\Actions\Concerns\InteractsWithRecord;
use Sylament\Actions\Concerns\InteractsWithSelectedRecords;
use Sylament\Actions\Enums\ActionStatus;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Concerns\CanAccessAssociations;
use Sylament\Schemas\Components\Concerns\CanAccessProperties;
use Sylament\Schemas\Components\Contracts\HasExtraItemActions;
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
use Sylament\Support\Contracts\ScalableIcon;
use Sylament\Support\Enums\IconPosition;
use Sylament\Support\Enums\IconSize;
use Sylament\Support\Enums\Size;
use Sylament\Support\Evaluation\ClosureParameter;
use Sylament\Support\Exceptions\Cancel;
use Sylament\Support\Exceptions\Halt;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Twig\Markup;

use function Sylament\Support\filled;
use function Sylament\Support\get_record_label;
use function Sylament\Support\plural;

class Action extends ViewComponent
{
    use BelongsToGroup;
    use BelongsToLiveComponent;
    use BelongsToSchemaComponent;
    use BelongsToTable;
    use CanAccessAssociations;
    use CanAccessProperties;
    use CanBeDisabled;
    use CanBeHidden;
    use CanBeMarkedAsRead;
    use CanBeMounted;
    use CanBeOutlined;
    use CanCallParentAction;
    use CanClose;
    use CanDeselectRecordsAfterCompletion;
    use CanDispatchEvent;
    use CanFetchSelectedRecords;
    use CanNotify;
    use CanOpenModal;
    use CanOpenUrl;
    use CanRedirect;
    use CanRequireConfirmation;
    use CanSubmitForm;
    use CanUseDatabaseTransactions;
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
    use HasWizard;
    use InteractsWithRecord;
    use InteractsWithSelectedRecords;

    protected string $evaluationIdentifier = 'action';

    public const string BADGE_TEMPLATE = '@SylamentActions/partials/Action/Badge.html.twig';

    public const string GROUPED_TEMPLATE = '@SylamentActions/partials/Action/Grouped.html.twig';

    public const string BUTTON_TEMPLATE = '@SylamentActions/partials/Action/Button.html.twig';

    public const string ICON_BUTTON_TEMPLATE = '@SylamentActions/partials/Action/IconButton.html.twig';

    public const string LINK_TEMPLATE = '@SylamentActions/partials/Action/Link.html.twig';

    protected string|Closure|null $defaultTemplate = self::BUTTON_TEMPLATE;

    protected bool|Closure $isBulk = false;

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

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $icon = $this->getIcon();

        if ($icon instanceof BackedEnum && !$icon instanceof ScalableIcon) {
            $icon = (string) $icon->value;
        }

        $iconSize = $this->getIconSize();
        $size = $this->getSize();

        return [
            'name' => $this->getName(),
            'color' => $this->getColor(),
            'event' => $this->getEvent(),
            'eventData' => $this->getEventData(),
            'dispatchDirection' => $this->getDispatchDirection(),
            'dispatchToComponent' => $this->getDispatchToComponent(),
            'extraAttributes' => $this->getExtraAttributes(),
            'icon' => $icon,
            'iconPosition' => $this->getIconPosition()->value,
            'iconSize' => $iconSize instanceof IconSize ? $iconSize->value : $iconSize,
            'isOutlined' => $this->isOutlined(),
            'isDisabled' => $this->isDisabled(),
            'label' => $this->getLabel(),
            'shouldClose' => $this->shouldClose(),
            'shouldMarkAsRead' => $this->shouldMarkAsRead(),
            'shouldMarkAsUnread' => $this->shouldMarkAsUnread(),
            'shouldOpenUrlInNewTab' => $this->shouldOpenUrlInNewTab(),
            'size' => $size instanceof Size ? $size->value : $size,
            'template' => $this->getTemplate(),
            'tooltip' => $this->getTooltip(),
            'url' => $this->getUrl(),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        $name = $data['name'] ?? null;
        $static = static::make(is_string($name) ? $name : null);

        $template = $data['template'] ?? null;

        if (in_array($template, [
            static::BADGE_TEMPLATE,
            static::BUTTON_TEMPLATE,
            static::GROUPED_TEMPLATE,
            static::ICON_BUTTON_TEMPLATE,
            static::LINK_TEMPLATE,
        ], true)) {
            $static->template($template);
        }

        $size = $data['size'] ?? null;

        if ($size instanceof Size || is_string($size)) {
            $static->size($size);
        }

        $static->close((bool) ($data['shouldClose'] ?? false));

        $color = $data['color'] ?? null;
        $static->color(match (true) {
            is_string($color) => $color,
            is_array($color) => array_filter($color, is_string(...)),
            default => null,
        });

        $static->disabled((bool) ($data['isDisabled'] ?? false));

        $direction = $data['dispatchDirection'] ?? null;
        $event = $data['event'] ?? null;
        $event = is_string($event) ? $event : null;

        $eventData = $data['eventData'] ?? [];
        $eventData = is_array($eventData) ? $eventData : [];

        $to = $data['dispatchToComponent'] ?? null;

        match (true) {
            $direction === 'self' => $static->dispatchSelf($event, $eventData),
            $direction === 'to' && is_string($to) => $static->dispatchTo($to, $event, $eventData),
            default => $static->dispatch($event, $eventData),
        };

        $extraAttributes = $data['extraAttributes'] ?? [];
        $static->extraAttributes(is_array($extraAttributes) ? $extraAttributes : []);

        $icon = $data['icon'] ?? null;
        $static->icon(is_string($icon) || $icon instanceof BackedEnum || $icon instanceof Markup ? $icon : null);

        $iconPosition = $data['iconPosition'] ?? null;
        $static->iconPosition($iconPosition instanceof IconPosition || is_string($iconPosition) ? $iconPosition : null);

        $iconSize = $data['iconSize'] ?? null;
        $static->iconSize($iconSize instanceof IconSize || is_string($iconSize) ? $iconSize : null);

        $label = $data['label'] ?? null;
        $static->label(is_string($label) || $label instanceof Markup ? $label : null);

        $static->markAsRead((bool) ($data['shouldMarkAsRead'] ?? false));
        $static->markAsUnread((bool) ($data['shouldMarkAsUnread'] ?? false));
        $static->outlined((bool) ($data['isOutlined'] ?? false));

        $url = $data['url'] ?? null;
        $static->url(is_string($url) ? $url : null, (bool) ($data['shouldOpenUrlInNewTab'] ?? false));

        $tooltip = $data['tooltip'] ?? null;
        $static->tooltip(is_string($tooltip) || $tooltip instanceof Markup ? $tooltip : null);

        return $static;
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
        try {
            return $this->evaluate($this->getAction(), $parameters);
        } finally {
            if ($this->shouldDeselectRecordsAfterCompletion() && ($host = $this->getLiveComponentOrNull()) instanceof InteractsWithTable) {
                $host->deselectAllTableRecords();
            }
        }
    }

    public function cancel(bool $shouldRollBackDatabaseTransaction = false): void
    {
        throw new Cancel()->rollBackDatabaseTransaction($shouldRollBackDatabaseTransaction);
    }

    public function halt(bool $shouldRollBackDatabaseTransaction = false): void
    {
        throw new Halt()->rollBackDatabaseTransaction($shouldRollBackDatabaseTransaction);
    }

    public function bulk(bool|Closure $condition = true): static
    {
        $this->isBulk = $condition;

        return $this;
    }

    public function isBulk(): bool
    {
        return (bool) $this->evaluate($this->isBulk);
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
        if ($this->status instanceof ActionStatus) {
            return $this->status;
        }

        if (! $this->canAccessSelectedRecords()) {
            return ActionStatus::Success;
        }

        if ($this->successfulSelectedRecordsCount === $this->totalSelectedRecordsCount) {
            return ActionStatus::Success;
        }

        return ActionStatus::Failure;
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

    public function getRecord(bool $withDefault = true): mixed
    {
        $record = $this->evaluate($this->record);

        if (is_object($record) || filled($record)) {
            return $this->ensureCorrectRecordType($record);
        }

        $record = $this->getGroup()?->getRecord()
            ?? $this->getSchemaContainer()?->getRecord()
            ?? $this->getSchemaComponent()?->getRecord();

        if ($record !== null) {
            return $this->ensureCorrectRecordType($record);
        }

        if (! $withDefault) {
            return null;
        }

        return $this->ensureCorrectRecordType($this->getLiveComponentOrNull()?->getDefaultActionRecord($this));
    }

    /**
     * @return class-string|null
     */
    public function getRecordClass(bool $withDefault = true): ?string
    {
        $class = $this->getCustomRecordClass()
            ?? $this->getTable()?->getRecordClass();

        if ($class !== null) {
            return $class;
        }

        $record = $this->getRecord($withDefault);

        if (is_object($record)) {
            return $record::class;
        }

        $class = $this->getGroup()?->getRecordClass()
            ?? $this->getSchemaContainer()?->getRecordClass()
            ?? $this->getSchemaComponent()?->getRecordClass();

        if ($class !== null || ! $withDefault) {
            return $class;
        }

        return $this->getLiveComponentOrNull()?->getDefaultActionRecordClass($this);
    }

    public function getRecordLabel(): ?string
    {
        $label = $this->getCustomRecordLabel();

        if (filled($label)) {
            return $label;
        }

        $label = $this->getTable()?->getRecordLabel();

        if (filled($label)) {
            return $label;
        }

        $class = $this->getRecordClass();
        $host = $this->getLiveComponentOrNull();

        if ($class === null) {
            return $host?->getDefaultActionRecordLabel($this);
        }

        if ($class === $host?->getDefaultActionRecordClass($this)) {
            return $host->getDefaultActionRecordLabel($this) ?? get_record_label($class);
        }

        return get_record_label($class);
    }

    public function getPluralRecordLabel(): ?string
    {
        $label = $this->getCustomPluralRecordLabel();

        if (filled($label)) {
            return $label;
        }

        $label = $this->getTable()?->getPluralRecordLabel();

        if (filled($label)) {
            return $label;
        }

        $singular = $this->getRecordLabel();

        return $singular === null ? null : plural($singular);
    }

    public function getRecordTitle(?object $record = null): ?string
    {
        $record ??= $this->getRecordObject();

        if ($record !== null) {
            $title = $this->getCustomRecordTitle($record);

            if (filled($title)) {
                return $title;
            }

            $title = $this->getTable()?->getRecordTitle($record);

            if (filled($title)) {
                return $title;
            }
        }

        return $this->getLiveComponentOrNull()?->getDefaultActionRecordTitle($this)
            ?? $this->getRecordLabel();
    }

    public function getRecordTitleAttribute(): ?string
    {
        return $this->getCustomRecordTitleAttribute() ?? $this->getTable()?->getRecordTitleAttribute();
    }

    public function shouldClearRecordAfter(): bool
    {
        return false;
    }

    public function clearRecordAfter(): void
    {
        if (! $this->shouldClearRecordAfter()) {
            return;
        }

        $this->record(null);
    }

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        $context = [];

        if (filled($recordKey = $this->resolveRecordKey($this->getRecord(withDefault: false)))) {
            $context['recordKey'] = $recordKey;
        }

        if ($this->parentAction instanceof self) {
            return $context;
        }

        if ($this->hasTable()) {
            $context['table'] = true;
        }

        if ($this->hasTable() && $this->isBulk()) {
            $context['bulk'] = true;
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
            'recordClass' => ClosureParameter::make($this->getRecordClass()),
            'arguments' => ClosureParameter::make($this->getArguments()),
            'data' => ClosureParameter::make($this->getData()),
            'liveComponent' => ClosureParameter::make($this->getLiveComponent()),
            'table' => ClosureParameter::make($this->getTable()),
            'records', 'selectedRecords' => $this->hasCanAccessSelectedRecords()
                ? ClosureParameter::make($this->getSelectedRecords())
                : parent::resolveDefaultClosureDependencyForEvaluationByName($parameterName),
            'recordsQuery', 'selectedRecordsQuery' => $this->hasCanAccessSelectedRecords()
                ? ClosureParameter::make($this->getSelectedRecordsQuery())
                : parent::resolveDefaultClosureDependencyForEvaluationByName($parameterName),
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
            QueryBuilder::class => $this->hasCanAccessSelectedRecords()
                ? ClosureParameter::make($this->getSelectedRecordsQuery())
                : parent::resolveDefaultClosureDependencyForEvaluationByType($parameterType),
            Get::class => ClosureParameter::make($this->getSchemaComponentOrFail()->makeGetUtility()->skipComponentsChildContainersWhileSearching(false)),
            Table::class => ClosureParameter::make($this->getTable()),
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
        $schemaComponent = $this->getSchemaComponent();
        $itemKey = $this->getArguments()['item'] ?? null;

        if ($schemaComponent instanceof HasExtraItemActions && (is_string($itemKey) || is_int($itemKey)) && $itemKey !== '') {
            return $schemaComponent->getItemState((string) $itemKey);
        }

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
