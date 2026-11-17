<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A product table a user browses: sorts and searches by name, searches the name column
 * on its own, shows the id column on request and opens a product from its name.
 */
#[AsLiveComponent(name: 'Tables:BrowsableProductTable', template: '@Fixtures/tables/table.html.twig')]
class BrowsableProductTable implements InteractsWithTable
{
    use DefaultActionTrait;
    use HasTable;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly HookLog $hookLog,
    ) {
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->entityManager->createQueryBuilder()->select('product')->from(Product::class, 'product'))
            ->columns([
                TextColumn::make('name')
                    ->sortable()
                    ->searchable(isIndividual: true)
                    ->action(function (Product $record): void {
                        $this->hookLog->record("open {$record->name}");
                    }),
                TextColumn::make('id')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name');
    }
}
