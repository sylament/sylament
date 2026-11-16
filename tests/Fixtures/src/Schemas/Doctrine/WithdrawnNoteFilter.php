<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas\Doctrine;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

/**
 * Hides every row whose `note` reads `withdrawn`, on any class mapping a `note` field:
 * the soft-delete shape, registered disabled for a test to enable.
 */
final class WithdrawnNoteFilter extends SQLFilter
{
    public const string NAME = 'schemas_withdrawn_note';

    public function addFilterConstraint(ClassMetadata $targetEntity, string $targetTableAlias): string
    {
        if (!$targetEntity->hasField('note')) {
            return '';
        }

        return sprintf("%s.%s <> 'withdrawn'", $targetTableAlias, $targetEntity->getColumnName('note'));
    }
}
