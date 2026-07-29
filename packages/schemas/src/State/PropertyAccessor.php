<?php

declare(strict_types=1);

namespace Sylament\Schemas\State;

use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

final readonly class PropertyAccessor
{
    public function __construct(
        private PropertyAccessorInterface $accessor,
    ) {
    }

    public function get(object $host, string $path): mixed
    {
        return $this->accessor->getValue($host, $this->toPropertyPath($path));
    }

    public function set(object $host, string $path, mixed $value): void
    {
        $this->accessor->setValue($host, $this->toPropertyPath($path), $value);
    }

    /**
     * Read a value from a detached nested array by dotted path, returning null
     * when any segment is absent or a non-array is met mid-path.
     *
     * @param array<string, mixed> $state
     */
    public function getFromArray(array $state, string $path): mixed
    {
        $value = $state;

        foreach (explode('.', $path) as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return null;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * Write a value into a detached nested array by dotted path, auto-vivifying
     * (and clobbering any non-array) intermediate segments.
     *
     * @param array<string, mixed> $state
     */
    public function setInArray(array &$state, string $path, mixed $value): void
    {
        $state = $this->writeSegments($state, explode('.', $path), $value);
    }

    /**
     * Remove one or more dotted paths from a detached nested array. Only the leaf
     * key is unset; emptied parents are left in place (matching `Arr::forget`, on
     * which the dehydration walk's own explicit parent cleanup relies).
     *
     * @param array<string, mixed> $state
     * @param string|array<string> $paths
     */
    public function forgetFromArray(array &$state, string|array $paths): void
    {
        foreach (is_array($paths) ? $paths : [$paths] as $path) {
            $state = $this->forgetSegments($state, explode('.', $path));
        }
    }

    /**
     * @param array<string, mixed> $array
     * @param list<string> $segments
     * @return array<string, mixed>
     */
    private function writeSegments(array $array, array $segments, mixed $value): array
    {
        $key = array_shift($segments);

        if ($key === null) {
            return $array;
        }

        if ($segments === []) {
            $array[$key] = $value;

            return $array;
        }

        $child = $array[$key] ?? null;

        /** @var array<string, mixed> $child */
        $child = is_array($child) ? $child : [];

        $array[$key] = $this->writeSegments($child, $segments, $value);

        return $array;
    }

    /**
     * @param array<string, mixed> $array
     * @param list<string> $segments
     * @return array<string, mixed>
     */
    private function forgetSegments(array $array, array $segments): array
    {
        $key = array_shift($segments);

        if ($key === null) {
            return $array;
        }

        if ($segments === []) {
            unset($array[$key]);

            return $array;
        }

        $child = $array[$key] ?? null;

        if (is_array($child)) {
            /** @var array<string, mixed> $child */
            $array[$key] = $this->forgetSegments($child, $segments);
        }

        return $array;
    }

    private function toPropertyPath(string $dottedPath): string
    {
        $segments = explode('.', $dottedPath);
        $first = array_shift($segments);

        if ($segments === []) {
            return $first;
        }

        return $first . '[' . implode('][', $segments) . ']';
    }
}
