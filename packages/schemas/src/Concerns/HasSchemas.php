<?php

declare(strict_types=1);

namespace Sylament\Schemas\Concerns;

use JsonException;
use LogicException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use RuntimeException;
use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Contracts\AcceptsUploadedFiles;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Exceptions\ValidationException;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Validation\PresenceGate;
use Sylament\Schemas\Validation\RecordKeyGuard;
use Sylament\Support\Components\Attributes\ExposedLiveComponentMethod;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
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
     * The prefix every schema component of this host stamps its live-component
     * key with — a `LiveProp` so the same ids come back on a re-render.
     */
    #[LiveProp]
    public ?string $schemasHostId = null;

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

    public function getSchemasHostId(): string
    {
        return $this->schemasHostId ??= bin2hex(random_bytes(4));
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

        $this->dispatchSchemaComponentMethodResult($componentKey, $method, $result);
    }

    #[LiveAction]
    public function uploadSchemaComponentFile(
        #[LiveArg] string $componentKey,
        #[LiveArg] string $fileKey,
        Request $request,
    ): void {
        // Security: callable from the browser. The component is resolved through the same gate
        // callSchemaComponentMethod() uses, only an AcceptsUploadedFiles implementor is handed a
        // file, the file key is shape-checked before it names a state key or a multipart part, and
        // the file is the one part the browser attached under that key — nothing is taken from
        // browser-supplied arguments.
        $result = null;
        $component = $this->getSchemaComponent($componentKey);

        if ($component instanceof AcceptsUploadedFiles && preg_match('/^[A-Za-z0-9-]{1,64}$/D', $fileKey) === 1) {
            $file = $request->files->get('sylament_upload_' . $fileKey);

            if ($file instanceof UploadedFile) {
                $result = $component->acceptUploadedFile($fileKey, $file);
            }
        }

        $this->dispatchSchemaComponentMethodResult($componentKey, 'uploadSchemaComponentFile', $result, $fileKey);
    }

    /**
     * The one exit both browser-callable seams take: the result rides back as the
     * `sylament:schema-component-method-result` event, carrying the file key that tells
     * concurrent uploads to one component apart (`null` for every non-upload call).
     */
    private function dispatchSchemaComponentMethodResult(
        string $componentKey,
        string $method,
        mixed $result,
        ?string $fileKey = null,
    ): void {
        try {
            json_encode($result, JSON_THROW_ON_ERROR);
        } catch (JsonException $jsonException) {
            throw new LogicException(sprintf('The method "%s" on schema component "%s" returned a value that is not JSON-serialisable; a browser-callable method must.', $method, $componentKey), $jsonException->getCode(), previous: $jsonException);
        }

        $this->dispatchBrowserEvent('sylament:schema-component-method-result', [
            'componentKey' => $componentKey, 'method' => $method, 'fileKey' => $fileKey, 'result' => $result,
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
        $constraintsByPath = $schema->getValidationConstraints();

        $state = [];

        foreach (array_keys($constraintsByPath) as $path) {
            $state[$path] = $schema->getPropertyAccessor()->get($this, $path);
        }

        $state = $schema->mutateStateForValidation($state);

        foreach ($constraintsByPath as $path => $constraints) {
            $value = $state[$path];
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

        $reader = $schema->getAssociationReader();

        $violations = $this->schemaStateValidator->validate($record, RecordKeyGuard::constraint($reader->revertIdentifierChanges($record)));
        $violations->addAll($this->schemaStateValidator->validate($record));

        if (count($violations) === 0 && $schema->isRecordKeyValidated()) {
            $violations = $this->schemaStateValidator->validate($record, RecordKeyGuard::constraint($this->getMappedBlankIdentifier($schema, $reader->findBlankAssignedIdentifiers($record))));

            if (count($violations) === 0) {
                $violations = $this->schemaStateValidator->validate($record, RecordKeyGuard::constraint($this->getMappedTakenIdentifiers($schema, $reader->findTakenIdentifiers($record))));
            }
        }

        if (count($violations) === 0) {
            return;
        }

        $translator = $schema->getRecordViolationPathTranslator();
        $rootPath = (string) $schema->getStatePath();

        $errors = [];

        foreach ($violations as $violation) {
            $path = $translator->translate($violation->getPropertyPath()) ?? $rootPath;
            $message = (string) $violation->getMessage();

            if (!in_array($message, $errors[$path] ?? [], true)) {
                $errors[$path][] = $message;
            }
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
     * The first blank-key candidate a component of the schema maps, or none.
     *
     * @param array<string, string> $candidates the refusal, keyed by each candidate field in the order the rule picks from
     *
     * @return array<string, string>
     */
    private function getMappedBlankIdentifier(Schema $schema, array $candidates): array
    {
        if ($candidates === []) {
            return [];
        }

        $translator = $schema->getRecordViolationPathTranslator();
        $field = array_find_key($candidates, static fn (string $message, string $field): bool => $translator->translate($field) !== null);

        return $field === null ? [] : [$field => $candidates[$field]];
    }

    /**
     * Each taken record's candidates a component of the schema maps or, where it
     * maps none of a record's, that record's first: a record's candidates share
     * the property path up to their last segment, and a candidate that
     * translates only to the record's own path, through the component mapping
     * the record, is not mapped.
     *
     * @param array<string, string> $candidates the refusal, keyed by each candidate field, a taken record's together in identifier order
     *
     * @return array<string, string>
     */
    private function getMappedTakenIdentifiers(Schema $schema, array $candidates): array
    {
        if ($candidates === []) {
            return [];
        }

        $translator = $schema->getRecordViolationPathTranslator();
        $byRecord = [];

        foreach ($candidates as $field => $message) {
            $separator = mb_strrpos($field, '.');
            $byRecord[$separator === false ? '' : mb_substr($field, 0, $separator)][$field] = $message;
        }

        $refusals = [];

        foreach ($byRecord as $prefix => $recordCandidates) {
            $own = $prefix === '' ? null : $translator->translate($prefix);
            $mapped = array_filter($recordCandidates, static fn (string $field): bool => ($translation = $translator->translate($field)) !== null && $translation !== $own, ARRAY_FILTER_USE_KEY);
            $refusals = [...$refusals, ...($mapped === [] ? array_slice($recordCandidates, 0, 1, true) : $mapped)];
        }

        return $refusals;
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

        $this->clearNestedSchemaValidation($schemaKey);
    }

    public function makeSchema(): Schema
    {
        return Schema::make($this);
    }
}
