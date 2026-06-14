<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Concerns;

use Closure;

trait HasDescription
{
    /**
     * @var string|Closure():string|null
     */
    protected string|Closure|null $description = null;

    /**
     * @param string|callable():string|null $description
     */
    public function description(string|callable|null $description): static
    {
        $this->description = $this->evaluatable($description);

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->evaluate($this->description);
    }
}
