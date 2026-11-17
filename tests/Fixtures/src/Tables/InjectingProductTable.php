<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Sylament\Actions\Action;
use Sylament\Actions\BulkAction;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Actions\DeleteAction;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A product table titling its records by name, whose actions record in the request's
 * {@see HookLog} what their closures were handed: the header action `stock` its record
 * class before any record exists, the row action `inspect` its table by name and by type,
 * its record and its host, and the bulk action `tally`, which deselects the rows once it
 * ran, the selection by name and the selection's query by name and by type.
 */
#[AsLiveComponent(name: 'Tables:InjectingProductTable', template: '@Fixtures/tables/table-with-modals.html.twig')]
class InjectingProductTable implements InteractsWithActions, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasTable;

    private HookLog $hookLog;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Required]
    public function setHookLog(HookLog $hookLog): void
    {
        $this->hookLog = $hookLog;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->entityManager->createQueryBuilder()->select('product')->from(Product::class, 'product'))
            ->columns([
                TextColumn::make('name'),
            ])
            ->defaultSort('name')
            ->recordTitleAttribute('name')
            ->headerActions([
                Action::make('stock')->action(fn (?string $recordClass, mixed $record) => $this->hookLog->record(sprintf('stock %s %s', $recordClass, get_debug_type($record)))),
            ])
            ->recordActions([
                DeleteAction::make(),
                Action::make('inspect')->action(function (?Table $table, Table $grid, Product $record, object $liveComponent): void {
                    $this->hookLog->record(sprintf('inspect %s, its table %s, its host %s', $record->name, $table === $grid && $grid === $this->getTable() ? 'bound' : 'not bound', $liveComponent === $this ? 'bound' : 'not bound'));
                }),
            ])
            ->toolbarActions([
                BulkAction::make('tally')
                    ->deselectRecordsAfterCompletion()
                    ->action(function (array $records, QueryBuilder $recordsQuery, QueryBuilder $query): void {
                        $names = array_map(static fn (mixed $product): string => $product instanceof Product ? $product->name : get_debug_type($product), $records);
                        sort($names);
                        $this->hookLog->record(sprintf('tally %s, queried as %d and %d', implode(' and ', $names), count($recordsQuery->getQuery()->getArrayResult()), count($query->getQuery()->getArrayResult())));
                    }),
            ]);
    }
}
