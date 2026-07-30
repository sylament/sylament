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
        $this->constraint(new IsTrue(), $condition);

        return $this;
    }

    public function declined(bool|Closure $condition = true): static
    {
        $this->constraint(new IsFalse(), $condition);

        return $this;
    }
}
