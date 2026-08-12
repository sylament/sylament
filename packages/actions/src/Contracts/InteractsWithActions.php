<?php

declare(strict_types=1);

namespace Sylament\Actions\Contracts;

use Sylament\Actions\Action;
use Sylament\Schemas\Schema;

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

    /**
     * One frame of the per-mount form-state bag.
     *
     * @return array<string, mixed>
     */
    public function getMountedActionData(?int $actionNestingIndex = null): array;

    public function getMountedActionSchema(?int $actionNestingIndex = null, ?Action $mountedAction = null): ?Schema;

    public function mountedActionHasSchema(?Action $mountedAction = null): bool;

    public function mountedActionShouldOpenModal(?Action $mountedAction = null): bool;

    public function getMountedModalId(?int $actionNestingIndex = null): string;

    public function isMountedActionModalOpen(int $actionNestingIndex): bool;

    public function getAction(string $name): ?Action;

    /**
     * The URL an action with no `url()` of its own falls through to — null when
     * the host has no page to send it to.
     */
    public function getDefaultActionUrl(Action $action): ?string;
}
