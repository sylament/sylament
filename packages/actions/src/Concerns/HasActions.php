<?php

declare(strict_types=1);

namespace Sylament\Actions\Concerns;

use Closure;
use LogicException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;
use Sylament\Actions\Action;
use Sylament\Actions\Enums\ActionStatus;
use Sylament\Actions\Exceptions\ActionNotResolvableException;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Exceptions\ValidationException;
use Sylament\Schemas\Schema;
use Sylament\Support\Exceptions\Cancel;
use Sylament\Support\Exceptions\Halt;
use Sylament\Tables\Contracts\InteractsWithTable;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Attribute\PostHydrate;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Throwable;

use function Sylament\Support\blank;
use function Sylament\Support\filled;

trait HasActions
{
    use ComponentToolsTrait;

    /**
     * @var array<int, array{name: string, arguments: array<string, mixed>, context: array<string, mixed>}>
     */
    #[LiveProp(dehydrateWith: 'dehydrateMountedActions')]
    public array $mountedActions = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[LiveProp(writable: true)]
    public array $mountedActionsData = [];

    #[LiveProp]
    public ?string $actionsHostId = null;

    /**
     * @var array<string, Action>
     */
    protected array $cachedActions = [];

    /**
     * @var array<Action>|null
     */
    protected ?array $cachedMountedActions = null;

    /**
     * @var array<int, bool>
     */
    protected array $cachedMountedActionHasSchema = [];

    protected ?Closure $resolveRecordUsing = null;

    public function resolveRecordUsing(?Closure $callback): static
    {
        $this->resolveRecordUsing = $callback;

        return $this;
    }

    protected function getMountedActionRecord(): mixed
    {
        $descriptor = $this->mountedActions === []
            ? null
            : array_last($this->mountedActions);

        $recordKey = $descriptor['context']['recordKey'] ?? null;

        if ($recordKey === null) {
            return null;
        }

        if ($this instanceof InteractsWithTable && filled($descriptor['context']['table'] ?? null)) {
            return null;
        }

        if ($this->resolveRecordUsing === null) {
            throw new LogicException('An action context supplied a recordKey but the host registered no resolveRecordUsing() callback to resolve it.');
        }

        return ($this->resolveRecordUsing)($recordKey);
    }

    /**
     * @param array<string, mixed> $arguments
     * @param array<string, mixed> $context
     */
    #[LiveAction]
    public function mountAction(
        #[LiveArg] string $name,
        #[LiveArg] array $arguments = [],
        #[LiveArg] array $context = [],
    ): mixed {
        $this->mountedActions[] = [
            'name' => $name,
            'arguments' => $arguments,
            'context' => $context,
        ];

        $this->mountedActionsData[array_key_last($this->mountedActions)] = [];

        $action = $this->getMountedAction();

        if ($action === null || $action->isDisabled()) {
            $this->unmountAction(cancelParentActions: false);

            return null;
        }

        try {
            $hasSchema = $this->mountedActionHasSchema($action);

            if ($hasSchema) {
                $action->callBeforeFormFilled();
            }

            $schema = $this->getMountedActionSchema(mountedAction: $action);

            $action->mount([
                'form' => $schema,
                'schema' => $schema,
            ]);

            if ($hasSchema) {
                $action->callAfterFormFilled();
            }
        } catch (Halt|Cancel) {
            $this->unmountAction(cancelParentActions: false);

            return null;
        } catch (ValidationException $exception) {
            $this->unmountAction(cancelParentActions: false);

            throw $exception;
        }

        if (! $this->mountedActionShouldOpenModal($action)) {
            return $this->callMountedAction();
        }

        if ($this instanceof InteractsWithSchemas) {
            $this->clearSchemaValidation('mountedActionSchema' . array_key_last($this->mountedActions));
        }

        $this->dispatchBrowserEvent('sylament-open-modal', ['id' => $this->getMountedModalId()]);

        return null;
    }

