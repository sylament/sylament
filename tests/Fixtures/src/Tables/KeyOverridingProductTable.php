<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Actions\DeleteAction;
use Sylament\Actions\DeleteBulkAction;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Tables\Record\ShelfItem;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A product table whose host keys each row as its `keyPrefix` followed by the product's id,
 * listing the products through its query (`lane` `query`), through a `records()` closure
 * that reads them and records each read (`records`), or as `ShelfItem` projections its
 * query selects, unpaginated (`projection`); each row deletes, and `inspect` records the row it runs on.
 */
#[AsLiveComponent(name: 'Tables:KeyOverridingProductTable', template: '@Fixtures/tables/table-with-modals.html.twig')]
class KeyOverridingProductTable implements InteractsWithActions, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasTable;

    #[LiveProp]
    public string $lane = 'query';

    #[LiveProp]
    public string $keyPrefix = '';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly HookLog $hookLog,
    ) {
    }

    public function table(Table $table): Table
    {
        $products = $this->entityManager->createQueryBuilder()->from(Product::class, 'product')->orderBy('product.name');

        $table = match ($this->lane) {
            'query' => $table->query($products->select('product')),
            'records' => $table->records(function (): array {
                $this->hookLog->record('records read');

                return $this->entityManager->getRepository(Product::class)->findBy([], ['name' => 'ASC']);
            }),
            'projection' => $table->query($products->select(sprintf('NEW %s(product.name, product.id)', ShelfItem::class))),
            default => throw new LogicException("No lane is named [{$this->lane}]."),
        };

        return $table
            ->columns([
                TextColumn::make('name'),
            ])
            ->paginated($this->lane !== 'projection')
            ->recordActions([
                DeleteAction::make(),
                Action::make('inspect')->action(function (Product $record): void {
                    $this->hookLog->record("inspect {$record->name}");
                }),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    public function getTableRecordKey(object|array $record): string
    {
        if (!$record instanceof Product && !$record instanceof ShelfItem) {
            throw new LogicException(sprintf('This table keys only products, not a [%s].', get_debug_type($record)));
        }

        return $this->keyPrefix . $record->id;
    }
}
