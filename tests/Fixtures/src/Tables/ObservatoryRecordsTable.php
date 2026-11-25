<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\Persistence\ManagerRegistry;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Actions\DeleteAction;
use Sylament\Actions\DeleteBulkAction;
use Sylament\Tables\Columns\TextInputColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\SecondaryEntity\Observatory;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * The observatories the `secondary` manager holds, listed from a `records()` closure so the
 * table has no query manager of its own: an editable `name`, a row `delete` and a bulk delete,
 * each of which finds its manager through the registry.
 */
#[AsLiveComponent(name: 'Tables:ObservatoryRecordsTable', template: '@Fixtures/tables/table-with-modals.html.twig')]
class ObservatoryRecordsTable implements InteractsWithActions, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasTable;

    public function __construct(
        private readonly ManagerRegistry $registry,
    ) {
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): array => $this->registry->getRepository(Observatory::class)->findBy([], ['name' => 'ASC']))
            ->columns([
                TextInputColumn::make('name'),
            ])
            ->recordActions([
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }
}
