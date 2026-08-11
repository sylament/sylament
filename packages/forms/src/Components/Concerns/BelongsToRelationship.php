<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\Concerns;

use Sylament\Schemas\Components\Concerns\BelongsToRelationship as SchemasBelongsToRelationship;

/**
 * The field-flavoured face of {@see SchemasBelongsToRelationship}: the
 * association resolution and its refusals live one package down, shared with the
 * layout components that entangle a whole subtree, and this fills in the two
 * clauses a *field* says differently — how a refusal names it, and that a field
 * has its own name to fall back on when `relationship()` passes none.
 */
trait BelongsToRelationship
{
    use SchemasBelongsToRelationship;

    protected function getRelationshipSubject(): string
    {
        return sprintf('The [%s] field', (string) $this->getName());
    }

    protected function getMissingRelationshipNameNote(): string
    {
        return ' and the field carries no name to fall back on';
    }
}
