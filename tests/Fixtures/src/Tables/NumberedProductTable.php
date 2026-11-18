<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\Product;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A product table, two to a page or all at once, numbering its rows from one and from zero.
 */
#[AsLiveComponent(name: 'Tables:NumberedProductTable', template: '@Fixtures/tables/table.html.twig')]
class NumberedProductTable implements InteractsWithTable
{
    use DefaultActionTrait;
    use HasTable;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->entityManager->createQueryBuilder()->select('product')->from(Product::class, 'product'))
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('number')->rowIndex(),
                TextColumn::make('index')->rowIndex(isFromZero: true),
            ])
            ->defaultSort('name')
            ->paginated([2, 'all']);
    }
}
