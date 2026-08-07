<?php

declare(strict_types=1);

namespace Sylament\Forms\Components;

use Closure;
use Sylament\Forms\Components\Concerns\CanBeAutocapitalized;
use Sylament\Forms\Components\Concerns\CanBeAutocompleted;
use Sylament\Forms\Components\Concerns\CanBeLengthConstrained;
use Sylament\Forms\Components\Concerns\CanBeReadOnly;
use Sylament\Forms\Components\Concerns\HasDatalistOptions;
use Sylament\Forms\Components\Concerns\HasExtraInputAttributes;
use Sylament\Forms\Components\Concerns\HasStep;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\Url;

class TextInput extends Field implements Contracts\CanBeLengthConstrained
{
    use CanBeAutocapitalized;
    use CanBeAutocompleted;
    use CanBeLengthConstrained;
    use CanBeReadOnly;
    use HasDatalistOptions;
    use HasExtraInputAttributes;
    use HasStep;

    protected string|Closure|null $template = '@SylamentForms/partials/TextInput/index.html.twig';

    protected bool|Closure $isEmail = false;

    protected bool|Closure $isNumeric = false;

    protected bool|Closure $isPassword = false;

    protected bool|Closure $isTel = false;

    protected bool|Closure $isUrl = false;

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

    public function tel(bool|Closure $condition = true): static
    {
        $this->isTel = $condition;

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
        return $this->isNumeric() ? 'decimal' : null;
    }

    public function isEmail(): bool
    {
        return (bool) $this->evaluate($this->isEmail);
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

        if ($this->isTel()) {
            $constraints[] = new Regex($this->getTelRegex());
        }

        if ($this->isUrl()) {
            $constraints[] = new Url();
        }

        return $constraints;
    }
}
