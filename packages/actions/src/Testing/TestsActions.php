<?php

declare(strict_types=1);

namespace Sylament\Actions\Testing;

use Closure;
use LogicException;
use PHPUnit\Framework\Assert;
use Sylament\Actions\Action;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Tables\Contracts\InteractsWithTable;

/**
 * @phpstan-require-extends Testable
 */
trait TestsActions
{
    /**
     * @param string|TestAction|array<mixed> $actions
     * @param array<string, mixed> $arguments
     */
    public function mountAction(string|TestAction|array $actions, array $arguments = []): static
    {
        foreach ($this->parseNestedActions($actions, $arguments) as $action) {
            $this->call('mountAction', $action);
        }

        return $this;
    }

    /**
     * @param string|TestAction|array<mixed> $actions
     * @param array<string, mixed> $data
     * @param array<string, mixed> $arguments
     */
    public function callAction(string|TestAction|array $actions, array $data = [], array $arguments = []): static
    {
        $initialMountedActionsCount = count($this->getTestedActionsHost()->getMountedActions());

        $this->assertActionVisible($actions, $arguments);

        $parsedActions = $this->parseNestedActions($actions, $arguments);

        $this->mountAction($actions, $arguments);

        if ($this->response()->isRedirection()) {
            return $this;
        }

        $host = $this->getTestedActionsHost();

        if (count($host->getMountedActions()) !== ($initialMountedActionsCount + count($parsedActions))) {
            return $this;
        }

        if ($host->getMountedAction()?->getName() !== (array_last($parsedActions)['name'] ?? null)) {
            return $this;
        }

        if ($data !== []) {
            $this->fillForm($data);
        }

        $this->callMountedAction($arguments);

        return $this;
    }

    /**
     * @param array<string, mixed> $arguments
     */
    public function callMountedAction(array $arguments = []): static
    {
        if ($this->getTestedActionsHost()->getMountedAction() === null) {
            return $this;
        }

        $this->call('callMountedAction', ['arguments' => $arguments]);

        return $this;
    }

    /**
     * @param string|TestAction|array<mixed> $actions
     * @param (Closure(Action): bool)|null $checkActionUsing
     * @param (Closure(string, string): string)|null $generateMessageUsing
     * @param array<string, mixed> $arguments
     */
    public function assertActionExists(string|TestAction|array $actions, ?Closure $checkActionUsing = null, ?Closure $generateMessageUsing = null, array $arguments = []): static
    {
        $parsedActions = $this->parseNestedActions($actions, $arguments);
        $host = $this->getTestedActionsHost();
        $action = $this->resolveTestedAction($host, $parsedActions);

        $hostClass = $host::class;
        $prettyName = implode(' > ', array_column($parsedActions, 'name'));

        Assert::assertInstanceOf(
            Action::class,
            $action,
            $generateMessageUsing instanceof Closure
                ? $generateMessageUsing($prettyName, $hostClass)
                : "Failed asserting that an action with name [{$prettyName}] exists on the [{$hostClass}] component.",
        );

        if ($checkActionUsing instanceof Closure) {
            Assert::assertTrue(
                $checkActionUsing($action),
                $generateMessageUsing instanceof Closure
                    ? $generateMessageUsing($prettyName, $hostClass)
                    : "Failed asserting that an action with the name [{$prettyName}] and provided configuration exists on the [{$hostClass}] component.",
            );
        }

        return $this;
    }

    /**
     * @param string|TestAction|array<mixed> $actions
     * @param array<string, mixed> $arguments
     */
    public function assertActionVisible(string|TestAction|array $actions, array $arguments = []): static
    {
        return $this->assertActionExists(
            $actions,
            checkActionUsing: fn (Action $action): bool => $action->isVisible(),
            generateMessageUsing: fn (string $prettyName, string $hostClass): string => "Failed asserting that an action with name [{$prettyName}] is visible on the [{$hostClass}] component.",
            arguments: $arguments,
        );
    }

