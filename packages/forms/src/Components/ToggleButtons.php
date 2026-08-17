<?php

declare(strict_types=1);

namespace Sylament\Forms\Components;

use Closure;
use LogicException;
use Sylament\Forms\Components\Concerns\CanDisableOptions;
use Sylament\Forms\Components\Concerns\HasColors;
use Sylament\Forms\Components\Concerns\HasExtraInputAttributes;
use Sylament\Forms\Components\Concerns\HasGridDirection;
use Sylament\Forms\Components\Concerns\HasIcons;
use Sylament\Forms\Components\Concerns\HasOptions;
use Sylament\Forms\Components\Concerns\HasTooltips;
use Sylament\Forms\Twig\FormsIconAlias;
use Sylament\Schemas\State\Casts\BooleanStateCast;
use Sylament\Schemas\State\Casts\Contracts\StateCast;
use Sylament\Schemas\State\Casts\EnumArrayStateCast;
use Sylament\Schemas\State\Casts\EnumStateCast;
use Sylament\Schemas\State\Casts\OptionsArrayStateCast;
use Sylament\Schemas\State\Casts\OptionStateCast;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Type;

use function Sylament\Support\blank;
use function Sylament\Support\filled;

class ToggleButtons extends Field implements Contracts\CanDisableOptions
{
    use CanDisableOptions;
    use HasColors;
    use HasExtraInputAttributes;
    use HasGridDirection;
    use HasIcons;
    use HasOptions;
    use HasTooltips;

    public const string TEMPLATE = '@SylamentForms/partials/ToggleButtons/index.html.twig';

    public const string GROUPED_TEMPLATE = '@SylamentForms/partials/ToggleButtons/Grouped.html.twig';

    protected bool|Closure $isMultiple = false;

    protected bool|Closure $isInline = false;

    protected bool|Closure $isGrouped = false;

    protected bool|Closure $areButtonLabelsHidden = false;

    /**
     * @var array<string|int, string>
     */
    protected array $iconAliases = [];

    public function boolean(?string $trueLabel = null, ?string $falseLabel = null): static
    {
        $this->options([
            1 => $trueLabel ?? 'Yes',
            0 => $falseLabel ?? 'No',
        ]);

        $this->colors([
            1 => 'success',
            0 => 'danger',
        ]);

        $this->icons([
            1 => 'heroicons:check-20-solid',
            0 => 'heroicons:x-mark-20-solid',
        ]);

        $this->iconAliases = [
            1 => FormsIconAlias::COMPONENTS_TOGGLE_BUTTONS_BOOLEAN_TRUE,
            0 => FormsIconAlias::COMPONENTS_TOGGLE_BUTTONS_BOOLEAN_FALSE,
        ];

        $this->stateCast(new BooleanStateCast(isStoredAsInt: true));

        return $this;
    }

    public function getIconAlias(string|int $value): ?string
    {
        return $this->iconAliases[$value] ?? null;
    }

    public function grouped(bool|Closure $condition = true): static
    {
        $this->isGrouped = $condition;

        return $this;
    }

    public function isGrouped(): bool
    {
        return (bool) $this->evaluate($this->isGrouped);
    }

    public function inline(bool|Closure $condition = true): static
    {
        $this->isInline = $condition;

        return $this;
    }

    public function isInline(): bool
    {
        return (bool) $this->evaluate($this->isInline);
    }

    public function hiddenButtonLabels(bool|Closure $condition = true): static
    {
        $this->areButtonLabelsHidden = $condition;

        return $this;
    }

    public function areButtonLabelsHidden(): bool
    {
        return (bool) $this->evaluate($this->areButtonLabelsHidden);
    }

    public function multiple(bool|Closure $condition = true): static
    {
        $this->isMultiple = $condition;

        return $this;
    }

    public function isMultiple(): bool
    {
        return (bool) $this->evaluate($this->isMultiple);
    }

    public function getTemplate(): ?string
    {
        return $this->evaluate($this->template)
            ?? $this->evaluate($this->defaultTemplate)
            ?? ($this->isGrouped() ? static::GROUPED_TEMPLATE : static::TEMPLATE);
    }

    public function getDefaultState(): mixed
    {
        $state = parent::getDefaultState();

        if (is_bool($state)) {
            return $state ? 1 : 0;
        }

        return $state;
    }

    public function getEnumDefaultStateCast(): ?StateCast
    {
        $enum = $this->getEnum();

        if ($enum === null) {
            return null;
        }

        $this->assertBackedEnum($enum);

        return $this->isMultiple()
            ? new EnumArrayStateCast($enum)
            : new EnumStateCast($enum);
    }

    /**
     * @return array<StateCast>
     */
    public function getDefaultStateCasts(): array
    {
        if ($this->hasCustomStateCasts() || filled($this->getEnum())) {
            return parent::getDefaultStateCasts();
        }

        if ($this->isMultiple()) {
            return [new OptionsArrayStateCast()];
        }

        return [new OptionStateCast(isNullable: true)];
    }

    public function hasNullableBooleanState(): bool
    {
        return true;
    }

    /**
     * @return list<Constraint>
     */
    protected function buildValidationConstraints(): array
    {
        $constraints = parent::buildValidationConstraints();

        if (blank($this->getState())) {
            return $constraints;
        }

        if ($this->options === null && $this->getEnum() === null) {
            throw new LogicException(
                "Sylament failed to validate the [{$this->getStatePath()}] toggle buttons because it has no options() configuration. Call options() so Sylament knows which values are valid.",
            );
        }

        $isMultiple = $this->isMultiple();

        if ($isMultiple && ! is_array($this->getRawState())) {
            $constraints[] = new Type('array');

            return $constraints;
        }

        $constraints[] = new Choice(
            choices: $this->getEnabledOptionValues(),
            multiple: $isMultiple,
            strict: true,
        );

        return $constraints;
    }
}
