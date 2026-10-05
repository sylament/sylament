<?php

declare(strict_types=1);

namespace Sylament\Actions\Concerns;

use LogicException;
use Sylament\Schemas\Schema;

/**
 * @internal the default write closures of CreateAction, EditAction and ReplicateAction
 */
trait RefusesUnmappedData
{
    /**
     * Refuse by name every path `mutateDataUsing()` added to the data it was
     * handed that no component of the schema maps, which the default write
     * would drop.
     *
     * @param array<string, mixed> $data the hook's answer
     *
     * @throws LogicException when the hook added a path no component of the schema maps
     */
    protected function refuseUnmappedData(Schema $schema, array $data): void
    {
        $paths = $schema->findUnmappedAdditions($this->getUnmutatedData(), $data);

        if ($paths === []) {
            return;
        }

        throw new LogicException(sprintf(
            'The %s added [%s] in %s, but no component of its %s maps those paths, so the default write would drop them. Map each with a component — a Hidden one for a value the user should not type, with required(false) where the record would otherwise require it — or %s.',
            sprintf('[%s] action', $this->getName()),
            implode(', ', $paths),
            'mutateDataUsing()',
            'schema',
            'take the write over with using()',
        ));
    }
}
