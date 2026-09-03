<?php

declare(strict_types=1);

namespace Sylament\Schemas\State;

/**
 * What a record's object graph declares at one mapped path, read from the
 * write side: the class to construct for an object segment ({@see $objectClass},
 * null when the property is not a constructible object), whether null may be
 * written, whether the property holds a collection, and the native type it names
 * when that type is a plain builtin one ({@see $builtinType}, null for a
 * collection as much as for an object). The two collection flags are not
 * interchangeable — {@see $isCollection} covers any collection
 * including a plain array, while {@see $isObjectCollection} covers only the
 * object-item arms (a `Traversable` object, or a collection whose value type is
 * an object — how an adder resolves), whose items have no identity to map onto.
 */
final readonly class RecordTarget
{
    /**
     * @param class-string|null $objectClass
     * @param string|null       $builtinType the declared native type — `int`,
     *                                       `string`, `bool`, … — or null where
     *                                       the property names an object, a
     *                                       collection, a union, or a type that
     *                                       cannot be read
     */
    public function __construct(
        public ?string $objectClass,
        public bool $isNullable,
        public bool $isCollection,
        public bool $isObjectCollection,
        public ?string $builtinType = null,
    ) {
    }
}
