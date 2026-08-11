<?php

declare(strict_types=1);

namespace Sylament\Tables\Query;

use Closure;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\QueryBuilder;
use LogicException;
use Stringable;

final class AggregateQuery
{
    /**
     * One batched read of a declared aggregate for a whole page: a grouped
     * scalar select over the record class, joined along the declared path and
     * narrowed to the page's keys, never touching the page query itself.
     *
     * The joins are inner, so a key whose related set is empty produces no
     * group and is absent from the result rather than zero — what a missing
     * key reads as is the caller's decision, not this one's. The scope closure
     * arrives already bound, and is handed the builder and the alias of the
     * path's last hop so it can narrow the aggregated set.
     *
     * @param class-string     $recordClass
     * @param list<int|string> $keys
     *
     * @return array<string, int|float|string|null> record key ⇒ raw value
     *
     * @throws LogicException when the record class carries a composite
     *                        identifier, which no single key column addresses
     */
    public static function fetch(EntityManagerInterface $entityManager, string $recordClass, AggregateDeclaration $declaration, array $keys): array
    {
        try {
            $identifier = $entityManager->getClassMetadata($recordClass)->getSingleIdentifierFieldName();
        } catch (MappingException) {
            throw new LogicException(sprintf('Cannot aggregate [%s]: the record class [%s] has a composite identifier.', $declaration->relationshipPath, $recordClass));
        }

        $query = $entityManager->createQueryBuilder()->from($recordClass, 'sy_r');
        $alias = 'sy_r';

        foreach (explode('.', $declaration->relationshipPath) as $index => $segment) {
            $next = "sy_t{$index}";

            $query->join("{$alias}.{$segment}", $next);

            $alias = $next;
        }

        $expression = $declaration->column === null ? $alias : "{$alias}.{$declaration->column}";

        $query
            ->select("sy_r.{$identifier} AS sy_key", "{$declaration->function}({$expression}) AS sy_value")
            ->where("sy_r.{$identifier} IN (:sylament_agg_keys)")
            ->setParameter('sylament_agg_keys', $keys)
            ->groupBy("sy_r.{$identifier}");

        if ($declaration->scope instanceof Closure) {
            ($declaration->scope)($query, $alias);
        }

        return self::readValues($query, $recordClass);
    }

    /**
     * The batch read back as record key ⇒ value, with an identifier that
     * hydrated as an object — a Uuid, a Ulid, any string-backed value object —
     * unwrapped to the string the page keyed its records by, the same way
     * `getTableRecordKey()` unwraps it on the page side. The two must agree, or
     * the store is filled under keys no cell ever asks for.
     *
     * Nothing here recovers from a row it cannot read: every shape below is one
     * the select this class authored cannot produce, so meeting one means the
     * query was rewritten out from under it, and a skipped row would surface as
     * a cell quietly reading its default rather than as an error.
     *
     * @param class-string $recordClass
     *
     * @return array<string, int|float|string|null>
     *
     * @throws LogicException when a row is not the scalar pair that was selected
     */
    private static function readValues(QueryBuilder $query, string $recordClass): array
    {
        $values = [];

        foreach ($query->getQuery()->getArrayResult() as $row) {
            if (!is_array($row)) {
                throw new LogicException(sprintf('The aggregate batch over [%s] returned a %s where the selected key and value were expected.', $recordClass, get_debug_type($row)));
            }

            $key = $row['sy_key'] ?? null;
            $value = $row['sy_value'] ?? null;

            if ($key instanceof Stringable) {
                $key = (string) $key;
            }

            if (!is_scalar($key)) {
                throw new LogicException(sprintf('Cannot key the aggregate batch over [%s]: its identifier read back as %s, which is neither scalar nor stringable.', $recordClass, get_debug_type($key)));
            }

            if ($value !== null && !is_int($value) && !is_float($value) && !is_string($value)) {
                throw new LogicException(sprintf('The aggregate batch over [%s] returned a %s value, which no cell can carry.', $recordClass, get_debug_type($value)));
            }

            $values[(string) $key] = $value;
        }

        return $values;
    }
}
