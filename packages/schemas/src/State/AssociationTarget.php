<?php

declare(strict_types=1);

namespace Sylament\Schemas\State;

/**
 * What a Doctrine entity mapping declares at one association property: the
 * entity to load or reference ({@see $targetClass}), whether the property holds
 * a collection, whether this side owns the foreign key — an inverse side writes
 * nothing on its own — and whether removing an item from it deletes the entity
 * rather than only detaching it.
 */
final readonly class AssociationTarget
{
    /**
     * @param class-string $targetClass
     */
    public function __construct(
        public string $targetClass,
        public bool $isToMany,
        public bool $isOwningSide,
        public bool $isOrphanRemoval,
    ) {
    }
}
