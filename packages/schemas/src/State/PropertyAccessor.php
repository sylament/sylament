<?php

declare(strict_types=1);

namespace Sylament\Schemas\State;

use LogicException;
use ReflectionClass;
use Symfony\Component\PropertyAccess\Exception\InvalidArgumentException;
use Symfony\Component\PropertyAccess\Exception\UninitializedPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Traversable;

final readonly class PropertyAccessor
{
    public function __construct(
        private PropertyAccessorInterface $accessor,
        private RecordTypeReader $typeReader,
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
     * Write into a record's object graph by dotted path. A null intermediate
     * whose declared type names a constructible class is built and assigned
     * before the walk continues; an existing object is mutated in place. From
     * the first segment that is neither an object nor constructible, the
     * remainder is written as a nested array with {@see setInArray()}'s
     * contract (vivifying, clobbering scalars) and assigned back through the
     * boundary property; a boundary property whose type rejects that array
     * throws LogicException naming the path, so the failure spells out the fix
     * rather than surfacing a type error.
     *
     * @throws LogicException when a mapped segment declares or holds an object
     *                        collection, names a class the mapper cannot
     *                        build, or rejects the array remainder
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

            if ($current instanceof Traversable) {
                $this->refuseCollection($holder, $segment, $path);
            }

            if (is_object($current)) {
                $holder = $current;

                continue;
            }

            if ($current === null) {
                $constructed = $this->constructIntermediate($holder, $segment, $path);

                if ($constructed !== null) {
                    $this->accessor->setValue($holder, $segment, $constructed);
                    $holder = $constructed;

                    continue;
                }
            }

            /** @var array<string, mixed> $bag */
            $bag = is_array($current) ? $current : [];
            $this->setInArray($bag, implode('.', $segments), $value);

            try {
                $this->accessor->setValue($holder, $segment, $bag);
            } catch (InvalidArgumentException $exception) {
                throw new LogicException(sprintf(
                    'Cannot map state path "%s" onto %s: "%s" declares no object the mapper can construct, and its property rejects the array written in its place. Assign the nested object yourself if the property can hold one, or map this path onto a property that accepts the branch.',
                    $path,
                    $holder::class,
                    $segment,
                ), $exception->getCode(), previous: $exception);
            }

            return;
        }

        $leaf = $this->typeReader->describeProperty($holder::class, $segments[0]);

        if ($leaf instanceof RecordTarget) {
            $this->assertNotCollection($leaf, $holder, $segments[0], $path);
        }

        $this->accessor->setValue($holder, $segments[0], $value);
    }

    /**
     * Write an association value straight onto a record property, bypassing the
     * object-collection refusal {@see setOnRecord()} raises. That refusal exists
     * because a plain state array carries no identity for its items; an
     * association write carries entities, and the identity is the mapping's own.
     * A collection-valued association takes the whole desired list — Symfony's
     * accessor diffs it against what the property holds and calls the record's
     * adder and remover per element, so the collection instance survives and
     * Doctrine sees only the difference.
     */
    public function setAssociationOnRecord(object $record, string $property, mixed $value): void
    {
        $this->accessor->setValue($record, $property, $value);
    }

    /**
     * Whether the record's declared write-side type at a dotted path accepts
     * null, so a caller about to write one there can refuse first with a message
     * of its own rather than letting Symfony raise a bare type error. True when
     * the type cannot be read, keeping the behaviour a caller had before types
     * were consulted.
     *
     * @param class-string $recordClass
     */
    public function acceptsNullOnRecord(string $recordClass, string $path): bool
    {
        $target = $this->typeReader->describe($recordClass, $path);

        return ! $target instanceof RecordTarget || $target->isNullable;
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

    /**
     * The object a null intermediate should become, or null when the record
     * declares no constructible class there — in which case the caller falls
     * through to the array-remainder write, preserving the JSON-column shape.
     *
     * @throws LogicException when the declared type is an object collection, or
     *                        names a class whose constructor requires arguments
     */
    private function constructIntermediate(object $holder, string $segment, string $path): ?object
    {
        $target = $this->typeReader->describeProperty($holder::class, $segment);

        if (!$target instanceof RecordTarget) {
            return null;
        }

        $this->assertNotCollection($target, $holder, $segment, $path);

        if ($target->objectClass === null) {
            return null;
        }

        $reflection = new ReflectionClass($target->objectClass);
        $constructor = $reflection->getConstructor();

        if ($constructor !== null && $constructor->getNumberOfRequiredParameters() > 0) {
            throw new LogicException(sprintf(
                'Cannot map state path "%s" onto %s: %s requires constructor arguments, so the mapper cannot build it. Give the constructor defaults, or initialize "%s" before mapping.',
                $path,
                $holder::class,
                $target->objectClass,
                $segment,
            ));
        }

        return $reflection->newInstance();
    }

    /**
     * @throws LogicException when the record declares a collection at a mapped
     *                        path, which has no supported item identity yet
     */
    private function assertNotCollection(RecordTarget $target, object $holder, string $segment, string $path): void
    {
        if (! $target->isObjectCollection) {
            return;
        }

        $this->refuseCollection($holder, $segment, $path);
    }

    private function refuseCollection(object $holder, string $segment, string $path): never
    {
        throw new LogicException(sprintf(
            'Cannot map state path "%s" onto %s: "%s" is an object collection. Collection items need identity mapping (which state item is which entity), which arrives with the Doctrine relationship layer — the Repeater maps plain-array items only. Until then, map this property by hand after getData().',
            $path,
            $holder::class,
            $segment,
        ));
    }
}
