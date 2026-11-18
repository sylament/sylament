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
use Sylament\Tables\Filters\Indicator;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A larder's jars under filters that chip in every way a filter can: always as a string, as
 * a built green indicator and as a list of two; when ticked, as an indicator removing its
 * `isActive` field, as one that cannot be removed while it keeps the sealed jars, and under a
 * prefix of its own while it keeps the waxed ones.
 */
#[AsLiveComponent(name: 'Tables:IndicatingJarFilterTable', template: '@Fixtures/tables/table.html.twig')]
class IndicatingJarFilterTable implements InteractsWithSchemas, InteractsWithTable
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
                Filter::make('worded')->indicateUsing(static fn (): string => 'Worded chip'),
                Filter::make('built')->indicateUsing(static fn (): Indicator => Indicator::make('Built chip')->color('success')),
                Filter::make('listed')->indicateUsing(static fn (): array => ['First chip', 'Second chip']),
                Filter::make('fielded')->indicateUsing(static fn (array $state): array => ($state['isActive'] ?? false) ? [Indicator::make('Field chip')->removeField('isActive')] : []),
                Filter::make('pinned')
                    ->query(static fn (QueryBuilder $query): QueryBuilder => $query->andWhere('jar.seal IS NOT NULL'))
                    ->indicateUsing(static fn (array $state): array => ($state['isActive'] ?? false) ? [Indicator::make('Pinned chip')->removable(false)] : []),
                Filter::make('waxed')->indicator('Wax only')->query(static fn (QueryBuilder $query): QueryBuilder => $query->andWhere("jar.seal = 'wax'")),
            ]);
    }
}
