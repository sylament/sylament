<?php

declare(strict_types=1);

namespace Sylament\Tables\Query;

/**
 * One association step of a relation path: the property to join, the entity it
 * lands on, and whether it lands on a collection — a to-many step multiplies
 * rows, so a joiner and a search constraint handle it differently from a to-one
 * step.
 */
final readonly class Hop
{
    /**
     * @param class-string $targetClass
     */
    public function __construct(
        public string $property,
        public string $targetClass,
        public bool $isToMany,
    ) {
    }
}
