<?php

declare(strict_types=1);

namespace Sylament\Actions\Concerns;

use Closure;
use Twig\Markup;

use function Sylament\Support\filled;

trait CanOpenModal
{
    protected bool|Closure|null $hasModal = null;

    protected bool|Closure|null $isModalHidden = null;

    protected string|Markup|Closure|null $modalHeading = null;

    protected string|Markup|Closure|null $modalDescription = null;

    protected string|Closure|null $modalIcon = null;

    /**
     * @var string|array<string>|Closure|null
     */
    protected string|array|Closure|null $modalIconColor = null;

    protected string|Closure|null $modalSubmitActionLabel = null;

    protected string|Closure|null $modalCancelActionLabel = null;

    protected string|Closure|null $modalWidth = null;

    public function modal(bool|Closure|null $condition = true): static
    {
        $this->hasModal = $condition;

        return $this;
    }

    public function modalHidden(bool|Closure|null $condition = true): static
    {
        $this->isModalHidden = $condition;

        return $this;
    }

    public function modalHeading(string|Markup|Closure|null $heading = null): static
    {
        $this->modalHeading = $heading;

        return $this;
    }

    public function modalDescription(string|Markup|Closure|null $description = null): static
    {
        $this->modalDescription = $description;

        return $this;
    }

    public function modalIcon(string|Closure|null $icon = null): static
    {
        $this->modalIcon = $icon;

        return $this;
    }

    /**
     * @param string|array<string>|Closure|null $color
     */
    public function modalIconColor(string|array|Closure|null $color = null): static
    {
        $this->modalIconColor = $color;

        return $this;
    }

    public function modalSubmitActionLabel(string|Closure|null $label = null): static
    {
        $this->modalSubmitActionLabel = $label;

        return $this;
    }

    public function modalCancelActionLabel(string|Closure|null $label = null): static
    {
        $this->modalCancelActionLabel = $label;

        return $this;
    }

    public function modalWidth(string|Closure|null $width = null): static
    {
        $this->modalWidth = $width;

        return $this;
    }

    public function hasModal(): ?bool
    {
        return $this->evaluate($this->hasModal);
    }

    public function hasCustomModalPresence(): bool
    {
        return $this->hasModal !== null;
    }

    public function shouldOpenModal(?Closure $checkForSchemaUsing = null): bool
    {
        if (is_bool($hasModal = $this->hasModal())) {
            return $hasModal;
        }

        if ((bool) $this->evaluate($this->isModalHidden)) {
            return false;
        }

        if ($this->hasCustomModalHeading()) {
            return true;
        }

        if ($this->hasModalDescription()) {
            return true;
        }

        return (bool) ($checkForSchemaUsing instanceof Closure ? $checkForSchemaUsing($this) : false);
    }

    public function getCustomModalHeading(): string|Markup|null
    {
        return $this->evaluate($this->modalHeading);
    }

    public function hasCustomModalHeading(): bool
    {
        return filled($this->getCustomModalHeading());
    }

    public function getModalHeading(): string|Markup|null
    {
        return $this->getCustomModalHeading() ?? $this->getLabel();
    }

    public function getModalDescription(): string|Markup|null
    {
        if (filled($description = $this->evaluate($this->modalDescription))) {
            return $description;
        }

        if ($this->isConfirmationRequired()) {
            return 'Are you sure you would like to do this?';
        }

        return null;
    }

    public function hasModalDescription(): bool
    {
        return filled($this->getModalDescription());
    }

    public function getModalIcon(): ?string
    {
        if (filled($icon = $this->evaluate($this->modalIcon))) {
            return $icon;
        }

        if ($this->isConfirmationRequired()) {
            return 'heroicons:exclamation-triangle';
        }

        return null;
    }

    /**
     * @return string|array<string>|null
     */
    public function getModalIconColor(): string|array|null
    {
        return $this->evaluate($this->modalIconColor) ?? $this->getColor() ?? 'primary';
    }

    public function getModalSubmitActionLabel(): string
    {
        if (filled($label = $this->evaluate($this->modalSubmitActionLabel))) {
            return $label;
        }

        return $this->isConfirmationRequired() ? 'Confirm' : 'Submit';
    }

    public function getModalCancelActionLabel(): string
    {
        return $this->evaluate($this->modalCancelActionLabel) ?? 'Cancel';
    }

    public function getModalWidth(): string
    {
        return $this->evaluate($this->modalWidth) ?? 'md';
    }
}
