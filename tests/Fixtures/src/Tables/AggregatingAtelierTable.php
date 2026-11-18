<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\Atelier;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * An atelier table showing figures of its easels, pigments and stocks, beside its name:
 * one column per aggregate function over the easels' positions, a count of the pigments
 * (a many-to-many), an existence check, a count scoped to the easels past the first
 * position under an alias of its own, freely named lone declarations, an array form read
 * by its derived alias through a dotted path, and an array form only one of whose entries
 * names its column. `columns` names the aggregate columns to show; `scoped` narrows the
 * rows by the query's own alias.
 */
#[AsLiveComponent(name: 'Tables:AggregatingAtelierTable', template: '@Fixtures/tables/table.html.twig')]
class AggregatingAtelierTable implements InteractsWithTable
{
    use DefaultActionTrait;
    use HasTable;

    /**
     * @var list<string>
     */
    #[LiveProp]
    public array $columns = [];

    #[LiveProp]
    public bool $scoped = false;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function table(Table $table): Table
    {
        $aggregates = [
            TextColumn::make('easels_count')->counts('easels')->sortable(),
            TextColumn::make('pigments_count')->counts('pigments'),
            TextColumn::make('easels_exists')->exists('easels'),
            TextColumn::make('easels_avg_position')->avg('easels', 'position'),
            TextColumn::make('easels_max_position')->max('easels', 'position'),
            TextColumn::make('easels_min_position')->min('easels', 'position'),
            TextColumn::make('easels_sum_position')->sum('easels', 'position'),
            TextColumn::make('raised')->counts(['easels as raised' => static fn (QueryBuilder $query, string $alias): QueryBuilder => $query->andWhere("{$alias}.position > 1")]),
            TextColumn::make('easel_total')->counts('easels'),
            TextColumn::make('numbered')->counts('easels as positioned'),
            TextColumn::make('stocks_pigment_count')->counts(['stocks.pigment']),
            TextColumn::make('trophies')->counts(['easels', 'pigments as trophies']),
        ];

        $table
            ->query(fn (): QueryBuilder => $this->entityManager->createQueryBuilder()->select('atelier')->from(Atelier::class, 'atelier'))
            ->columns([
                TextColumn::make('name'),
                ...array_values(array_filter($aggregates, fn (TextColumn $column): bool => in_array($column->getName(), $this->columns, true))),
            ])
            ->defaultSort('name');

        if ($this->scoped) {
            $table->modifyQueryUsing(static fn (QueryBuilder $query): QueryBuilder => $query->andWhere("atelier.name <> 'West'"));
        }

        return $table;
    }
}
