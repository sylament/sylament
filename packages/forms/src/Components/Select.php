<?php

declare(strict_types=1);

namespace Sylament\Forms\Components;

use Closure;
use LogicException;
use Sylament\Forms\Components\Concerns\CanDisableOptions;
use Sylament\Forms\Components\Concerns\CanSelectPlaceholder;
use Sylament\Forms\Components\Concerns\HasAffixes;
use Sylament\Forms\Components\Concerns\HasExtraInputAttributes;
use Sylament\Forms\Components\Concerns\HasOptions;
use Sylament\Forms\Components\Concerns\HasRelationship;
use Sylament\Schemas\State\Casts\BooleanStateCast;
use Sylament\Schemas\State\Casts\Contracts\StateCast;
use Sylament\Schemas\State\Casts\EnumArrayStateCast;
use Sylament\Schemas\State\Casts\EnumStateCast;
use Sylament\Schemas\State\Casts\OptionsArrayStateCast;
use Sylament\Schemas\State\Casts\OptionStateCast;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Type;
use Twig\Markup;

use function Sylament\Support\blank;
use function Sylament\Support\filled;

class Select extends Field implements Contracts\CanDisableOptions, Contracts\HasAffixes
{
    use CanDisableOptions;
    use CanSelectPlaceholder;
    use HasAffixes;
    use HasExtraInputAttributes;
    use HasOptions;
    use HasRelationship;

    protected string|Closure|null $template = '@SylamentForms/partials/Select/index.html.twig';

    protected bool|Closure $isMultiple = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->placeholder('Select an option');
    }

    public function boolean(?string $trueLabel = null, ?string $falseLabel = null, ?string $placeholder = null): static
    {
        $this->options([
            1 => $trueLabel ?? 'Yes',
            0 => $falseLabel ?? 'No',
        ]);

        $this->placeholder($placeholder ?? '-');

        $this->stateCast(new BooleanStateCast(isStoredAsInt: true));

        return $this;
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

    public function getOptionLabel(): string|Markup|null
    {
        $state = $this->getOptionComparisonState();

        if (! is_string($state) && ! is_int($state)) {
            return null;
        }

        foreach ($this->getOptions() as $value => $label) {
            $group = is_array($label) ? $label : [$value => $label];

            if (array_key_exists($state, $group)) {
                return $group[$state];
            }
        }

        return null;
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
            return [new OptionsArrayStateCast(isNullable: false)];
        }

        return [new OptionStateCast(isNullable: true)];
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

    public function hasNullableBooleanState(): bool
    {
        return true;
    }

    /**
     * Validation runs against the raw state, and `Choice(multiple: true)` is only
     * defined over an array — its validator throws on any other value rather than
     * failing it — so a multiple select whose raw state is not an array is failed
     * by a type constraint instead of being handed to the Choice.
     *
     * @return list<Constraint>
     */
    protected function buildValidationConstraints(): array
    {
        $constraints = parent::buildValidationConstraints();

        $rawState = $this->getRawState();

        if (blank($rawState)) {
            return $constraints;
        }

        if ($this->options === null && $this->getEnum() === null) {
            throw new LogicException(
                "Sylament failed to validate the [{$this->getStatePath()}] select because it has no options() configuration. Call options() so Sylament knows which values are valid.",
            );
        }

        $isMultiple = $this->isMultiple();

        if ($isMultiple && ! is_array($rawState)) {
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
