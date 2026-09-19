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
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Residency;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * The derived-identity counterpart of {@see CompositeShortcutsTable}: half of
 * {@see Residency}'s key is a to-one onto the `Handle`-keyed `Venue`, so the row
 * side reaches it through `IDENTITY()` and runs the value back through the
 * target's own DBAL type before stringifying it, while the record side reads the
 * object. The two must mint the same string, and every case here depends on that.
 */
#[AsLiveComponent(name: 'CompositeResidenciesTable', template: '@TablesFixtures/components/CompositeResidenciesTable.html.twig')]
class CompositeResidenciesTable implements InteractsWithActions, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasTable;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->entityManager->createQueryBuilder()->select('residency')->from(Residency::class, 'residency'))
            ->columns([
                TextColumn::make('label'),
                ToggleColumn::make('active'),
            ])
            ->recordActions([
                Action::make('promote')
                    ->requiresConfirmation()
                    ->action(function (Residency $record): void {
                        $record->setLabel($record->getLabel() . ' (promoted)');

                        $this->entityManager->flush();
                    }),
            ])
            ->toolbarActions([DeleteBulkAction::make()->fetchSelectedRecords(false)])
            ->reorderable('position');
    }
}
