<?php

declare(strict_types=1);

namespace Sylament\Tables\Query;

use Closure;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\QueryBuilder;
use LogicException;

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

        return self::readValues($query);
    }

    /**
     * @return array<string, int|float|string|null>
     */
    private static function readValues(QueryBuilder $query): array
    {
        $values = [];

        foreach ($query->getQuery()->getArrayResult() as $row) {
            if (!is_array($row)) {
                continue;
            }

            $key = $row['sy_key'] ?? null;
            $value = $row['sy_value'] ?? null;

            if (!is_scalar($key)) {
                continue;
            }

            $values[(string) $key] = is_int($value) || is_float($value) || is_string($value) ? $value : null;
        }

        return $values;
    }
}