    /**
     * @param array<string, mixed> $arguments
     */
    #[LiveAction]
    public function callMountedAction(#[LiveArg] array $arguments = []): mixed
    {
        $action = $this->getMountedAction();

        if ($action === null) {
            return null;
        }

        $originalArguments = $action->getArguments();
        $action->mergeArguments($arguments);

        if (filled($record = $this->getMountedActionRecord())) {
            $action->record($record);
        }

        if ($action->isDisabled()) {
            return null;
        }

        $originallyMountedActions = $this->mountedActions;

        $result = null;
        $schema = null;
        $hasFinalizedDatabaseTransaction = false;

        try {
            $action->beginDatabaseTransaction();

            if ($this->mountedActionHasSchema($action)) {
                $action->callBeforeFormValidated();

                $schema = $this->getMountedActionSchema(mountedAction: $action);

                if ($schema instanceof Schema) {
                    $state = $schema->getState();

                    $action->callAfterFormValidated();
                    $action->data($state);
                    $action->callBefore();
                }
            } else {
                $action->callBefore();
            }

            $result = $action->call($schema instanceof Schema ? [
                'form' => $schema,
                'schema' => $schema,
            ] : []);
            $result = $action->callAfter() ?? $result;

            $this->afterActionCalled($action);

            if ($action->getStatus() === ActionStatus::Success) {
                $action->sendSuccessNotification();
                $action->dispatchSuccessRedirect();
            } else {
                $action->sendFailureNotification();
                $action->dispatchFailureRedirect();
            }
        } catch (Halt $exception) {
            $exception->shouldRollbackDatabaseTransaction()
                ? $action->rollBackDatabaseTransaction()
                : $action->commitDatabaseTransaction();

            return null;
        } catch (Cancel $exception) {
            $exception->shouldRollbackDatabaseTransaction()
                ? $action->rollBackDatabaseTransaction()
                : $action->commitDatabaseTransaction();

            $hasFinalizedDatabaseTransaction = true;

            // Falls through to unmount — Cancel closes the modal.
        } catch (ValidationException $exception) {
            $action->rollBackDatabaseTransaction();

            if (! $this->mountedActionShouldOpenModal($action)) {
                $action->arguments($originalArguments);
                $action->resetData();
                $this->unmountAction();
            }

            throw $exception;
        } catch (Throwable $exception) {
            $action->rollBackDatabaseTransaction();

            throw $exception;
        }

        if (! $hasFinalizedDatabaseTransaction) {
            $action->commitDatabaseTransaction();
        }

        if (filled($redirectUrl = $action->getPendingRedirectUrl())) {
            $this->unmountAction();

            return new RedirectResponse($redirectUrl);
        }

        $action->arguments($originalArguments);

        if (self::onlyActionNamesAndContexts($originallyMountedActions) !== self::onlyActionNamesAndContexts($this->mountedActions)) {
            return null;
        }

        $this->unmountAction();

        return $result;
    }

    protected function afterActionCalled(Action $action): void
    {
    }

    /**
     * The identity a mounted stack is compared by while an action runs: an
     * action that replaced itself changed a name or a context, where one that
     * merely re-read its arguments did not.
     *
     * @param array<int, array{name: string, arguments: array<string, mixed>, context: array<string, mixed>}> $mountedActions
     *
     * @return array<int, array{name: string, context: array<string, mixed>}>
     */
    private static function onlyActionNamesAndContexts(array $mountedActions): array
    {
        return array_map(
            static fn (array $mountedAction): array => [
                'name' => $mountedAction['name'],
                'context' => $mountedAction['context'],
            ],
            $mountedActions,
        );
    }

    /**
     * @param array<string, mixed> $arguments
     * @param array<string, mixed> $context
     */
    public function replaceMountedAction(string $name, array $arguments = [], array $context = []): void
    {
        $staleSchemaIndexes = array_unique([
            ...array_keys($this->mountedActions),
            ...array_keys($this->mountedActionsData),
        ]);

        $this->mountedActions = [];
        $this->cachedMountedActions = null;
        $this->mountedActionsData = [];
        $this->cachedMountedActionHasSchema = [];

        foreach ($staleSchemaIndexes as $actionNestingIndex) {
            $this->forgetMountedActionSchema($actionNestingIndex);
        }

        $this->mountAction($name, $arguments, $context);
    }

    #[PostHydrate(priority: 10)]
    public function afterMountedActionsHydrated(): void
    {
        if ($this->mountedActions === []) {
            return;
        }

        if ($this->cacheMountedActions($this->mountedActions) === []) {
            $this->mountedActions = [];
        }
    }

    #[LiveAction]
    public function unmountAction(#[LiveArg] bool|string|null $cancelParentActions = null): void
    {
        $id = $this->getMountedModalId();

        $action = $this->getMountedAction();

        if (($cancelParentActions === false) || ($action === null)) {
            array_pop($this->mountedActions);
        } elseif (
            ($cancelParentActions === true)
            || (($cancelParentActions === null) && $action->shouldCancelAllParentActions())
        ) {
            $this->mountedActions = [];
        } else {
            $parentActionToCancelTo = is_string($cancelParentActions)
                ? $cancelParentActions
                : $action->getParentActionToCancelTo();

            while (true) {
                $recentlyClosedParentAction = array_pop($this->mountedActions);

                if (
                    blank($parentActionToCancelTo)
                    || ($recentlyClosedParentAction === null)
                    || ($recentlyClosedParentAction['name'] === $parentActionToCancelTo)
                ) {
                    break;
                }
            }
        }

        while (count($this->cachedMountedActions ?? []) > count($this->mountedActions)) {
            array_pop($this->cachedMountedActions);
        }

        foreach (array_keys($this->mountedActionsData) as $actionNestingIndex) {
            if ($actionNestingIndex < count($this->mountedActions)) {
                continue;
            }

            unset($this->mountedActionsData[$actionNestingIndex], $this->cachedMountedActionHasSchema[$actionNestingIndex]);

            $this->forgetMountedActionSchema($actionNestingIndex);
        }

        $this->dispatchBrowserEvent('sylament-close-modal', ['id' => $id]);
    }

    public function getMountedAction(?int $actionNestingIndex = null): ?Action
    {
        if ($this->mountedActions === []) {
            return null;
        }

        $actionNestingIndex ??= array_key_last($this->mountedActions);

        if (array_key_exists((string) $actionNestingIndex, $this->cachedMountedActions ?? [])) {
            return $this->cachedMountedActions[$actionNestingIndex];
        }

        $cached = $this->cacheMountedActions(array_slice($this->mountedActions, 0, $actionNestingIndex + 1));

        return $cached === [] ? null : array_last($cached);
    }

    /**
     * @return array<Action>
     */
    public function getMountedActions(): array
    {
        if ($this->mountedActions === []) {
            return [];
        }

        $cachedMountedActions = $this->cachedMountedActions ?? [];

        if (array_key_exists(count($this->mountedActions) - 1, $cachedMountedActions)) {
            return $cachedMountedActions;
        }

        return $this->cacheMountedActions($this->mountedActions);
    }

    /**
     * @return array<string, mixed>
     */
    public function getMountedActionData(?int $actionNestingIndex = null): array
    {
        if ($this->mountedActions === []) {
            return [];
        }

        $actionNestingIndex ??= array_key_last($this->mountedActions);

        return $this->mountedActionsData[$actionNestingIndex] ?? [];
    }

    public function getMountedActionSchema(?int $actionNestingIndex = null, ?Action $mountedAction = null): ?Schema
    {
        if ($this->mountedActions === []) {
            return null;
        }

        $actionNestingIndex ??= $mountedAction?->getNestingIndex() ?? array_key_last($this->mountedActions);

        $mountedAction ??= $this->getMountedAction($actionNestingIndex);

        if (! $mountedAction instanceof Action) {
            return null;
        }

        if (! $this instanceof InteractsWithSchemas) {
            if ($mountedAction->getSchema(Schema::make()) instanceof Schema) {
                throw new LogicException("Action [{$mountedAction->getName()}] declares a schema but " . static::class . ' is not a schema host — implement ' . InteractsWithSchemas::class . ' and use the ' . HasSchemas::class . ' trait.');
            }

            return null;
        }

        $schemaName = "mountedActionSchema{$actionNestingIndex}";

        if ($this->hasCachedSchema($schemaName)) {
            return $this->getSchema($schemaName);
        }

        return $this->cacheSchema($schemaName, $mountedAction->getSchema(
            $this->makeSchema()
                ->key($schemaName)
                ->statePath("mountedActionsData.{$actionNestingIndex}")
                ->rootHeadingLevel(3),
        ));
    }

    public function mountedActionHasSchema(?Action $mountedAction = null): bool
    {
        if ($this->mountedActions === []) {
            return false;
        }

        $actionNestingIndex = $mountedAction?->getNestingIndex() ?? array_key_last($this->mountedActions);

        return $this->cachedMountedActionHasSchema[$actionNestingIndex] ??= count(
            $this->getMountedActionSchema($actionNestingIndex, $mountedAction)?->getComponents() ?? [],
        ) > 0;
    }

    protected function forgetMountedActionSchema(int $actionNestingIndex): void
    {
        if (! $this instanceof InteractsWithSchemas) {
            return;
        }

        $this->cacheSchema("mountedActionSchema{$actionNestingIndex}", null);
        $this->clearSchemaValidation("mountedActionSchema{$actionNestingIndex}");
    }

    /**
     * @return array<int, array{name: string, arguments: array<string, mixed>, context: array<string, mixed>}>
     */
    public function dehydrateMountedActions(): array
    {
        return $this->mountedActions;
    }

    /**
     * @param array<int, array{name: string, arguments: array<string, mixed>, context: array<string, mixed>}> $mountedActions
     * @return array<Action>
     */
    protected function cacheMountedActions(array $mountedActions): array
    {
        try {
            return $this->cachedMountedActions = $this->resolveActions($mountedActions);
        } catch (ActionNotResolvableException) {
            return $this->cachedMountedActions = [];
        }
    }

    /**
     * @param array<int, array{name: string, arguments: array<string, mixed>, context: array<string, mixed>}> $actions
     * @return array<Action>
     */
    protected function resolveActions(array $actions): array
    {
        $resolvedActions = [];

        foreach ($actions as $actionNestingIndex => $descriptor) {
            if (mb_trim($descriptor['name']) === '') {
                throw new ActionNotResolvableException('An action tried to resolve without a name.');
            }

            if (filled($descriptor['context']['schemaComponent'] ?? null)) {
                $resolvedAction = $this->resolveSchemaComponentAction($descriptor, $resolvedActions);
            } elseif ($this instanceof InteractsWithTable && filled($descriptor['context']['table'] ?? null)) {
                $resolvedAction = $this->resolveTableAction($descriptor, $resolvedActions);
            } else {
                $resolvedAction = $this->resolveAction($descriptor, $resolvedActions);
            }

            if ($resolvedAction === null) {
                break;
            }

            if ($descriptor['arguments'] !== []) {
                $resolvedAction->mergeArguments($descriptor['arguments']);
            }

            $resolvedAction->nestingIndex($actionNestingIndex);

            $resolvedActions[] = $resolvedAction;

            if ($this instanceof InteractsWithSchemas) {
                $this->cacheSchema(
                    "mountedActionSchema{$actionNestingIndex}",
                    $this->getMountedActionSchema($actionNestingIndex, $resolvedAction),
                );
            }
        }

        return $resolvedActions;
    }

    public function mountedActionShouldOpenModal(?Action $mountedAction = null): bool
    {
        return ($mountedAction ?? $this->getMountedAction())?->shouldOpenModal(
            checkForSchemaUsing: $this->mountedActionHasSchema(...),
        ) ?? false;
    }

    public function getAction(string $name): ?Action
    {
        if (array_key_exists($name, $this->cachedActions)) {
            return $this->cachedActions[$name];
        }

        if ((! str_ends_with($name, 'Action')) && method_exists($this, $method = "{$name}Action")) {
            $methodName = $method;
        } elseif (method_exists($this, $name)) {
            $methodName = $name;
        } else {
            return null;
        }

        $returnTypeReflection = new ReflectionMethod($this, $methodName)->getReturnType();

        if ($returnTypeReflection === null) {
            return null;
        }

        $returnTypes = $returnTypeReflection instanceof ReflectionUnionType
            ? $returnTypeReflection->getTypes()
            : [$returnTypeReflection];

        $hasActionReturnType = array_any(
            $returnTypes,
            fn (ReflectionType $returnType): bool => $returnType instanceof ReflectionNamedType
                && is_a($returnType->getName(), Action::class, allow_string: true),
        );

        if (! $hasActionReturnType) {
            return null;
        }

        $action = $this->{$methodName}();

        if (! $action instanceof Action) {
            throw new ActionNotResolvableException('Actions must be an instance of ' . Action::class . ". The [{$methodName}] method on the live component returned an instance of [" . get_debug_type($action) . '].');
        }

        return $this->cacheAction($action);
    }

    public function getDefaultActionUrl(Action $action): ?string
    {
        return null;
    }

    public function getDefaultActionSuccessRedirectUrl(Action $action): ?string
    {
        return null;
    }

    public function getDefaultActionFailureRedirectUrl(Action $action): ?string
    {
        return null;
    }

    public function cacheAction(Action $action): Action
    {
        $action->liveComponent($this);

        return $this->cachedActions[$action->getName()] = $action;
    }

    public function getActionsHostId(): string
    {
        return $this->actionsHostId ??= bin2hex(random_bytes(4));
    }

    public function getMountedModalId(?int $actionNestingIndex = null): string
    {
        $actionNestingIndex ??= ($this->mountedActions === []) ? 0 : array_key_last($this->mountedActions);

        return "sy-{$this->getActionsHostId()}-action-{$actionNestingIndex}";
    }

    public function isMountedActionModalOpen(int $actionNestingIndex): bool
    {
        if ($this->mountedActions === []) {
            return false;
        }

        if ($actionNestingIndex === array_key_last($this->mountedActions)) {
            return $this->mountedActionShouldOpenModal();
        }

        foreach ($this->getMountedActions() as $mountedAction) {
            if (($mountedAction->getNestingIndex() ?? 0) <= $actionNestingIndex) {
                continue;
            }

            if (! $this->mountedActionShouldOpenModal($mountedAction)) {
                continue;
            }

            if (! $mountedAction->shouldOverlayParentActions()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array{name: string, arguments: array<string, mixed>, context: array<string, mixed>} $descriptor
     * @param array<Action> $parentActions
     */
    protected function resolveAction(array $descriptor, array $parentActions): ?Action
    {
        if ($parentActions !== []) {
            $parentAction = array_last($parentActions);

            return $parentAction->getModalAction($descriptor['name'])
                ?? throw new ActionNotResolvableException("Action [{$descriptor['name']}] was not found for action [{$parentAction->getName()}].");
        }

        return $this->getAction($descriptor['name']);
    }

    /**
     * @param array{name: string, arguments: array<string, mixed>, context: array<string, mixed>} $descriptor
     * @param array<Action> $parentActions
     */
    protected function resolveTableAction(array $descriptor, array $parentActions): ?Action
    {
        if (! $this instanceof InteractsWithTable) {
            throw new LogicException('An action context supplied a table flag but ' . static::class . ' is not a table host — implement ' . InteractsWithTable::class . '.');
        }

        if ($parentActions !== []) {
            $parentAction = array_last($parentActions);

            return $parentAction->getModalAction($descriptor['name'])
                ?? throw new ActionNotResolvableException("Action [{$descriptor['name']}] was not found for action [{$parentAction->getName()}].");
        }

        $resolvedAction = null;

        if ($descriptor['context']['bulk'] ?? false) {
            $resolvedAction = $this->getTable()->getBulkAction($descriptor['name']);
        }

        $resolvedAction ??= $this->getTable()->getAction($descriptor['name'])
            ?? throw new ActionNotResolvableException("Action [{$descriptor['name']}] not found on table.");

        $recordKey = $descriptor['context']['recordKey'] ?? null;

        if (filled($recordKey)) {
            if (! is_scalar($recordKey)) {
                throw new ActionNotResolvableException(sprintf('Record key of type [%s] is not a valid identifier.', get_debug_type($recordKey)));
            }

            $record = $this->getTableRecord((string) $recordKey);

            if ($record === null) {
                throw new ActionNotResolvableException(sprintf('Record [%s] no longer exists.', (string) $recordKey));
            }

            $resolvedAction->getRootGroup()?->record($record) ?? $resolvedAction->record($record);
        }

        return $resolvedAction;
    }

    /**
     * @param array{name: string, arguments: array<string, mixed>, context: array<string, mixed>} $descriptor
     * @param array<Action> $parentActions
     */
    protected function resolveSchemaComponentAction(array $descriptor, array $parentActions): ?Action
    {
        if (! $this instanceof InteractsWithSchemas) {
            throw new LogicException('An action context supplied a schemaComponent key but ' . static::class . ' is not a schema host — implement ' . InteractsWithSchemas::class . ' and use the ' . HasSchemas::class . ' trait.');
        }

        $key = $descriptor['context']['schemaComponent'] ?? null;

        if (! is_string($key)) {
            return null;
        }

        $dotPosition = mb_strpos($key, '.');
        $schemaName = ($dotPosition === false) ? $key : mb_substr($key, 0, $dotPosition);
        $nestedContainerKey = ($dotPosition === false) ? null : mb_substr($key, $dotPosition + 1);

        if (($parentActions !== []) && (! $this->hasCachedSchema($schemaName))) {
            return null;
        }

        $schema = $this->resolveSchemaByName($schemaName);

        if (!$schema instanceof Schema) {
            return null;
        }

        $action = $schema->getAction($descriptor['name'], $nestedContainerKey);

        return $action?->liveComponent($this);
    }
}
