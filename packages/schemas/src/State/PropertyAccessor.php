<?php

declare(strict_types=1);

namespace Sylament\Schemas\State;

use LogicException;
use Symfony\Component\PropertyAccess\Exception\InvalidArgumentException;
use Symfony\Component\PropertyAccess\Exception\UninitializedPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

final readonly class PropertyAccessor
{
    public function __construct(
        private PropertyAccessorInterface $accessor,
    ) {
    }

    /**
     * Read a host's state bag by absolute dotted path. The first segment is a
     * property on the host; the rest index into the array it holds, so the
     * traversal past it carries {@see getFromArray()}'s contract — null when any
     * segment is absent or a non-array is met mid-path.
     */
    public function get(object $host, string $path): mixed
    {
        $segments = explode('.', $path);
        $rootProperty = array_shift($segments);

        $value = $this->accessor->getValue($host, $rootProperty);

        if ($segments === []) {
            return $value;
        }

        if (! is_array($value)) {
            return null;
        }

        /** @var array<string, mixed> $value */
        return $this->getFromArray($value, implode('.', $segments));
    }

    /**
     * Write into a host's state bag by absolute dotted path. The first segment is
     * a property on the host; the rest index into the array it holds, so the
     * traversal past it carries {@see setInArray()}'s contract — auto-vivifying
     * (and clobbering any non-array) intermediate segments.
     */
    public function set(object $host, string $path, mixed $value): void
    {
        $segments = explode('.', $path);
        $rootProperty = array_shift($segments);

        if ($segments === []) {
            $this->accessor->setValue($host, $rootProperty, $value);

            return;
        }

        $bag = $this->accessor->getValue($host, $rootProperty);

        /** @var array<string, mixed> $bag */
        $bag = is_array($bag) ? $bag : [];

        $this->setInArray($bag, implode('.', $segments), $value);

        $this->accessor->setValue($host, $rootProperty, $bag);
    }

    /**
     * Read a record's object graph by dotted path — the `data_get` counterpart
     * for records: an object segment hops by property through the Symfony
     * accessor, an array segment by key, and a null or scalar met mid-path
     * yields null. Accessor failures on an object hop (no accessor,
     * uninitialized property) propagate as AccessException for the caller to
     * judge.
     */
    public function getFromRecord(object $record, string $path): mixed
    {
        $value = $record;

        foreach (explode('.', $path) as $segment) {
            if (is_object($value)) {
                $value = $this->accessor->getValue($value, $segment);

                continue;
            }

            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return null;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * Write into a record's object graph by dotted path. Object segments are
     * never vivified — they must already hold an object. From the first
     * non-object segment the remainder is written as a nested array with
     * {@see setInArray()}'s contract (vivifying, clobbering scalars) and
     * assigned back through the boundary property; a boundary property whose
     * type rejects that array throws LogicException naming the path, so a
     * schema declaring a nested object path over a null intermediate fails
     * with the fix spelled out rather than a type error.
     */
    public function setOnRecord(object $record, string $path, mixed $value): void
    {
        $segments = explode('.', $path);
        $holder = $record;

        while (count($segments) > 1) {
            $segment = array_shift($segments);

            try {
                $current = $this->accessor->getValue($holder, $segment);
            } catch (UninitializedPropertyException) {
                $current = null;
            }

            if (is_object($current)) {
                $holder = $current;

                continue;
            }

            /** @var array<string, mixed> $bag */
            $bag = is_array($current) ? $current : [];
            $this->setInArray($bag, implode('.', $segments), $value);

            try {
                $this->accessor->setValue($holder, $segment, $bag);
            } catch (InvalidArgumentException $exception) {
                throw new LogicException(sprintf(
                    'Cannot map state path "%s" onto %s: "%s" is not an object and its property does not accept an array. Initialize the nested object (constructor or lazy getter) before mapping.',
                    $path,
                    $record::class,
                    $segment,
                ), $exception->getCode(), previous: $exception);
            }

            return;
        }

        $this->accessor->setValue($holder, $segments[0], $value);
    }

    /**
     * Whether a dotted path exists in a detached nested array — unlike
     * {@see getFromArray()}, distinguishes an absent key from a present null
     * (matching `Arr::has`).
     *
     * @param array<string, mixed> $state
     */
    public function hasInArray(array $state, string $path): bool
    {
        $value = $state;

        foreach (explode('.', $path) as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return false;
            }

            $value = $value[$segment];
        }

        return true;
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
}
