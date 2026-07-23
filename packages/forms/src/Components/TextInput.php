<?php

declare(strict_types=1);

namespace Sylament\Forms\Components;

use Closure;
use Sylament\Forms\Components\Concerns\CanBeLengthConstrained;

class TextInput extends Field
{
    use CanBeLengthConstrained;

    protected string|Closure|null $template = '@SylamentForms/components/TextInput.html.twig';

    protected bool|Closure $isEmail = false;

    protected bool|Closure $isNumeric = false;

    protected bool|Closure $isPassword = false;

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

        return $this;
    }

    public function password(bool|Closure $condition = true): static
    {
        $this->isPassword = $condition;

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

        return 'text';
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
}
