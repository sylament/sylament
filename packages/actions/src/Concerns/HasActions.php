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
use Sylament\Schemas\Schema;
use Sylament\Support\Exceptions\Cancel;
use Sylament\Support\Exceptions\Halt;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;

use function Sylament\Support\blank;
use function Sylament\Support\filled;

trait HasActions
{
    use ComponentToolsTrait;

    /**
     * @var array<int, array{name: string, arguments: array<mixed>, context: array<mixed>}>
     */
    #[LiveProp]
    public array $mountedActions = [];

    /**
     * @var array<string, Action>
     */
    protected array $cachedActions = [];

    protected ?Closure $resolveRecordUsing = null;

    public function resolveRecordUsing(?Closure $callback): static
    {
        $this->resolveRecordUsing = $callback;

        return $this;
    }

    protected function getMountedActionRecord(): mixed
    {
        $recordKey = $this->mountedActions[0]['context']['recordKey'] ?? null;

        if ($recordKey === null) {
            return null;
        }

        if ($this->resolveRecordUsing === null) {
            throw new LogicException('An action context supplied a recordKey but the host registered no resolveRecordUsing() callback to resolve it.');
        }

        return ($this->resolveRecordUsing)($recordKey);
    }

    /**
     * @param array<mixed> $arguments
     * @param array<mixed> $context
     */
    #[LiveAction]
    public function mountAction(
        #[LiveArg] string $name,
        #[LiveArg] array $arguments = [],
        #[LiveArg] array $context = [],
    ): mixed {
        $this->mountedActions = [[
            'name' => $name,
            'arguments' => $arguments,
            'context' => $context,
        ]];

        try {
            $action = $this->getMountedAction();
        } catch (ActionNotResolvableException) {
            $action = null;
        }

        if ($action === null || $action->isDisabled()) {
            $this->unmountAction();

            return null;
        }

        if (! $this->mountedActionShouldOpenModal($action)) {
            return $this->callMountedAction();
        }

        $this->dispatchBrowserEvent('sylament-open-modal', ['id' => $this->getMountedModalId()]);

        return null;
    }

    #[LiveAction]
    public function callMountedAction(): mixed
    {
        $action = $this->getMountedAction();

        if ($action === null) {
            return null;
        }

        $action->mergeArguments($this->mountedActions[0]['arguments'] ?? []);
        $action->record($this->getMountedActionRecord());

        if ($action->isDisabled()) {
            return null;
        }

        $result = null;

        try {
            $action->callBefore();
            $result = $action->call();
            $result = $action->callAfter() ?? $result;

            $this->afterActionCalled($action);

            if ($action->getStatus() === ActionStatus::Success) {
                $action->sendSuccessNotification();
            } else {
                $action->sendFailureNotification();
            }
        } catch (Halt) {
            return null;
        } catch (Cancel) {
            // Falls through to unmount — Cancel closes the modal.
        }

        $this->unmountAction();

        return $result;
    }

    protected function afterActionCalled(Action $action): void
    {
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

    public function mountedActionShouldOpenModal(?Action $mountedAction = null): bool
    {
        return ($mountedAction ?? $this->getMountedAction())?->shouldOpenModal() ?? false;
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

    public function cacheAction(Action $action): Action
    {
        $action->liveComponent($this);

        return $this->cachedActions[$action->getName()] = $action;
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
        if (blank($descriptor['name'])) {
            throw new ActionNotResolvableException('An action tried to resolve without a name.');
        }

        if (filled($descriptor['context']['schemaComponent'] ?? null)) {
            return $this->resolveSchemaComponentAction($descriptor);
        }

        return $this->getAction($descriptor['name']);
    }

    /**
     * Resolve the descriptor against the host schema its `context['schemaComponent']`
     * key names: the segment before the first dot is the schema name, the rest is
     * the nested container key handed to `Schema::getAction()`. Unresolvable
     * client references — unknown schema name, unknown action — return null so the
     * mount no-ops; a host that cannot carry schemas at all is a wiring bug and
     * throws. The schema-name miss check ports the discovery guard of Filament's
     * `cacheSchema()`: the candidate `{$name}()` / `{$name}Schema()` method must
     * be schema-shaped — first parameter typed `Schema`, or parameterless with a
     * `Schema` return type. It runs here because `HasSchemas::getSchema()` throws
     * on unknown names and invokes whatever method discovery finds; with a
     * client-writable context key, the shape guard is what keeps every other host
     * method from being remotely invocable.
     *
     * @param array{name: string, arguments: array<mixed>, context: array<mixed>} $descriptor
     */
    protected function resolveSchemaComponentAction(array $descriptor): ?Action
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

        $methodName = match (true) {
            method_exists($this, $schemaName) => $schemaName,
            method_exists($this, "{$schemaName}Schema") => "{$schemaName}Schema",
            default => null,
        };

        if ($methodName === null) {
            return null;
        }

        $methodReflection = new ReflectionMethod($this, $methodName);
        $firstParameter = $methodReflection->getParameters()[0] ?? null;

        if ($firstParameter === null) {
            $returnType = $methodReflection->getReturnType();

            if (! $returnType instanceof ReflectionNamedType || ! is_a($returnType->getName(), Schema::class, allow_string: true)) {
                return null;
            }
        } else {
            $parameterType = $firstParameter->getType();

            if (! $parameterType instanceof ReflectionNamedType) {
                return null;
            }

            $parameterTypeName = $parameterType->getName();

            if (! class_exists($parameterTypeName) || ! is_a($parameterTypeName, Schema::class, allow_string: true)) {
                return null;
            }
        }

        $action = $this->getSchema($schemaName)->getAction($descriptor['name'], $nestedContainerKey);

        return $action?->liveComponent($this);
    }
}
