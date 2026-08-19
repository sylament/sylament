<?php

declare(strict_types=1);

namespace Sylament\Schemas\Concerns;

use LogicException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use RuntimeException;
use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Exceptions\ValidationException;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Validation\PresenceGate;
use Sylament\Support\Components\Attributes\ExposedLiveComponentMethod;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Attribute\PostHydrate;
use Symfony\UX\LiveComponent\Attribute\PreDehydrate;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use TypeError;

trait HasSchemas
{
    use ComponentToolsTrait;

    /**
     * @var array<string, mixed>
     */
    #[LiveProp]
    public array $oldState = [];

    /**
     * @var list<string>
     */
    #[LiveProp]
    public array $validatedSchemas = [];

    /**
     * @var array<string, Schema>
     */
    protected array $cachedSchemas = [];

    /**
     * @var array<string, array<string, list<string>>>
     */
    protected array $schemaValidationErrors = [];

    protected ?ValidatorInterface $schemaStateValidator = null;

    #[Required]
    public function setSchemaStateValidator(ValidatorInterface $validator): void
    {
        $this->schemaStateValidator = $validator;
    }

    public function schemas(): array
    {
        return [
            'schema',
        ];
    }

    public function getSchema(string $name = 'schema'): Schema
    {
        $cachedSchema = $this->cachedSchemas[$name] ?? null;
        if ($cachedSchema instanceof Schema) {
            return $cachedSchema;
        }

        $method = match (true) {
            method_exists($this, $name) => $name,
            method_exists($this, "{$name}Schema") => "{$name}Schema",
            default => throw new RuntimeException("Schema {$name} does not exist on component " . $this::class . " — expected a {$name}() or {$name}Schema() method."),
        };

        $schema = $this->{$method}($this->makeSchema());
        if (!$schema instanceof Schema) {
            throw new RuntimeException('Schema must be an instance of ' . Schema::class);
        }

        return $this->cachedSchemas[$name] = $schema->key($name);
    }

    public function cacheSchema(string $name, ?Schema $schema): ?Schema
    {
        if ($schema instanceof Schema) {
            return $this->cachedSchemas[$name] = $schema->key($name);
        }

        unset($this->cachedSchemas[$name]);

        return null;
    }

    public function hasCachedSchema(string $name): bool
    {
        return array_key_exists($name, $this->cachedSchemas);
    }

    public function resolveSchemaByName(string $name): ?Schema
    {
        if ($this->hasCachedSchema($name)) {
            return $this->getSchema($name);
        }

        $methodName = match (true) {
            method_exists($this, $name) => $name,
            method_exists($this, "{$name}Schema") => "{$name}Schema",
            default => null,
        };

        if ($methodName === null || method_exists(InteractsWithSchemas::class, $methodName)) {
            return null;
        }

        $methodReflection = new ReflectionMethod($this, $methodName);
        $firstParameter = $methodReflection->getParameters()[0] ?? null;

        if ($firstParameter === null) {
            $returnType = $methodReflection->getReturnType();

            if (!$returnType instanceof ReflectionNamedType || !is_a($returnType->getName(), Schema::class, allow_string: true)) {
                return null;
            }
        } else {
            if ($methodReflection->getNumberOfRequiredParameters() > 1) {
                return null;
            }

            $parameterType = $firstParameter->getType();

            if (!$parameterType instanceof ReflectionNamedType) {
                return null;
            }

            $parameterTypeName = $parameterType->getName();

            if (!class_exists($parameterTypeName) || !is_a($parameterTypeName, Schema::class, allow_string: true)) {
                return null;
            }
        }

        return $this->getSchema($name);
    }

    public function getSchemaComponent(string $key, bool $withHidden = false): Component|Action|ActionGroup|null
    {
        $dotPosition = mb_strpos($key, '.');

        if ($dotPosition === false) {
            return null;
        }

        $schemaName = mb_substr($key, 0, $dotPosition);

        return $this->resolveSchemaByName($schemaName)?->getComponent($key, withHidden: $withHidden, isAbsoluteKey: true);
    }

