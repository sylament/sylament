<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Contracts;

/**
 * A component whose subtree is entangled with a to-one Doctrine association: it
 * maps the association's property wholesale, filling its children off the
 * related record and writing them back onto it.
 */
interface CanEntangleWithSingularRelationships
{
    public function getRelationshipName(): ?string;

    public function hasRelationship(): bool;

    /**
     * The subtree state the related record holds, keyed by the child paths this
     * component declares — empty when the record carries no related object, which
     * the null-fill pass turns into a null per child.
     *
     * @return array<string, mixed>
     */
    public function getStateFromRelationship(object $record): array;

    public function saveStateToRelationship(object $record, mixed $state): void;
}
