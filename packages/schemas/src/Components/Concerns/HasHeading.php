<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Concerns;

use Closure;

trait HasHeading
{
    /**
     * @var string|Closure():string|null
     */
    protected string|Closure|null $heading = null;

    /**
     * @param string|callable():string|null $heading
     */
    public function heading(string|callable|null $heading): static
    {
        $this->heading = $this->evaluatable($heading);

        return $this;
    }

    public function getHeading(): ?string
    {
        return $this->evaluate($this->heading);
    }
}
