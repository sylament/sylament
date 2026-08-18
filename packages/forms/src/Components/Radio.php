<?php

declare(strict_types=1);

namespace Sylament\Forms\Components;

use Closure;
use LogicException;
use Sylament\Forms\Components\Concerns\CanDisableOptions;
use Sylament\Forms\Components\Concerns\HasDescriptions;
use Sylament\Forms\Components\Concerns\HasExtraInputAttributes;
use Sylament\Forms\Components\Concerns\HasGridDirection;
use Sylament\Forms\Components\Concerns\HasOptions;
use Sylament\Schemas\State\Casts\BooleanStateCast;
use Sylament\Schemas\State\Casts\Contracts\StateCast;
use Sylament\Schemas\State\Casts\OptionStateCast;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Choice;

use function Sylament\Support\blank;
use function Sylament\Support\filled;

class Radio extends Field implements Contracts\CanDisableOptions
{
    use CanDisableOptions;
    use HasDescriptions;
    use HasExtraInputAttributes;
    use HasGridDirection;
    use HasOptions;

    protected string|Closure|null $template = '@SylamentForms/partials/Radio/index.html.twig';

    protected bool|Closure $isInline = false;

    public function boolean(?string $trueLabel = null, ?string $falseLabel = null): static
    {
        $this->options([
            1 => $trueLabel ?? 'Yes',
            0 => $falseLabel ?? 'No',
        ]);

        $this->stateCast(new BooleanStateCast(isStoredAsInt: true));

        return $this;
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

    public function getDefaultState(): mixed
    {
        $state = parent::getDefaultState();

        if (is_bool($state)) {
            return $state ? 1 : 0;
        }

        return $state;
    }

    /**
     * @return array<StateCast>
     */
    public function getDefaultStateCasts(): array
    {
        if ($this->hasCustomStateCasts() || filled($this->getEnum())) {
            return parent::getDefaultStateCasts();
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

        if (blank($this->getRawState())) {
            return $constraints;
        }

        if ($this->options === null && $this->getEnum() === null) {
            throw new LogicException(
                "Sylament failed to validate the [{$this->getStatePath()}] radio because it has no options() configuration. Call options() so Sylament knows which values are valid.",
            );
        }

        $constraints[] = new Choice(
            choices: $this->getEnabledOptionValues(),
            multiple: false,
            strict: true,
        );

        return $constraints;
    }
}
