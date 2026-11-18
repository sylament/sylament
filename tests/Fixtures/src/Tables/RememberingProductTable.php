<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Filters\Filter;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\Product;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\Metadata\UrlMapping;

/**
 * A product table a routed page carries in its URL and remembers between visits: its sort,
 * its search and its page are bound to the query string (the page as `shelfPage`, under the
 * host's identifier, while its table names another), and its sort, search, column searches
 * and `short` filter are remembered in the session, beside the per-page choice of 5 or 10.
 */
#[AsLiveComponent(name: 'Tables:RememberingProductTable', template: '@Fixtures/tables/table.html.twig')]
class RememberingProductTable implements InteractsWithSchemas, InteractsWithTable
{
    use DefaultActionTrait;
    use HasSchemas;
    use HasTable;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array<string>
     */
    public function schemas(): array
    {
        return [];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->entityManager->createQueryBuilder()->select('product')->from(Product::class, 'product'))
            ->columns([
                TextColumn::make('name')->sortable()->searchable(isIndividual: true),
                TextColumn::make('id')->sortable(),
            ])
            ->filters([
                Filter::make('short')->query(static fn (QueryBuilder $query): QueryBuilder => $query->andWhere('LENGTH(product.name) <= 5')),
            ])
            ->paginationPageOptions([5, 10])
            ->queryStringIdentifier('ledger')
            ->persistSortInSession()
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistFiltersInSession();
    }

    public function getTableQueryStringIdentifier(): string
    {
        return 'shelf';
    }

    protected function getTableUrlMapping(string $prop): ?UrlMapping
    {
        return match ($prop) {
            'page' => new UrlMapping(as: $this->getTableQueryStringIdentifier() . 'Page'),
            'sort' => new UrlMapping(as: 'sort'),
            'search' => new UrlMapping(as: 'search'),
            default => null,
        };
    }
}
