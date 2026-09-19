<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Twig\Components;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Actions\DeleteBulkAction;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Columns\ToggleColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Shortcut;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * The scalar-pair composite host: a query-lane table over {@see Shortcut},
 * whose `host` and `path` both escape into the key, carrying every surface a
 * row key travels — a confirming record action that writes, an editable cell,
 * a by-key `DeleteBulkAction`, selection and a reorder column. The one
 * `LiveProp` flag is {@see BulkDeleteSongsTable}'s trick, so a test can rebuild
 * the same table with the fetching arm armed across a round trip. It declares
 * no summary and joins no collection, because a composite root under a
 * fetch-join paginator is epic 2's refusal.
 */
#[AsLiveComponent(name: 'CompositeShortcutsTable', template: '@TablesFixtures/components/CompositeShortcutsTable.html.twig')]
class CompositeShortcutsTable implements InteractsWithActions, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasTable;

    #[LiveProp]
    public bool $fetchesRecords = false;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->entityManager->createQueryBuilder()->select('shortcut')->from(Shortcut::class, 'shortcut'))
            ->columns([
                TextColumn::make('label'),
                ToggleColumn::make('active'),
            ])
            ->recordActions([
                Action::make('promote')
                    ->requiresConfirmation()
                    ->action(function (Shortcut $record): void {
                        $record->setLabel($record->getLabel() . ' (promoted)');

                        $this->entityManager->flush();
                    }),
            ])
            ->toolbarActions([DeleteBulkAction::make()->fetchSelectedRecords($this->fetchesRecords)])
            ->reorderable('position')
            ->paginated([10]);
    }
}
