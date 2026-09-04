<?php

declare(strict_types=1);

namespace Sylament\Actions\Concerns;

use Closure;
use TypeError;

trait HasData
{
    /**
     * @var array<string, mixed>
     */
    protected array $data = [];

    protected ?Closure $mutateDataUsing = null;

    public function mutateDataUsing(?Closure $callback): static
    {
        $this->mutateDataUsing = $callback;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function data(array $data, bool $shouldMutate = true): static
    {
        if ($shouldMutate && $this->mutateDataUsing) {
            $data = $this->evaluate($this->mutateDataUsing, [
                'data' => $data,
            ]);

            self::assertFieldKeyedData($data, 'mutateDataUsing');
        }

        $this->data = $data;

        return $this;
    }

    /**
     * @phpstan-assert array<string, mixed> $data
     */
    protected static function assertFieldKeyedData(mixed $data, string $hook): void
    {
        if (!is_array($data) || !array_all(array_keys($data), static fn (int|string $field): bool => is_string($field))) {
            throw new TypeError(sprintf('%s::%s() must return an array keyed by field name.', static::class, $hook));
        }
    }

    public function resetData(): static
    {
        $this->data([], shouldMutate: false);

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return array<string, mixed>
     */
    public function getRawData(): array
    {
        return $this->getLiveComponentOrNull()?->getMountedActionData($this->getNestingIndex()) ?? [];
    }
}
