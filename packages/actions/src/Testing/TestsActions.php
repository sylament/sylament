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
            $this->call('mountAction', [...$action, 'arguments' => $action['arguments'] ?? []]);
        }

        return $this;
    }

    public function unmountAction(bool|string|null $cancelParentActions = null): static
    {
        $this->call('unmountAction', ['cancelParentActions' => $cancelParentActions]);

        return $this;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function setActionData(array $data): static
    {
        $this->fillForm($data);

        return $this;
    }

    /**
     * @param array<string, mixed>|Closure $data
     */
    public function assertActionDataSet(array|Closure $data): static
    {
        $this->assertSchemaStateSet($data);

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
     * With no actions it asserts that any action is mounted. Otherwise the parsed levels
     * are compared with the end of the mounted stack: each level's name and context, and
     * its arguments only when the call gave some.
     *
     * @param string|TestAction|array<mixed> $actions
     */
    public function assertActionMounted(string|TestAction|array $actions = []): static
    {
        $mountedActions = $this->getTestedMountedActionFrames();

        if ($actions === []) {
            Assert::assertNotEquals([], $mountedActions, 'Failed asserting that an action is mounted.');

            return $this;
        }

        $originalActions = is_array($actions) ? $actions : [$actions];
        $parsedActions = $this->parseNestedActions($actions, areRelativeToMountedActions: false);
        $actionNestingIndexOffset = count($mountedActions) - count($parsedActions);

        foreach ($parsedActions as $actionNestingIndex => $action) {
            $mountedAction = $mountedActions[$actionNestingIndex + $actionNestingIndexOffset] ?? [];

            Assert::assertEquals($action['name'], $mountedAction['name'] ?? null, "Failed asserting that the action [{$action['name']}] is mounted.");

            if (array_key_exists('arguments', $action)) {
                Assert::assertEquals($action['arguments'], $mountedAction['arguments'] ?? null, "Failed asserting that the action [{$action['name']}] is mounted with the expected arguments.");
            }

            if (($originalAction = array_shift($originalActions)) instanceof TestAction) {
                Assert::assertTrue(
                    $originalAction->checkArguments($this->getTestedMountedActionArguments($mountedAction)),
                    "Failed asserting that the mounted arguments for the action [{$action['name']}] match the expected arguments.",
                );
            }

            Assert::assertEquals($action['context'], $mountedAction['context'] ?? null, "Failed asserting that the action [{$action['name']}] is mounted in the expected context.");
        }

        return $this;
    }

    /**
     * With no actions it asserts that no action is mounted. Otherwise it fails only when
     * every parsed level matches the end of the mounted stack exactly, as
     * {@see assertActionMounted()} compares it.
     *
     * @param string|TestAction|array<mixed> $actions
     */
    public function assertActionNotMounted(string|TestAction|array $actions = []): static
    {
        $mountedActions = $this->getTestedMountedActionFrames();

        if ($actions === []) {
            Assert::assertEquals([], $mountedActions, 'Failed asserting that no action is mounted.');

            return $this;
        }

        $originalActions = is_array($actions) ? $actions : [$actions];
        $parsedActions = $this->parseNestedActions($actions, areRelativeToMountedActions: false);
        $actionNestingIndexOffset = count($mountedActions) - count($parsedActions);

        foreach ($parsedActions as $actionNestingIndex => $action) {
            $mountedAction = $mountedActions[$actionNestingIndex + $actionNestingIndexOffset] ?? [];

            if (($mountedAction['name'] ?? null) !== $action['name']) {
                return $this;
            }

            if (array_key_exists('arguments', $action) && (($mountedAction['arguments'] ?? null) !== $action['arguments'])) {
                return $this;
            }

            if (
                (($originalAction = array_shift($originalActions)) instanceof TestAction)
                && !$originalAction->checkArguments($this->getTestedMountedActionArguments($mountedAction))
            ) {
                return $this;
            }

            if (($mountedAction['context'] ?? null) !== $action['context']) {
                return $this;
            }
        }

        Assert::fail('Failed asserting that the action is not mounted.');
    }

    /**
     * @param string|TestAction|array<mixed> $actions
     */
    public function assertActionHalted(string|TestAction|array $actions = []): static
    {
        return $this->assertActionMounted($actions);
    }

    /**
     * @param array<int|string, string> $keys
     */
    public function assertHasActionErrors(array $keys = []): static
    {
        $this->assertHasFormErrors($keys);

        return $this;
    }

    /**
     * @param array<int|string, string> $keys
     */
    public function assertHasNoActionErrors(array $keys = []): static
    {
        $this->assertHasNoFormErrors($keys);

        return $this;
    }

    /**
     * Turns a name, a dotted path, a `TestAction` or a list of either into the
     * descriptors the host's `mountAction()` takes, one per nesting level; a level is
     * given `arguments` only when the call names some, as Filament's is.
     *
     * @param string|TestAction|array<mixed> $actions
     * @param array<string, mixed> $arguments
     * @return list<array{name: string, arguments?: array<mixed>, context: array<mixed>}>
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

            if ((($actionArguments !== null) && !is_array($actionArguments)) || !is_array($context)) {
                throw new LogicException("The arguments and context of action [{$actionName}] must be arrays.");
            }

            if (($context['table'] ?? false) && is_object($context['recordKey'] ?? null)) {
                $context['recordKey'] = $this->getTestedTableRecordKey($host, $context['recordKey']);
            }

            $parsedActions[] = [
                'name' => $actionName,
                ...(($actionArguments !== null) ? ['arguments' => $actionArguments] : []),
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
     * @param list<array{name: string, arguments?: array<mixed>, context: array<mixed>}> $parsedActions
     */
    private function resolveTestedAction(InteractsWithActions $host, array $parsedActions): ?Action
    {
        if (!property_exists($host, 'mountedActions') || !is_array($host->mountedActions)) {
            throw new LogicException(sprintf('The [%s] component keeps no mounted-action stack.', $host::class));
        }

        $path = [
            ...array_values($host->mountedActions),
            ...array_map(static fn (array $action): array => [...$action, 'arguments' => $action['arguments'] ?? []], $parsedActions),
        ];
        $host->mountedActions = $path;

        $resolvedActions = $host->getMountedActions();

        return (count($resolvedActions) === count($path)) ? array_last($resolvedActions) : null;
    }

    /**
     * The frames the re-hydrated host keeps, `{name, arguments, context}` each, read raw:
     * an action's own context is rebuilt on resolution and can differ from the frame.
     *
     * @return list<array<mixed>>
     */
    private function getTestedMountedActionFrames(): array
    {
        $host = $this->getTestedActionsHost();

        if (!property_exists($host, 'mountedActions') || !is_array($host->mountedActions)) {
            throw new LogicException(sprintf('The [%s] component keeps no mounted-action stack.', $host::class));
        }

        $frames = [];

        foreach ($host->mountedActions as $frame) {
            if (!is_array($frame)) {
                throw new LogicException(sprintf('The [%s] component keeps a mounted-action frame that is not an array.', $host::class));
            }

            $frames[] = $frame;
        }

        return $frames;
    }

    /**
     * @param array<mixed> $mountedAction
     * @return array<string, mixed>
     */
    private function getTestedMountedActionArguments(array $mountedAction): array
    {
        $arguments = $mountedAction['arguments'] ?? [];
        $namedArguments = [];

        if (!is_array($arguments)) {
            throw new LogicException('A mounted action frame keeps arguments that are not an array.');
        }

        foreach ($arguments as $name => $value) {
            if (!is_string($name)) {
                throw new LogicException('A mounted action frame keeps arguments that are not named.');
            }

            $namedArguments[$name] = $value;
        }

        return $namedArguments;
    }

    private function getTestedTableRecordKey(InteractsWithActions $host, object $record): string
    {
        if (!$host instanceof InteractsWithTable) {
            throw new LogicException(sprintf('A table action names a record, but the [%s] component is not a table host: it does not implement [%s].', $host::class, InteractsWithTable::class));
        }

        return $host->getTableRecordKey($record);
    }
}
