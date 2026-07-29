<?php

declare(strict_types=1);

namespace Sylament\Actions\Concerns;

trait HasMountableArguments
{
    /**
     * @var array<string, mixed>|null
     */
    protected ?array $invokedArguments = null;

    /**
     * @param array<string, mixed> $arguments
     */
    public function __invoke(array $arguments): static
    {
        $action = clone $this;

        $action->arguments($arguments);
        $action->invokedArguments = $arguments;

        return $action;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getInvokedArguments(): ?array
    {
        return $this->invokedArguments;
    }
}
