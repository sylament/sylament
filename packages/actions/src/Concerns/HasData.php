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
        }

        if (!is_array($data)) {
            throw new TypeError(sprintf('%s::mutateDataUsing() must return an array.', static::class));
        }

        $this->data = $data;

        return $this;
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
