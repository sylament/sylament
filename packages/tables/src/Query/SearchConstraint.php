<?php

declare(strict_types=1);

namespace Sylament\Tables\Query;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\QueryBuilder;
use LogicException;

final class SearchConstraint
{
    public static function like(QueryBuilder $query, string $path, string $search, ?bool $forceCaseInsensitive): Comparison
    {
        $caseInsensitive = $forceCaseInsensitive
            ?? $query->getEntityManager()->getConnection()->getDatabasePlatform() instanceof PostgreSQLPlatform;

        $parameter = 'sylament_search_' . count($query->getParameters());

        if ($caseInsensitive) {
            $query->setParameter($parameter, '%' . mb_strtolower($search) . '%');

            return $query->expr()->like("LOWER({$path})", ":{$parameter}");
        }

        $query->setParameter($parameter, '%' . $search . '%');

        return $query->expr()->like($path, ":{$parameter}");
    }

    /**
     * The predicate a search column becomes, whichever tier declared it. A path
     * the mapping crosses associations for becomes the correlated `EXISTS (…)`
     * DQL string {@see RelationConstraint::existsLike()} builds, so a to-many
     * hop filters roots instead of multiplying them; everything else becomes a
     * `LIKE` comparison — read off the root alias when the mapping resolves the
     * path without crossing anything (an embeddable field), off the caller's own
     * resolution otherwise. Either arm is for the caller to add to its own
     * predicate.
     *
     * @throws LogicException when the query names no root alias to read a mapped field off
     */
    public static function forColumn(QueryBuilder $query, string $column, string $search, ?bool $forceCaseInsensitive): string|Comparison
    {
        $analysed = RelationPathResolver::analyseQueryPath($query, $column);

        if ($analysed instanceof ResolvedRelationPath && $analysed->isRelationPath()) {
            return RelationConstraint::existsLike($query, $analysed, $search, $forceCaseInsensitive);
        }

        $path = match (true) {
            !$analysed instanceof ResolvedRelationPath => ColumnPathResolver::resolve($query, $column),
            default => ($query->getRootAliases()[0] ?? throw new LogicException('The table query has no root alias to resolve column paths against.')) . ".{$analysed->fieldPath}",
        };

        return self::like($query, $path, $search, $forceCaseInsensitive);
    }
}
