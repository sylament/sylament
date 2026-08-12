<?php

declare(strict_types=1);

namespace Sylament\Panels\Panel\Concerns;

use LogicException;

trait HasId
{
    protected string $id;

    public function id(string $id): static
    {
        if (isset($this->id)) {
            throw new LogicException("The panel has already been registered with the ID [{$this->id}].");
        }

        $this->id = $id;

        return $this;
    }

    public function getId(): string
    {
        if (! isset($this->id)) {
            throw new LogicException('A panel has been registered without an `id()`.');
        }

        return $this->id;
    }
}
