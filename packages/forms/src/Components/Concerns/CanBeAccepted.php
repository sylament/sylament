<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\Concerns;

use Closure;
use Symfony\Component\Validator\Constraints\IsFalse;
use Symfony\Component\Validator\Constraints\IsTrue;

trait CanBeAccepted
{
    public function accepted(bool|Closure $condition = true): static
    {
        $this->constraint(
            fn (): IsTrue => new IsTrue(message: $this->getValidationMessages()['accepted'] ?? null),
            $condition,
        );

        return $this;
    }

    public function declined(bool|Closure $condition = true): static
    {
        $this->constraint(
            fn (): IsFalse => new IsFalse(message: $this->getValidationMessages()['declined'] ?? null),
            $condition,
        );

        return $this;
    }
}
