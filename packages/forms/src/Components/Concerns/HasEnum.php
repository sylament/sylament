<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\Concerns;

use Closure;
use UnitEnum;

trait HasEnum
{
    /**
     * @var class-string<UnitEnum>|Closure|null
     */
    protected string|Closure|null $enum = null;

    /**
     * @param class-string<UnitEnum>|Closure|null $enum
     */
    public function enum(string|Closure|null $enum): static
    {
        $this->enum = $enum;

        return $this;
    }

    /**
     * @return class-string<UnitEnum>|null
     */
    public function getEnum(): ?string
    {
        return $this->evaluate($this->enum);
    }
}
