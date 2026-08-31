<?php

declare(strict_types=1);

namespace Sylament\Forms\Components;

use BackedEnum;
use Closure;
use InvalidArgumentException;
use LogicException;
use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Forms\Components\Concerns\CanBeAutofocused;
use Sylament\Forms\Components\Concerns\CanBeValidated;
use Sylament\Forms\Components\Concerns\HasEnum;
use Sylament\Forms\Components\Concerns\HasExtraFieldWrapperAttributes;
use Sylament\Forms\Components\Concerns\HasHelperText;
use Sylament\Forms\Components\Concerns\HasHint;
use Sylament\Forms\Components\Concerns\HasPlaceholder;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Concerns\CanBeMarkedAsRequired;
use Sylament\Schemas\Components\Concerns\HasLabel;
use Sylament\Schemas\Components\Concerns\HasName;
use Sylament\Schemas\Components\Contracts\HasValidationConstraints;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\Casts\Contracts\StateCast;
use Sylament\Schemas\State\Casts\EnumStateCast;
use Sylament\Support\Enums\Size;
use Twig\Markup;

use function Sylament\Support\blank;
use function Sylament\Support\filled;
use function Symfony\Component\String\u;

abstract class Field extends Component implements HasValidationConstraints
{
    use CanBeAutofocused;
    use CanBeMarkedAsRequired;
    use CanBeValidated;
    use HasEnum;
    use HasExtraFieldWrapperAttributes;
    use HasHelperText;
    use HasHint;
    use HasLabel {
        getLabel as getBaseLabel;
    }
    use HasName;
    use HasPlaceholder;

    public const string ABOVE_LABEL_SCHEMA_KEY = 'above_label';

    public const string BELOW_LABEL_SCHEMA_KEY = 'below_label';

    public const string BEFORE_LABEL_SCHEMA_KEY = 'before_label';

    public const string AFTER_LABEL_SCHEMA_KEY = 'after_label';

    public const string ABOVE_CONTENT_SCHEMA_KEY = 'above_content';

    public const string BELOW_CONTENT_SCHEMA_KEY = 'below_content';

    public const string BEFORE_CONTENT_SCHEMA_KEY = 'before_content';

    public const string AFTER_CONTENT_SCHEMA_KEY = 'after_content';

    public const string ABOVE_ERROR_MESSAGE_SCHEMA_KEY = 'above_error_message';

