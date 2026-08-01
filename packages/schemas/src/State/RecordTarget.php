<?php

declare(strict_types=1);

namespace Sylament\Schemas\State;

/**
 * What a record's object graph declares at one mapped path, read from the
 * write side: the class to construct for an object segment ({@see $objectClass},
 * null when the property is not a constructible object), whether null may be
 * written, and whether the property holds a collection. The two collection
 * flags are not interchangeable — {@see $isCollection} covers any collection
 * including a plain array, while {@see $isObjectCollection} covers only a
 * `Traversable` object, the arm whose items have no identity to map onto.
 */
final readonly class RecordTarget
{
    /**
     * @param class-string $class
     * @param class-string|null $objectClass
     */
    public function __construct(
        public string $class,
        public string $property,
        public ?string $objectClass,
        public bool $isNullable,
        public bool $isCollection,
        public bool $isObjectCollection,
    ) {
    }
}
