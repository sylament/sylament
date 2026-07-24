<?php

declare(strict_types=1);

namespace Sylament\Actions\Concerns;

use Sylament\Actions\Action;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;

trait HasActions
{
    use ComponentToolsTrait;

    /**
     * @var array<int, array{name: string, arguments: array<mixed>, context: array<mixed>}>
     */
    #[LiveProp]
    public array $mountedActions = [];

    /**
     * @param array<mixed> $arguments
     * @param array<mixed> $context
     */
    #[LiveAction]
    public function mountAction(
        #[LiveArg] string $name,
        #[LiveArg] array $arguments = [],
        #[LiveArg] array $context = [],
    ): void {
        $this->mountedActions = [[
            'name' => $name,
            'arguments' => $arguments,
            'context' => $context,
        ]];

        $action = $this->getMountedAction();

        if ($action === null) {
            $this->unmountAction();

            return;
        }

        if ($action->isConfirmationRequired()) {
            $this->dispatchBrowserEvent('sylament-open-modal', ['id' => $this->getMountedModalId()]);
        }
    }

    #[LiveAction]
    public function callMountedAction(): mixed
    {
        $action = $this->getMountedAction();

        if ($action === null) {
            return null;
        }

        $arguments = $this->mountedActions[0]['arguments'] ?? [];
        $closure = $action->getAction();

        $result = $closure !== null
            ? $action->evaluate($closure, ['arguments' => $arguments])
            : null;

        $this->unmountAction();

        return $result;
    }

    #[LiveAction]
    public function unmountAction(): void
    {
        $id = $this->getMountedModalId();

        $this->mountedActions = [];

        $this->dispatchBrowserEvent('sylament-close-modal', ['id' => $id]);
    }

    public function getMountedAction(): ?Action
    {
        $descriptor = $this->mountedActions[0] ?? null;

        if ($descriptor === null) {
            return null;
        }

        return $this->resolveAction($descriptor);
    }

    public function getAction(string $name): ?Action
    {
        $method = "{$name}Action";

        if (! method_exists($this, $method)) {
            return null;
        }

        $action = $this->{$method}();

        return $action instanceof Action ? $action->liveComponent($this) : null;
    }

    public function getMountedModalId(): string
    {
        $name = $this->mountedActions[0]['name'] ?? 'action';

        return "{$name}-modal";
    }

    /**
     * @param array{name: string, arguments: array<mixed>, context: array<mixed>} $descriptor
     */
    protected function resolveAction(array $descriptor): ?Action
    {
        return $this->getAction($descriptor['name']);
    }
}
