<?php

declare(strict_types=1);

namespace Sylament\Panels\Resources\Resource\Concerns;

use Doctrine\ORM\QueryBuilder;
use Sylament\Actions\Action;
use Sylament\Panels\GlobalSearch\GlobalSearchResult;
use Sylament\Tables\Query\SearchConstraint;
use Sylament\Tables\Query\SearchTermSplitter;
use Twig\Markup;

use function Sylament\Support\blank;
use function Sylament\Support\wrap;

trait HasGlobalSearch
{
    protected static int $globalSearchResultsLimit = 50;

    protected static ?bool $isGlobalSearchForcedCaseInsensitive = null;

    protected static ?bool $shouldSplitGlobalSearchTerms = null;

    protected static bool $isGloballySearchable = true;

    protected static ?int $globalSearchSort = null;

    public function canGloballySearch(): bool
    {
        return static::$isGloballySearchable && $this->getGloballySearchableAttributes() !== [];
    }

    /**
     * @return array<string|array<string>>
     */
    public function getGloballySearchableAttributes(): array
    {
        $titleAttribute = $this->getRecordTitleAttribute();

        if ($titleAttribute === null) {
            return [];
        }

        return [$titleAttribute];
    }

    /**
     * @return array<Action>
     */
    public function getGlobalSearchResultActions(object $record): array
    {
        return [];
    }

    /**
     * @return array<string, string>|list<string>
     */
    public function getGlobalSearchResultDetails(object $record): array
    {
        return [];
    }

    public function getGlobalSearchResultTitle(object $record): string|Markup
    {
        return $this->getRecordTitle($record);
    }

    /**
     * A nested resource answers none: its pages need the parent records a search
     * result carries no parameters for, so a resource under a parent links
     * nowhere until it overrides this.
     */
    public function getGlobalSearchResultUrl(object $record): ?string
    {
        if ($this->getParentResourceRegistration() !== null) {
            return null;
        }

        if ($this->hasPage('view')) {
            return $this->getUrl('view', ['record' => $record]);
        }

        if ($this->hasPage('edit')) {
            return $this->getUrl('edit', ['record' => $record]);
        }

        if ($this->hasPage('index')) {
            return $this->getIndexUrl();
        }

        return null;
    }

    public function getGlobalSearchResultsLimit(): int
    {
        return static::$globalSearchResultsLimit;
    }

    public function modifyGlobalSearchQuery(QueryBuilder $query, string $search): void
    {
    }

    /**
     * @return list<GlobalSearchResult>
     */
    public function getGlobalSearchResults(string $search): array
    {
        $query = $this->getGlobalSearchRecordQuery();

        $this->applyGlobalSearchAttributeConstraints($query, $search);
        $this->modifyGlobalSearchQuery($query, $search);

        /** @var list<object> $records */
        $records = $query
            ->setMaxResults($this->getGlobalSearchResultsLimit())
            ->getQuery()
            ->getResult();

        $results = [];

        foreach ($records as $record) {
            $url = $this->getGlobalSearchResultUrl($record);

            if (blank($url)) {
                continue;
            }

            $results[] = new GlobalSearchResult(
                title: $this->getGlobalSearchResultTitle($record),
                url: $url,
                details: $this->getGlobalSearchResultDetails($record),
                actions: array_map(
                    fn (Action $action): Action => $action->hasRecord() ? $action : $action->record($record),
                    $this->getGlobalSearchResultActions($record),
                ),
            );
        }

        return $results;
    }

    public function isGlobalSearchForcedCaseInsensitive(): ?bool
    {
        return static::$isGlobalSearchForcedCaseInsensitive;
    }

    public function shouldSplitGlobalSearchTerms(): bool
    {
        return static::$shouldSplitGlobalSearchTerms ?? true;
    }

    public function getGlobalSearchRecordQuery(): QueryBuilder
    {
        return $this->getRecordQuery();
    }

    public function getGlobalSearchSort(): ?int
    {
        return static::$globalSearchSort;
    }

    protected function applyGlobalSearchAttributeConstraints(QueryBuilder $query, string $search): void
    {
        $searchWords = $this->shouldSplitGlobalSearchTerms() ? SearchTermSplitter::split($search) : [$search];
        $isForcedCaseInsensitive = $this->isGlobalSearchForcedCaseInsensitive();

        foreach ($searchWords as $searchWord) {
            $searchExpression = $query->expr()->orX();

            foreach ($this->getGloballySearchableAttributes() as $searchAttributes) {
                foreach (wrap($searchAttributes) as $searchAttribute) {
                    $searchExpression->add(SearchConstraint::forColumn($query, $searchAttribute, $searchWord, $isForcedCaseInsensitive));
                }
            }

            if ($searchExpression->count() > 0) {
                $query->andWhere($searchExpression);
            }
        }
    }
}
