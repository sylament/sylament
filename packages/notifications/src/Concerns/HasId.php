<?php

declare(strict_types=1);

namespace Sylament\Notifications\Concerns;

trait HasId
{
    protected string $id;

    public function id(string $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }
}
