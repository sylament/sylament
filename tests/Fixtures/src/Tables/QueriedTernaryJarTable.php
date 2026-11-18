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
use Sylament\Tables\Filters\Indicator;
use Sylament\Tables\Filters\TernaryFilter;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A larder's jars under two ternary filters with queries of their own: one keeps the waxed,
 * the screwed or, while blank, the shelved jars (a position above 0); the other replaces its
 * arms with a query keeping the fig jar whichever way it is set, and chips as an indicator of
 * its own.
 */
#[AsLiveComponent(name: 'Tables:QueriedTernaryJarTable', template: '@Fixtures/tables/table.html.twig')]
class QueriedTernaryJarTable implements InteractsWithSchemas, InteractsWithTable
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
            ->query($this->entityManager->createQueryBuilder()->select('jar')->from(LarderJar::class, 'jar'))
            ->columns([
                TextColumn::make('label'),
            ])
            ->filters([
                TernaryFilter::make('waxed')->queries(
                    true: static fn (QueryBuilder $query): QueryBuilder => $query->andWhere("jar.seal = 'wax'"),
                    false: static fn (QueryBuilder $query): QueryBuilder => $query->andWhere("jar.seal = 'screw'"),
                    blank: static fn (QueryBuilder $query): QueryBuilder => $query->andWhere('jar.position > 0'),
                ),
                TernaryFilter::make('figOnly')->indicator(Indicator::make('Fig chip'))->query(static fn (QueryBuilder $query, array $data): QueryBuilder => ($data['value'] ?? null) === null ? $query : $query->andWhere("jar.label = 'Fig'")),
            ]);
    }
}
