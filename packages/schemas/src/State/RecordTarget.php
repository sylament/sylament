<?php

declare(strict_types=1);

namespace Sylament\Schemas\State;

/**
 * What a record's object graph declares at one mapped path, read from the
 * write side: the class to construct for an object segment ({@see $objectClass},
 * null when the property is not a constructible object), whether null may be
 * written, and whether the property holds a collection. The two collection
 * flags are not interchangeable — {@see $isCollection} covers any collection
 * including a plain array, while {@see $isObjectCollection} covers only the
 * object-item arms (a `Traversable` object, or a collection whose value type is
 * an object — how an adder resolves), whose items have no identity to map onto.
 */
final readonly class RecordTarget
{
    /**
     * @param class-string|null $objectClass
     */
    public function __construct(
        public ?string $objectClass,
        public bool $isNullable,
        public bool $isCollection,
        public bool $isObjectCollection,
    ) {
    }
}
