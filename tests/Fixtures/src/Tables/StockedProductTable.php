<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A product table that registers a `resolveRecordUsing()` callback the table branch never
 * needs, each call recorded in the request's {@see HookLog}: its `restock` header action
 * reads the rows, writes a "Restocked" product and then ends as its `outcome` argument says,
 * and its grouped `rename` row action records the record it runs on.
 */
#[AsLiveComponent(name: 'Tables:StockedProductTable', template: '@Fixtures/tables/table-with-modals.html.twig')]
class StockedProductTable implements InteractsWithActions, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasTable;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly HookLog $hookLog,
    ) {
        $this->resolveRecordUsing(function (mixed $key): null {
            $this->hookLog->record('resolveRecordUsing: ' . get_debug_type($key));

            return null;
        });
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->entityManager->createQueryBuilder()->select('product')->from(Product::class, 'product'))
            ->columns([
                TextColumn::make('name'),
            ])
            ->defaultSort('name')
            ->headerActions([
                Action::make('restock')
                    ->requiresConfirmation()
                    ->action(function (Action $action, array $arguments): void {
                        $this->getTableRecords();

                        $this->entityManager->persist(new Product('Restocked'));
                        $this->entityManager->flush();

                        match ($arguments['outcome'] ?? null) {
                            'failure' => $action->failure(),
                            'halt' => $action->halt(),
                            'cancel' => $action->cancel(),
                            default => $action->success(),
                        };
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('rename')
                        ->requiresConfirmation()
                        ->action(function (Product $record): void {
                            $this->hookLog->record("rename: {$record->name}");
                        }),
                ]),
            ]);
    }
}
