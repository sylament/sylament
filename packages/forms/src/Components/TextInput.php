<?php

declare(strict_types=1);

namespace Sylament\Forms\Components;

use Closure;
use Sylament\Forms\Components\Concerns\CanBeAutocapitalized;
use Sylament\Forms\Components\Concerns\CanBeAutocompleted;
use Sylament\Forms\Components\Concerns\CanBeLengthConstrained;
use Sylament\Forms\Components\Concerns\CanBeReadOnly;
use Sylament\Forms\Components\Concerns\HasAffixes;
use Sylament\Forms\Components\Concerns\HasDatalistOptions;
use Sylament\Forms\Components\Concerns\HasExtraInputAttributes;
use Sylament\Forms\Components\Concerns\HasStep;
use Sylament\Schemas\Components\Concerns\CanStripCharactersFromState;
use Sylament\Schemas\Components\Concerns\CanTrimState;
use Sylament\Schemas\State\Casts\Contracts\StateCast;
use Sylament\Schemas\State\Casts\StripCharactersStateCast;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\Url;

use function Sylament\Support\filled;

class TextInput extends Field implements Contracts\CanBeLengthConstrained, Contracts\HasAffixes
{
    use CanBeAutocapitalized;
    use CanBeAutocompleted;
    use CanBeLengthConstrained;
    use CanBeReadOnly;
    use CanStripCharactersFromState;
    use CanTrimState;
    use HasAffixes;
    use HasDatalistOptions;
    use HasExtraInputAttributes;
    use HasStep;

    protected string|Closure|null $template = '@SylamentForms/partials/TextInput/index.html.twig';

    protected bool|Closure $isEmail = false;

    protected bool|Closure $isInteger = false;

    protected bool|Closure $isNumeric = false;

    protected bool|Closure $isPassword = false;

    protected bool|Closure $isTel = false;

    protected bool|Closure $isUrl = false;

    protected int|float|string|Closure|null $maxValue = null;

    protected int|float|string|Closure|null $minValue = null;

    protected string|Closure|null $telRegex = null;

    protected string|Closure|null $type = null;

    public function type(string|Closure|null $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function email(bool|Closure $condition = true): static
    {
        $this->isEmail = $condition;

        return $this;
    }

    public function integer(bool|Closure $condition = true): static
    {
        $this->isInteger = $condition;
        $this->numeric($condition);
        $this->step(static fn (TextInput $component): ?int => $component->isInteger() ? 1 : null);

        return $this;
    }

    public function maxValue(int|float|string|Closure|null $value): static
    {
        $this->maxValue = $value;

        return $this;
    }

    public function minValue(int|float|string|Closure|null $value): static
    {
        $this->minValue = $value;

        return $this;
    }

    public function numeric(bool|Closure $condition = true): static
    {
        $this->isNumeric = $condition;
        $this->step(static fn (TextInput $component): ?string => $component->isNumeric() ? 'any' : null);

        return $this;
    }

    public function password(bool|Closure $condition = true): static
    {
        $this->isPassword = $condition;

        return $this;
    }

    /**
     * Also writes the shared `regex()` slot, so `->tel()->regex('/…/')` keeps the
     * later call and `->regex('/…/')->tel()` keeps the tel pattern — Filament's own
     * behaviour, which only looked like a `tel()` decision while no `regex()` existed
     * to collide with.
     */
    public function tel(bool|Closure $condition = true): static
    {
        $this->isTel = $condition;

        $this->regex(fn (): ?string => $this->isTel() ? $this->getTelRegex() : null);

        return $this;
    }

    public function telRegex(string|Closure|null $regex): static
    {
        $this->telRegex = $regex;

        return $this;
    }

    public function url(bool|Closure $condition = true): static
    {
        $this->isUrl = $condition;

        return $this;
    }

    public function getMaxValue(): int|float|string|null
    {
        return $this->evaluate($this->maxValue);
    }

    public function getMinValue(): int|float|string|null
    {
        return $this->evaluate($this->minValue);
    }

    public function getType(): string
    {
        if ($type = $this->evaluate($this->type)) {
            return $type;
        }

        if ($this->isEmail()) {
            return 'email';
        }

        if ($this->isNumeric()) {
            return 'number';
        }

        if ($this->isPassword()) {
            return 'password';
        }

        if ($this->isTel()) {
            return 'tel';
        }

        if ($this->isUrl()) {
            return 'url';
        }

        return 'text';
    }

    public function getTelRegex(): string
    {
        return $this->evaluate($this->telRegex) ?? '/^[+]*[(]{0,1}\d{1,4}[)]{0,1}[-\s\.\/0-9]*$/';
    }

    public function getInputMode(): ?string
    {
        if ($this->isInteger()) {
            return 'numeric';
        }

        return $this->isNumeric() ? 'decimal' : null;
    }

    public function isEmail(): bool
    {
        return (bool) $this->evaluate($this->isEmail);
    }

    public function isInteger(): bool
    {
        return (bool) $this->evaluate($this->isInteger);
    }

    public function isNumeric(): bool
    {
        return (bool) $this->evaluate($this->isNumeric);
    }

    public function isPassword(): bool
    {
        return (bool) $this->evaluate($this->isPassword);
    }

    public function isTel(): bool
    {
        return (bool) $this->evaluate($this->isTel);
    }

    public function isUrl(): bool
    {
        return (bool) $this->evaluate($this->isUrl);
    }

    /**
     * @return list<Constraint>
     */
    protected function buildValidationConstraints(): array
    {
        $constraints = parent::buildValidationConstraints();

        if ($this->isEmail()) {
            $constraints[] = new Email();
        }

        if ($this->isNumeric()) {
            $constraints[] = new Type('numeric');
        }

        if ($this->isInteger()) {
            $constraints[] = new Regex('/^[+-]?\d+$/');
        }

        if ($this->isUrl()) {
            $constraints[] = new Url();
        }

        if (filled($minValue = $this->getMinValue())) {
            $constraints[] = new GreaterThanOrEqual($minValue);
        }

        if (filled($maxValue = $this->getMaxValue())) {
            $constraints[] = new LessThanOrEqual($maxValue);
        }

        return $constraints;
    }

    public function mutateStateForValidation(mixed $state): mixed
    {
        return parent::mutateStateForValidation($this->trimState($this->stripCharactersFromState($state)));
    }

    public function mutatesStateForValidation(): bool
    {
        return parent::mutatesStateForValidation() || $this->hasStripCharacters() || $this->isTrimmed();
    }

    /**
     * Filament's asymmetry, kept: `trim()` reaches the saved value from here, while
     * `stripCharacters()` reaches it through the state cast below — and neither
     * reaches the raw bag the browser wrote.
     */
    public function mutateDehydratedState(mixed $state): mixed
    {
        return parent::mutateDehydratedState($this->trimState($state));
    }

    public function mutatesDehydratedState(): bool
    {
        return parent::mutatesDehydratedState() || $this->isTrimmed();
    }

    /**
     * @return array<StateCast>
     */
    public function getDefaultStateCasts(): array
    {
        return [
            ...parent::getDefaultStateCasts(),
            ...($this->hasStripCharacters() ? [new StripCharactersStateCast(array_values($this->getStripCharacters()))] : []),
        ];
    }
}
