<?php

declare(strict_types=1);

namespace Sylament\Schemas\Concerns;

use LogicException;
use RuntimeException;
use Sylament\Schemas\Exceptions\ValidationException;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Validation\PresenceGate;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Attribute\PostHydrate;
use Symfony\UX\LiveComponent\Attribute\PreDehydrate;

trait HasSchemas
{
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

            $this->clearSchemaValidation($schemaKey);
        }
    }

    public function validateSchema(Schema $schema, bool $shouldThrow = true): void
    {
        $schemaKey = $schema->getKey()
            ?? throw new LogicException('Cannot validate a schema without a key — register it through getSchema()/cacheSchema() so it is keyed.');

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

        throw new ValidationException($errors);
    }

    public function validateSchemaRecord(Schema $schema, object $record): void
    {
        if (!$this->schemaStateValidator instanceof ValidatorInterface) {
            return;
        }

        $schemaKey = $schema->getKey()
            ?? throw new LogicException('Cannot validate the record of a schema without a key — register it through getSchema()/cacheSchema() so it is keyed.');

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

        return $messages;
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
