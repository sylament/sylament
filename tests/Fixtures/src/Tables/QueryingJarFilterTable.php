<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Filters\Filter;
use Sylament\Tables\Filters\SelectFilter;
use Sylament\Tables\Filters\TernaryFilter;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A larder's jars joined to their larder as `larder`, always kept to the shelved ones (a
 * position above 0) by a filter with no `isActive` field, and filtered when set by a labelled
 * base query, a waxed-or-first condition, a filter logging what it was handed, the larder's
 * name and whether the jar or, under labels of its own, its larder has a supplier.
 */
#[AsLiveComponent(name: 'Tables:QueryingJarFilterTable', template: '@Fixtures/tables/table.html.twig')]
class QueryingJarFilterTable implements InteractsWithSchemas, InteractsWithTable
{
    use DefaultActionTrait;
    use HasSchemas;
    use HasTable;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly HookLog $hookLog,
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
            ->query($this->entityManager->createQueryBuilder()->select('jar')->from(LarderJar::class, 'jar')->leftJoin('jar.larder', 'larder'))
            ->columns([
                TextColumn::make('label'),
            ])
            ->filters([
                Filter::make('shelved')
                    ->schema([TextInput::make('shelf')])
                    ->query(static fn (QueryBuilder $query): QueryBuilder => $query->andWhere('jar.position > 0')),
                Filter::make('labelled')->baseQuery(static fn (QueryBuilder $query): QueryBuilder => $query->andWhere("jar.label <> ''")),
                Filter::make('waxOrFirst')->query(static fn (QueryBuilder $query): QueryBuilder => $query->andWhere($query->expr()->orX("jar.seal = 'wax'", 'jar.position = 1'))),
                Filter::make('injected')->query(function (Filter $filter, InteractsWithTable $liveComponent, Table $table, QueryBuilder $query): void {
                    $this->hookLog->record(sprintf('%s on %s, %s', $filter->getName(), $liveComponent::class, $table->getLiveComponent() === $liveComponent ? 'its own table' : 'another table'));
                }),
                SelectFilter::make('larder')->attribute('larder.name')->options(['Pantry' => 'Pantry', 'Cellar' => 'Cellar']),
                TernaryFilter::make('supplier')->nullable(),
                TernaryFilter::make('larderSupplied')->attribute('larder.supplier')->nullable()->trueLabel('Supplied')->falseLabel('Unsupplied')->options(['stray' => 'Stray']),
            ]);
    }
}
