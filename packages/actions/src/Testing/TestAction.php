<?php

declare(strict_types=1);

namespace Sylament\Actions\Testing;

use Closure;
use Sylament\Support\ArrayRecord;

use function Sylament\Support\blank;
use function Sylament\Support\filled;

class TestAction
{
    /**
     * @var array<string, mixed>|Closure|null
     */
    protected array|Closure|null $arguments = null;

    /**
     * @var array<string, mixed>
     */
    protected array $context = [];

    protected string|bool|null $schemaComponent = null;

    protected ?string $schema = null;

    protected mixed $table = null;

    protected bool $isBulk = false;

    final public function __construct(
        protected string $name,
    ) {
    }

    public static function make(string $name): static
    {
        return new static($name);
    }

    /**
     * @param array<string, mixed>|Closure|null $arguments
     */
    public function arguments(array|Closure|null $arguments): static
    {
        $this->arguments = $arguments;

        return $this;
    }

    public function schemaComponent(string|bool|null $component = true, ?string $schema = null): static
    {
        $this->schemaComponent = $component;
        $this->schema = $schema;

        return $this;
    }

    public function table(mixed $record = true): static
    {
        $this->table = $record;

        return $this;
    }

    public function bulk(bool $condition = true): static
    {
        $this->isBulk = $condition;

        return $this;
    }

    /**
     * @param array<string, mixed> $actualArguments
     */
    public function checkArguments(array $actualArguments): bool
    {
        if (!($this->arguments instanceof Closure)) {
            return true;
        }

        return (bool) ($this->arguments)($actualArguments);
    }

    /**
     * An object record stays in `recordKey`: its key is minted by the table host,
     * which the tester asks when it sends the action.
     *
     * @return array{name: string, arguments?: array<string, mixed>, context: array<string, mixed>}
     */
    public function toArray(?string $defaultSchema = null): array
    {
        $schema = $this->schema ?? $defaultSchema;

        $schemaComponentContext = match (true) {
            is_string($this->schemaComponent) => (filled($schema) ? "{$schema}." : '') . $this->schemaComponent,
            $this->schemaComponent === true => $schema,
            default => null,
        };

        $array = [
            'name' => $this->name,
            ...(is_array($this->arguments) ? ['arguments' => $this->arguments] : []),
            'context' => [
                ...($this->isBulk ? ['bulk' => true] : []),
                ...(filled($schemaComponentContext) ? ['schemaComponent' => $schemaComponentContext] : []),
                ...$this->context,
            ],
        ];

        if (blank($this->table) || ($this->table === false)) {
            return $array;
        }

        $array['context']['table'] = true;

        if ($this->table === true) {
            return $array;
        }

        $array['context']['recordKey'] = is_array($this->table)
            ? ($this->table[ArrayRecord::getKeyName()] ?? null)
            : $this->table;

        return $array;
    }
}
