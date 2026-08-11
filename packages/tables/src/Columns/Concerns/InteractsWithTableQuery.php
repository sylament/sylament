<?php

declare(strict_types=1);

namespace Sylament\Tables\Columns\Concerns;

use Closure;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\Query\Expr\Select;
use Doctrine\ORM\QueryBuilder;
use Stringable;
use Sylament\Tables\Query\ColumnPathResolver;
use Sylament\Tables\Query\RelationJoiner;
use Sylament\Tables\Query\RelationPathResolver;
use Sylament\Tables\Query\ResolvedRelationPath;
use Sylament\Tables\Query\SearchConstraint;

trait InteractsWithTableQuery
{
    public function applySort(QueryBuilder $query, string $direction = 'asc'): QueryBuilder
    {
        if ($this->sortQuery instanceof Closure) {
            $this->evaluate($this->sortQuery, [
                'direction' => $direction,
                'query' => $query,
            ]);

            return $query;
        }

        foreach ($this->getSortColumns() as $sortColumn) {
            $query->addOrderBy(ColumnPathResolver::resolveOrderable($query, $sortColumn), $direction);
        }

        return $query;
    }

    public function applySearchConstraint(QueryBuilder $query, Orx $searchExpression, string $search): void
    {
        if ($this->searchQuery instanceof Closure) {
            $constraint = $this->evaluateSearchQuery($query, $search);

            if ($constraint instanceof QueryBuilder) {
                return;
            }

            if (is_string($constraint) || $constraint instanceof Stringable) {
                $searchExpression->add($constraint);
            }

            return;
        }

        foreach ($this->getSearchColumns() as $searchColumn) {
            $searchExpression->add(SearchConstraint::forColumn($query, $searchColumn, $search, $this->isSearchForcedCaseInsensitive()));
        }
    }

    /**
     * Fetch-joins the relation this column reads through, so every cell of the
     * page is answered from the page query rather than by a lazy load per row.
     * A no-op unless the name resolves to a chain of to-one hops: a to-many one
     * would multiply the page's rows, and stays lazy. Nothing here is strict
     * about the field at the end — only the hops are read — so a name sorting
     * or searching would refuse still renders, joined or not.
     */
    public function applyEagerLoading(QueryBuilder $query): QueryBuilder
    {
        $analysed = RelationPathResolver::tryAnalyseQueryPath($query, $this->getName());

        if (!$analysed instanceof ResolvedRelationPath || !$analysed->isRelationPath() || $analysed->hasToManyHop()) {
            return $query;
        }

        $parentAlias = $query->getRootAliases()[0] ?? null;

        if ($parentAlias === null) {
            return $query;
        }

        foreach ($analysed->hops as $hop) {
            $parentAlias = RelationJoiner::ensureJoined($query, $parentAlias, [$hop]);

            if (!in_array($parentAlias, self::selectedAliases($query), true)) {
                $query->addSelect($parentAlias);
            }
        }

        return $query;
    }

    /**
     * Every expression the builder selects, so a hop already fetched — by the
     * caller's own query or by a column that crosses the same relation — is not
     * selected twice.
     *
     * @return list<string>
     */
    private static function selectedAliases(QueryBuilder $query): array
    {
        $part = $query->getDQLPart('select');
        $selected = [];

        foreach (is_array($part) ? $part : [] as $select) {
            if (!$select instanceof Select) {
                continue;
            }

            foreach ($select->getParts() as $expression) {
                $selected[] = (string) $expression;
            }
        }

        return $selected;
    }

    /**
     * Whatever the search closure returned — an expression to add to the search
     * group, or the builder it constrained itself, which joins nothing.
     */
    protected function evaluateSearchQuery(QueryBuilder $query, string $search): mixed
    {
        return $this->evaluate($this->searchQuery, [
            'query' => $query,
            'search' => $search,
            'searchQuery' => $search,
        ]);
    }
}
