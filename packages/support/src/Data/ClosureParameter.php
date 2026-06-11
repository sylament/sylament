<?php

declare(strict_types=1);

namespace Sylament\Support\Data;

class ClosureParameter
{
    public function __construct(
        protected readonly mixed $value,
    ) {
        //
    }

    public static function make(mixed $value): self
    {
        return new self($value);
    }

    public function getValue(): mixed
    {
        return $this->value;
    }
}
