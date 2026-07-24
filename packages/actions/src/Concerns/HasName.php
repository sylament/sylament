<?php

declare(strict_types=1);

namespace Sylament\Actions\Concerns;

use LogicException;

use function Sylament\Support\blank;

trait HasName
{
    protected ?string $name = null;

    public function name(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        if (blank($this->name)) {
            $actionClass = static::class;

            throw new LogicException("Action of class [{$actionClass}] must have a unique name, passed to the [make()] method.");
        }

        return $this->name;
    }
}
