<?php

declare(strict_types=1);

namespace Sylament\Schemas\Query;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\Expr\Select;
use Doctrine\ORM\QueryBuilder;

final class QueryAliases
{
    /**
     * The entity a root or join alias of the builder stands for: a root's own
     * class, the target of the association a join crosses, or the entity a join
     * names outright. Null for an alias the builder does not declare, and for a
     * join off an alias or across a property the mapping cannot name.
     *
     * @return class-string|null
     */
    public static function classOf(QueryBuilder $query, string $alias): ?string
    {
        return self::walk($query)[$alias][0] ?? null;
    }

    /**
     * Whether the alias is a join over a collection-valued association that the
     * builder also selects — a fetch join, whose rows Doctrine hydrates into each
     * record's collection.
     */
    public static function isFetchJoinedCollection(QueryBuilder $query, string $alias): bool
    {
        if (!(self::walk($query)[$alias][1] ?? false)) {
            return false;
        }

        $part = $query->getDQLPart('select');

        foreach (is_array($part) ? $part : [] as $select) {
            if (!$select instanceof Select) {
                continue;
            }

            foreach ($select->getParts() as $expression) {
                foreach (explode(',', (string) $expression) as $piece) {
                    $piece = mb_trim($piece);

                    if ($piece === $alias || str_starts_with($piece, "partial {$alias}.")) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Every alias the builder declares, in declaration order, with its class and
     * whether a join reaches it across a collection-valued association.
     *
     * @return array<string, array{class-string, bool}>
     */
    private static function walk(QueryBuilder $query): array
    {
        $entityManager = $query->getEntityManager();
        $aliases = [];

        foreach ($query->getRootAliases() as $index => $rootAlias) {
            $rootClass = $query->getRootEntities()[$index] ?? null;

            if ($rootClass !== null) {
                $aliases[$rootAlias] = [$entityManager->getClassMetadata($rootClass)->getName(), false];
            }
        }

        $part = $query->getDQLPart('join');

        foreach (is_array($part) ? $part : [] as $joins) {
            foreach (is_array($joins) ? $joins : [] as $join) {
                if (!$join instanceof Join || $join->getAlias() === null) {
                    continue;
                }

                $expression = $join->getJoin();

                if (!str_contains($expression, '.')) {
                    if (class_exists($expression)) {
                        $aliases[$join->getAlias()] = [$entityManager->getClassMetadata($expression)->getName(), false];
                    }

                    continue;
                }

                [$parentAlias, $property] = explode('.', $expression, 2);
                $parentClass = $aliases[$parentAlias][0] ?? null;

                if ($parentClass === null) {
                    continue;
                }

                $metadata = $entityManager->getClassMetadata($parentClass);

                if (!$metadata->hasAssociation($property)) {
                    continue;
                }

                $aliases[$join->getAlias()] = [$metadata->getAssociationTargetClass($property), $metadata->isCollectionValuedAssociation($property)];
            }
        }

        return $aliases;
    }
}
