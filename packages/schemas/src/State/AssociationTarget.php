<?php

declare(strict_types=1);

namespace Sylament\Schemas\State;

/**
 * What a Doctrine entity mapping declares at one association property: the
 * entity to load or reference ({@see $targetClass}), whether the property holds
 * a collection, whether this side owns the foreign key — an inverse side writes
 * nothing on its own — whether removing an item from it deletes the entity
 * rather than only detaching it, whether the association is a many-to-many, the
 * field completing it on the other side ({@see $mappedBy} names the owning field
 * on the target and is read on inverse sides only; {@see $inversedBy} names the
 * inverse field on the target and is read on owning sides only, null where the
 * mapping is unidirectional), whether the column behind it accepts null
 * ({@see $isNullable} is a to-one owning side's join column, and true for every
 * other shape, which has no column of its own to be null), and the order the
 * mapping gives the collection ({@see $orderBy}, empty where it declares none).
 */
final readonly class AssociationTarget
{
    /**
     * @param class-string $targetClass
     * @param array<string, string> $orderBy the mapping's collection order exactly as declared — Doctrine's own
     *                                       docblock says 'asc'|'desc' but hands 'ASC' through untouched; [] where none
     */
    public function __construct(
        public string $targetClass,
        public bool $isToMany,
        public bool $isOwningSide,
        public bool $isOrphanRemoval,
        public bool $isManyToMany,
        public ?string $mappedBy,
        public ?string $inversedBy,
        public bool $isNullable,
        public array $orderBy,
    ) {
    }
}
