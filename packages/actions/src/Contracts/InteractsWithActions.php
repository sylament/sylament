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

    /**
     * @param array<mixed> $arguments
     */
    public function callMountedAction(array $arguments = []): mixed;

    public function unmountAction(bool|string|null $cancelParentActions = null): void;

    public function getMountedAction(?int $actionNestingIndex = null): ?Action;

    /**
     * @return array<Action>
     */
    public function getMountedActions(): array;

    public function mountedActionShouldOpenModal(?Action $mountedAction = null): bool;

    public function getMountedModalId(?int $actionNestingIndex = null): string;

    public function isMountedActionModalOpen(int $actionNestingIndex): bool;

    public function getAction(string $name): ?Action;
}
