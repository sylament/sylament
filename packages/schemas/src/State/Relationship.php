<?php

declare(strict_types=1);

namespace Sylament\Schemas\State;

/**
 * One owner record's association, as a table or an action names it: the owner
 * and the property Doctrine maps the association under. Everything the mapping
 * says about it — target, side, mapped-by field, order — is read on demand
 * through {@see AssociationReader::describeRelationship()}.
 */
final readonly class Relationship
{
    public function __construct(
        public object $owner,
        public string $name,
    ) {
    }
}
