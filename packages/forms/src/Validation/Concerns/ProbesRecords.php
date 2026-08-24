<?php

declare(strict_types=1);

namespace Sylament\Forms\Validation\Concerns;

use Closure;
use Doctrine\ORM\QueryBuilder;
use LogicException;
use Sylament\Schemas\State\AssociationReader;

/**
 * The DQL probe {@see \Sylament\Forms\Validation\UniqueValueValidator} and
 * {@see \Sylament\Forms\Validation\ExistingValueValidator} share: one query over
 * the record's own entity manager, with the guards that keep a skipped probe from
 * passing silently.
 */
trait ProbesRecords
{
    /**
     * @param class-string $entityClass
     * @param string       $arm         the field-side call this probe backs, for the guard messages
     */
    private function createProbeQuery(AssociationReader $reader, string $entityClass, string $property, string $arm): QueryBuilder
    {
        if (! $reader->hasEntityManager()) {
            throw new LogicException(sprintf(
                '%s needs a Doctrine entity manager to probe [%s::$%s], and none is registered.',
                $arm,
                $entityClass,
                $property,
            ));
        }

        $query = $reader->createOptionQuery($entityClass);
        $fieldNames = $query->getEntityManager()->getClassMetadata($entityClass)->getFieldNames();

        if (! in_array($property, $fieldNames, true)) {
            throw new LogicException(sprintf(
                '%s probes [%s] by [%s], which is not a mapped field of that entity. Name a field it maps: [%s].',
                $arm,
                $entityClass,
                $property,
                implode('], [', $fieldNames),
            ));
        }

        return $query;
    }

    /**
     * A parameter name no earlier `andWhere()` — the caller's `modifyQueryUsing`
     * included — can already have bound.
     */
    private function nextParameter(QueryBuilder $query): string
    {
        return 'sylament_probe_' . count($query->getParameters());
    }

    private function applyModifier(QueryBuilder $query, ?Closure $modifyQueryUsing): void
    {
        if (! $modifyQueryUsing instanceof Closure) {
            return;
        }

        $modifyQueryUsing($query);
    }
}