    /**
     * Turns a name, a dotted path, a `TestAction` or a list of either into the
     * descriptors the host's `mountAction()` takes, one per nesting level.
     *
     * @param string|TestAction|array<mixed> $actions
     * @param array<string, mixed> $arguments
     * @return list<array{name: string, arguments: array<mixed>, context: array<mixed>}>
     */
    public function parseNestedActions(string|TestAction|array $actions, array $arguments = [], bool $areRelativeToMountedActions = true): array
    {
        $host = $this->getTestedActionsHost();
        $initialMountedActionsCount = $areRelativeToMountedActions ? count($host->getMountedActions()) : 0;

        if (is_string($actions)) {
            $actions = explode('.', $actions);
        } elseif (($actions instanceof TestAction) || array_key_exists('name', $actions)) {
            $actions = [$actions];
        }

        $areArgumentsKeyedByActionName = false;
        $parsedActions = [];

        foreach (array_values($actions) as $actionNestingIndex => $action) {
            if (is_string($action)) {
                $action = ['name' => $action];
            } elseif ($action instanceof TestAction) {
                $absoluteNestingIndex = $initialMountedActionsCount + $actionNestingIndex;

                $action = $action->toArray(defaultSchema: ($absoluteNestingIndex > 0)
                    ? 'mountedActionSchema' . ($absoluteNestingIndex - 1)
                    : $this->getDefaultTestingSchemaName());
            }

            if (!is_array($action) || !is_string($action['name'] ?? null)) {
                throw new LogicException("Action name at index [{$actionNestingIndex}] is not specified.");
            }

            $actionName = $action['name'];

            if (class_exists($actionName) && is_subclass_of($actionName, Action::class)) {
                $actionName = $actionName::getDefaultName()
                    ?? throw new LogicException("The action class [{$actionName}] has no default name to mount it by.");
            }

            $actionArguments = $action['arguments'] ?? null;

            if (($arguments !== []) && ($actionArguments === null)) {
                if (array_key_exists($actionName, $arguments)) {
                    $actionArguments = $arguments[$actionName];
                    $areArgumentsKeyedByActionName = true;
                } elseif (!$areArgumentsKeyedByActionName) {
                    $actionArguments = $arguments;
                }
            }

            $context = $action['context'] ?? [];

            if (!is_array($actionArguments ?? []) || !is_array($context)) {
                throw new LogicException("The arguments and context of action [{$actionName}] must be arrays.");
            }

            if (($context['table'] ?? false) && is_object($context['recordKey'] ?? null)) {
                $context['recordKey'] = $this->getTestedTableRecordKey($host, $context['recordKey']);
            }

            $parsedActions[] = [
                'name' => $actionName,
                'arguments' => $actionArguments ?? [],
                'context' => $context,
            ];
        }

        return $parsedActions;
    }

    protected function getMountedActionSchemaName(): ?string
    {
        $host = $this->component();
        $mountedActions = ($host instanceof InteractsWithActions) ? $host->getMountedActions() : [];

        if ($mountedActions === []) {
            return null;
        }

        return 'mountedActionSchema' . array_key_last($mountedActions);
    }

    private function getTestedActionsHost(): InteractsWithActions
    {
        $host = $this->component();

        if (!$host instanceof InteractsWithActions) {
            throw new LogicException(sprintf('The [%s] component mounts no actions: it does not implement [%s].', $host::class, InteractsWithActions::class));
        }

        return $host;
    }

    /**
     * Resolves the path through the host's own resolution, on the copy `component()`
     * hydrated for this read: the descriptors join its mounted stack, and the action
     * exists only when every level resolves.
     *
     * @param list<array{name: string, arguments: array<mixed>, context: array<mixed>}> $parsedActions
     */
    private function resolveTestedAction(InteractsWithActions $host, array $parsedActions): ?Action
    {
        if (!property_exists($host, 'mountedActions') || !is_array($host->mountedActions)) {
            throw new LogicException(sprintf('The [%s] component keeps no mounted-action stack.', $host::class));
        }

        $path = [...array_values($host->mountedActions), ...$parsedActions];
        $host->mountedActions = $path;

        $resolvedActions = $host->getMountedActions();

        return (count($resolvedActions) === count($path)) ? array_last($resolvedActions) : null;
    }

    private function getTestedTableRecordKey(InteractsWithActions $host, object $record): string
    {
        if (!$host instanceof InteractsWithTable) {
            throw new LogicException(sprintf('A table action names a record, but the [%s] component is not a table host: it does not implement [%s].', $host::class, InteractsWithTable::class));
        }

        return $host->getTableRecordKey($record);
    }
}
