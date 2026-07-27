<?php

declare(strict_types=1);

namespace Sylament\Actions\Contracts;

use Sylament\Actions\Action;

interface InteractsWithActions
{
    /**
     * @param array<mixed> $arguments
     * @param array<mixed> $context
     */
    public function mountAction(string $name, array $arguments = [], array $context = []): mixed;

    public function callMountedAction(): mixed;

    public function unmountAction(): void;

    public function getMountedAction(): ?Action;

    public function mountedActionShouldOpenModal(?Action $mountedAction = null): bool;

    public function getMountedModalId(): string;

    public function getAction(string $name): ?Action;
}