    /**
     * @param array<mixed> $arguments
     */
    #[LiveAction]
    public function callSchemaComponentMethod(
        #[LiveArg] string $componentKey,
        #[LiveArg] string $method,
        #[LiveArg] array $arguments = [],
    ): void {
        // Security: callable from the browser. The schema name is resolved through
        // resolveSchemaByName()'s reflection gate (never getSchema()), and only public
        // #[ExposedLiveComponentMethod] methods on the resolved component are called.
        $result = null;
        $component = $this->getSchemaComponent($componentKey);
        if ($component !== null && method_exists($component, $method)) {
            $reflection = new ReflectionMethod($component, $method);
            if (
                $reflection->isPublic()
                && $reflection->getAttributes(ExposedLiveComponentMethod::class) !== []
                && $this->argumentsMatchSignature($reflection, $arguments)
            ) {
                try {
                    $result = $component->{$method}(...$arguments);
                } catch (TypeError) {
                    $result = null;
                }
            }
        }

        $this->dispatchBrowserEvent('sylament:schema-component-method-result', [
            'componentKey' => $componentKey, 'method' => $method, 'result' => $result,
        ]);
    }

    /**
     * @param array<mixed> $arguments
     */
    private function argumentsMatchSignature(ReflectionMethod $reflection, array $arguments): bool
    {
        $parameterNames = array_map(
            static fn (ReflectionParameter $parameter): string => $parameter->getName(),
            $reflection->getParameters(),
        );

        foreach (array_keys($arguments) as $key) {
            if (!is_string($key) || !in_array($key, $parameterNames, true)) {
                return false;
            }
        }

        foreach ($reflection->getParameters() as $parameter) {
            if ($parameter->isDefaultValueAvailable() || $parameter->isVariadic()) {
                continue;
            }

            if (!array_key_exists($parameter->getName(), $arguments)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Fill the default schema. Multi-schema hosts fill each explicitly via
     * `getSchema($name)->fill(...)`.
     *
     * @param object|array<string, mixed>|null $state
     */
    public function fill(object|array|null $state = null): static
    {
        $this->getSchema()->fill($state);

        return $this;
    }

    /**
     * @return array<string, Schema>
     */
    public function getCachedSchemas(): array
    {
        foreach ($this->schemas() as $name) {
            if (!array_key_exists((string) $name, $this->cachedSchemas)) {
                $this->getSchema($name);
            }
        }

        return $this->cachedSchemas;
    }

    public function getOldSchemaState(?string $path): mixed
    {
        if ($path === null) {
            return null;
        }

        return $this->oldState[$path] ?? null;
    }

    #[PreDehydrate]
    public function persistOldState(): void
    {
        $oldState = [];

        foreach ($this->getCachedSchemas() as $schema) {
            foreach ($schema->getFlatComponents(withActions: false) as $component) {
                if (!$component->hasStatePath()) {
                    continue;
                }

                $oldState[(string) $component->getStatePath()] = $component->getRawState();
            }
        }

        $this->oldState = $oldState;
    }

    #[PostHydrate(priority: -10)]
    public function callAfterSchemaStateUpdated(): void
    {
        foreach ($this->getCachedSchemas() as $schema) {
            foreach ($schema->getFlatComponents(withActions: false) as $component) {
                if (!$component->hasStatePath()) {
                    continue;
                }

                if ($this->getOldSchemaState($component->getStatePath()) === $component->getRawState()) {
                    continue;
                }

                $component->callAfterStateUpdatedHooks();
            }
        }
    }

    #[PostHydrate(priority: -20)]
    public function revalidateValidatedSchemas(): void
    {
        foreach ($this->validatedSchemas as $schemaKey) {
            if ($this->hasCachedSchema($schemaKey) || in_array($schemaKey, $this->schemas(), true)) {
                $this->validateSchema($this->getSchema($schemaKey), shouldThrow: false);

                continue;
            }

            $component = $this->getSchemaComponent($schemaKey);
            $childSchema = $component instanceof Component ? $component->getChildSchema() : null;

            if ($childSchema instanceof Schema) {
                $this->validateSchema($childSchema, shouldThrow: false);

                continue;
            }

            $this->clearSchemaValidation($schemaKey);
        }
    }

    public function validateSchema(Schema $schema, bool $shouldThrow = true): void
    {
        $schemaKey = $this->getSchemaValidationKey($schema);

        $validator = $this->schemaStateValidator
            ?? throw new LogicException('No validator injected into ' . static::class . ' — the container calls setSchemaStateValidator(); a host built with `new` must inject one itself.');

        $errors = [];

        foreach ($schema->getValidationConstraints() as $path => $constraints) {
            $value = $schema->getPropertyAccessor()->get($this, $path);
            $violations = $validator->validate($value, PresenceGate::apply($value, $constraints));

            foreach ($violations as $violation) {
                $errors[$path][] = (string) $violation->getMessage();
            }
        }

        if ($schema->isRoot()) {
            $this->clearNestedSchemaValidation($schemaKey);
        }

        if ($errors === []) {
            unset($this->schemaValidationErrors[$schemaKey]);

            if ($shouldThrow) {
                $this->validatedSchemas = array_values(array_diff($this->validatedSchemas, [$schemaKey]));
            }

            return;
        }

        $this->schemaValidationErrors[$schemaKey] = $errors;

        if (!$shouldThrow) {
            return;
        }

        if (!in_array($schemaKey, $this->validatedSchemas, true)) {
            $this->validatedSchemas[] = $schemaKey;
        }

        if (isset($this->liveResponder)) {
            $this->dispatchBrowserEvent('form-validation-error', ['schemaKey' => $schemaKey]);
        }

        throw new ValidationException($errors);
    }

    /**
     * The identity a schema's errors and stickiness are filed under: a root schema keeps
     * the name the host registered it as, while a component's child schema takes that
     * component's absolute key, which the sticky pass resolves back through
     * {@see getSchemaComponent()} and which can never collide with a root name.
     */
    private function getSchemaValidationKey(Schema $schema): string
    {
        $key = $schema->getParentComponent()?->getKey() ?? $schema->getKey();

        return $key
            ?? throw new LogicException('Cannot validate a schema without a key — register it through getSchema()/cacheSchema() so it is keyed, or validate a keyed component\'s child schema.');
    }

    /**
     * A root schema's pass supersedes every pass over a component nested inside it, so its
     * identities are dropped rather than left to re-validate and repeat the same message.
     */
    private function clearNestedSchemaValidation(string $rootKey): void
    {
        $prefix = $rootKey . '.';

        $this->validatedSchemas = array_values(array_filter(
            $this->validatedSchemas,
            static fn (string $key): bool => !str_starts_with($key, $prefix),
        ));

        foreach (array_keys($this->schemaValidationErrors) as $key) {
            if (str_starts_with($key, $prefix)) {
                unset($this->schemaValidationErrors[$key]);
            }
        }
    }

    public function validateSchemaRecord(Schema $schema, object $record): void
    {
        if (!$this->schemaStateValidator instanceof ValidatorInterface) {
            return;
        }

        $schemaKey = $this->getSchemaValidationKey($schema);

        $violations = $this->schemaStateValidator->validate($record);

        if (count($violations) === 0) {
            return;
        }

        $translator = $schema->getRecordViolationPathTranslator();
        $rootPath = (string) $schema->getStatePath();

        $errors = [];

        foreach ($violations as $violation) {
            $path = $translator->translate($violation->getPropertyPath()) ?? $rootPath;

            $errors[$path][] = (string) $violation->getMessage();
        }

        $this->schemaValidationErrors[$schemaKey] = [
            ...$this->schemaValidationErrors[$schemaKey] ?? [],
            ...$errors,
        ];

        if (!in_array($schemaKey, $this->validatedSchemas, true)) {
            $this->validatedSchemas[] = $schemaKey;
        }

        throw new ValidationException($errors);
    }

    /**
     * @return list<string>
     */
    public function getErrorsForSchemaStatePath(?string $path): array
    {
        if ($path === null) {
            return [];
        }

        $messages = [];

        foreach ($this->schemaValidationErrors as $schemaErrors) {
            $messages = [...$messages, ...($schemaErrors[$path] ?? [])];
        }

        return array_values(array_unique($messages));
    }

    public function clearSchemaValidation(?string $schemaKey = null): void
    {
        if ($schemaKey === null) {
            $this->validatedSchemas = [];
            $this->schemaValidationErrors = [];

            return;
        }

        $this->validatedSchemas = array_values(array_diff($this->validatedSchemas, [$schemaKey]));
        unset($this->schemaValidationErrors[$schemaKey]);
    }

    public function makeSchema(): Schema
    {
        return Schema::make($this);
    }
}
