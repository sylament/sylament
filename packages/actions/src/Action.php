<?php

declare(strict_types=1);

namespace Sylament\Actions;

use BackedEnum;
use Closure;
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
use Sylament\Actions\Concerns\CanDispatchEvent;
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
use Sylament\Support\Contracts\ScalableIcon;
use Sylament\Support\Enums\IconPosition;
use Sylament\Support\Enums\IconSize;
use Sylament\Support\Enums\Size;
use Sylament\Support\Evaluation\ClosureParameter;
use Sylament\Support\Exceptions\Cancel;
use Sylament\Support\Exceptions\Halt;
use Twig\Markup;

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
    use CanBeMarkedAsRead;
    use CanBeMounted;
    use CanBeOutlined;
    use CanCallParentAction;
    use CanClose;
    use CanDispatchEvent;
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