    public const string BELOW_ERROR_MESSAGE_SCHEMA_KEY = 'below_error_message';

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpHint();
    }

    public static function make(?string $name = null): static
    {
        $name ??= static::getDefaultName();

        if (blank($name)) {
            throw new InvalidArgumentException(sprintf('Field of class [%s] must have a unique name, passed to the [make()] method.', static::class));
        }

        $field = static::newInstance();

        $field->name($name);
        $field->statePath($name);

        $field->configure();

        return $field;
    }

    public static function getDefaultName(): ?string
    {
        return null;
    }

    public function getLabel(): string|Markup|null
    {
        if (filled($label = $this->getBaseLabel())) {
            return $label;
        }

        return $this->getDefaultLabel();
    }

    protected function getDefaultLabel(): ?string
    {
        $name = $this->getName();

        if ($name === null) {
            return null;
        }

        return (string) u($name)->afterLast('.')->kebab()->replace('-', ' ')->title();
    }

    /**
     * @return list<string>
     */
    public function getErrorMessages(): array
    {
        return $this->getLiveComponentOrNull()?->getErrorsForSchemaStatePath($this->getStatePath()) ?? [];
    }

    /**
     * @return array<StateCast>
     */
    public function getDefaultStateCasts(): array
    {
        $casts = parent::getDefaultStateCasts();

        $enumCast = $this->getEnumDefaultStateCast();

        if ($enumCast instanceof StateCast) {
            $casts[] = $enumCast;
        }

        return $casts;
    }

    public function getEnumDefaultStateCast(): ?StateCast
    {
        $enum = $this->getEnum();

        if ($enum === null) {
            return null;
        }

        $this->assertBackedEnum($enum);

        return new EnumStateCast($enum);
    }

    /**
     * @phpstan-assert class-string<BackedEnum> $enum
     */
    protected function assertBackedEnum(string $enum): void
    {
        if (is_a($enum, BackedEnum::class, allow_string: true)) {
            return;
        }

        throw new LogicException(sprintf(
            'The [%s] field was given the enum [%s], which is not a backed enum, so its cases cannot round-trip through form state. Back the enum with string or int values.',
            $this->getName() ?? static::class,
            $enum,
        ));
    }

    /**
     * Whether a boolean-valued state has an "unset" resting value beside true and
     * false — a `Select` placeholder does, a checkbox does not.
     */
    public function hasNullableBooleanState(): bool
    {
        return false;
    }

    /**
     * @param Schema|array<Component|Action|ActionGroup|string|Markup>|Component|Action|ActionGroup|string|Markup|Closure|null $components
     */
    public function aboveLabel(Schema|array|Component|Action|ActionGroup|string|Markup|Closure|null $components): static
    {
        $this->childComponents($components, static::ABOVE_LABEL_SCHEMA_KEY);

        return $this;
    }

    /**
     * @param Schema|array<Component|Action|ActionGroup|string|Markup>|Component|Action|ActionGroup|string|Markup|Closure|null $components
     */
    public function belowLabel(Schema|array|Component|Action|ActionGroup|string|Markup|Closure|null $components): static
    {
        $this->childComponents($components, static::BELOW_LABEL_SCHEMA_KEY);

        return $this;
    }

    /**
     * @param Schema|array<Component|Action|ActionGroup|string|Markup>|Component|Action|ActionGroup|string|Markup|Closure|null $components
     */
    public function beforeLabel(Schema|array|Component|Action|ActionGroup|string|Markup|Closure|null $components): static
    {
        $this->childComponents($components, static::BEFORE_LABEL_SCHEMA_KEY);

        return $this;
    }

    /**
     * @param Schema|array<Component|Action|ActionGroup|string|Markup>|Component|Action|ActionGroup|string|Markup|Closure|null $components
     */
    public function afterLabel(Schema|array|Component|Action|ActionGroup|string|Markup|Closure|null $components): static
    {
        $this->childComponents($components, static::AFTER_LABEL_SCHEMA_KEY);

        return $this;
    }

    /**
     * @param Schema|array<Component|Action|ActionGroup|string|Markup>|Component|Action|ActionGroup|string|Markup|Closure|null $components
     */
    public function aboveContent(Schema|array|Component|Action|ActionGroup|string|Markup|Closure|null $components): static
    {
        $this->childComponents($components, static::ABOVE_CONTENT_SCHEMA_KEY);

        return $this;
    }

    /**
     * @param Schema|array<Component|Action|ActionGroup|string|Markup>|Component|Action|ActionGroup|string|Markup|Closure|null $components
     */
    public function belowContent(Schema|array|Component|Action|ActionGroup|string|Markup|Closure|null $components): static
    {
        $this->childComponents($components, static::BELOW_CONTENT_SCHEMA_KEY);

        return $this;
    }

    /**
     * @param Schema|array<Component|Action|ActionGroup|string|Markup>|Component|Action|ActionGroup|string|Markup|Closure|null $components
     */
    public function beforeContent(Schema|array|Component|Action|ActionGroup|string|Markup|Closure|null $components): static
    {
        $this->childComponents($components, static::BEFORE_CONTENT_SCHEMA_KEY);

        return $this;
    }

    /**
     * @param Schema|array<Component|Action|ActionGroup|string|Markup>|Component|Action|ActionGroup|string|Markup|Closure|null $components
     */
    public function afterContent(Schema|array|Component|Action|ActionGroup|string|Markup|Closure|null $components): static
    {
        $this->childComponents($components, static::AFTER_CONTENT_SCHEMA_KEY);

        return $this;
    }

    /**
     * @param Schema|array<Component|Action|ActionGroup|string|Markup>|Component|Action|ActionGroup|string|Markup|Closure|null $components
     */
    public function aboveErrorMessage(Schema|array|Component|Action|ActionGroup|string|Markup|Closure|null $components): static
    {
        $this->childComponents($components, static::ABOVE_ERROR_MESSAGE_SCHEMA_KEY);

        return $this;
    }

    /**
     * @param Schema|array<Component|Action|ActionGroup|string|Markup>|Component|Action|ActionGroup|string|Markup|Closure|null $components
     */
    public function belowErrorMessage(Schema|array|Component|Action|ActionGroup|string|Markup|Closure|null $components): static
    {
        $this->childComponents($components, static::BELOW_ERROR_MESSAGE_SCHEMA_KEY);

        return $this;
    }

    protected function makeChildSchema(string $key): Schema
    {
        $schema = parent::makeChildSchema($key);

        if (in_array($key, [static::AFTER_LABEL_SCHEMA_KEY, static::AFTER_CONTENT_SCHEMA_KEY], true)) {
            $schema->alignEnd();
        }

        return $schema;
    }

    protected function configureChildSchema(Schema $schema, string $key): Schema
    {
        $schema = parent::configureChildSchema($schema, $key);

        if (in_array($key, [
            static::ABOVE_LABEL_SCHEMA_KEY,
            static::BELOW_LABEL_SCHEMA_KEY,
            static::BEFORE_LABEL_SCHEMA_KEY,
            static::AFTER_LABEL_SCHEMA_KEY,
            static::ABOVE_CONTENT_SCHEMA_KEY,
            static::BELOW_CONTENT_SCHEMA_KEY,
            static::BEFORE_CONTENT_SCHEMA_KEY,
            static::AFTER_CONTENT_SCHEMA_KEY,
            static::ABOVE_ERROR_MESSAGE_SCHEMA_KEY,
            static::BELOW_ERROR_MESSAGE_SCHEMA_KEY,
        ], true)) {
            $schema
                ->inline()
                ->embeddedInParentComponent()
                ->modifyActionsUsing(fn (Action $action): Action => $action
                    ->defaultSize(Size::Small)
                    ->defaultTemplate(Action::LINK_TEMPLATE))
                ->modifyActionGroupsUsing(fn (ActionGroup $actionGroup): ActionGroup => $actionGroup->defaultSize(Size::Small));
        }

        return $schema;
    }
}
