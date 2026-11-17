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
use Sylament\Tables\Filters\SelectFilter;
use Sylament\Tables\Filters\TernaryFilter;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Entity\Product;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A larder's jars, filtered by whether they are sealed, by their seal, by several seals,
 * by their supplier and by whether they carry a label.
 */
#[AsLiveComponent(name: 'Tables:FilterableLarderJarTable', template: '@Fixtures/tables/table.html.twig')]
class FilterableLarderJarTable implements InteractsWithSchemas, InteractsWithTable
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
        $seals = ['wax' => 'Wax', 'screw' => 'Screw'];

        return $table
            ->query($this->entityManager->createQueryBuilder()->select('jar')->from(LarderJar::class, 'jar'))
            ->columns([
                TextColumn::make('label'),
            ])
            ->filters([
                TernaryFilter::make('sealedOn')->nullable(),
                SelectFilter::make('seal')->options($seals),
                SelectFilter::make('seals')->attribute('seal')->multiple()->options($seals),
                SelectFilter::make('supplier')->options(fn (): array => $this->suppliers()),
                Filter::make('labelled')->query(static fn (QueryBuilder $query): QueryBuilder => $query->andWhere("jar.label <> ''")),
            ]);
    }

    /**
     * @return array<int, string>
     */
    private function suppliers(): array
    {
        $suppliers = [];

        foreach ($this->entityManager->getRepository(Product::class)->findAll() as $product) {
            $suppliers[$product->id] = $product->name;
        }

        return $suppliers;
    }
}
