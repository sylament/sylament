<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\Concerns;

use Closure;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Length;

trait CanBeLengthConstrained
{
    protected int|Closure|null $length = null;

    protected int|Closure|null $maxLength = null;

    protected int|Closure|null $minLength = null;

    public function length(int|Closure|null $length): static
    {
        $this->length = $length;
        $this->maxLength = $length;
        $this->minLength = $length;

        return $this;
    }

    public function maxLength(int|Closure|null $length): static
    {
        $this->maxLength = $length;

        return $this;
    }

    public function minLength(int|Closure|null $length): static
    {
        $this->minLength = $length;

        return $this;
    }

    public function getLength(): ?int
    {
        return $this->evaluate($this->length);
    }

    public function getMaxLength(): ?int
    {
        return $this->evaluate($this->maxLength);
    }

    public function getMinLength(): ?int
    {
        return $this->evaluate($this->minLength);
    }

    /**
     * @return list<Constraint>
     */
    public function getLengthValidationConstraints(): array
    {
        if (($length = $this->getLength()) > 0) {
            return [new Length(exactly: $length)];
        }

        $constraints = [];

        if (($maxLength = $this->getMaxLength()) > 0) {
            $constraints[] = new Length(max: $maxLength);
        }

        if (($minLength = $this->getMinLength()) > 0) {
            $constraints[] = new Length(min: $minLength);
        }

        return $constraints;
    }
}
