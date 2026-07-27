<?php

declare(strict_types=1);

namespace Sylament\Support\Evaluation;

final readonly class ClosureParameter
{
    public function __construct(
        private mixed $value,
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
