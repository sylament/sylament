<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\Concerns;

use Closure;

trait HasDatalistOptions
{
    /**
     * @var array<string>|Closure|null
     */
    protected array|Closure|null $datalistOptions = null;

    /**
     * @param array<string>|Closure|null $options
     */
    public function datalist(array|Closure|null $options): static
    {
        $this->datalistOptions = $options;

        return $this;
    }

    /**
     * @return array<string>|null
     */
    public function getDatalistOptions(): ?array
    {
        return $this->evaluate($this->datalistOptions);
    }
}
