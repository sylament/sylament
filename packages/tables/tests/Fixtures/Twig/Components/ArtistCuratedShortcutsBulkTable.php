<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Twig\Components;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Actions\DetachBulkAction;
use Sylament\Actions\DissociateBulkAction;
use Sylament\Schemas\State\Relationship;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Artist;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * One artist's one-to-many onto a scalar-pair target, carrying both bulk
 * actions: the mapped-by column is no part of the target's identifier, so the
 * dissociate is legal here, and nothing refuses a one-to-many under the detach.
 *
 * It is also the only host in this family that answers the selection itself.
 * The table's own read narrows through {@see \Sylament\Schemas\Identity\IdentifierConstraint},
 * so a selection entry that is neither a record nor a key cannot come out of it
 * and neither bulk action's shape refusal is reachable from a real host; nor can
 * a well-formed key naming no record, which is what the failure toast is for.
 */
#[AsLiveComponent(name: 'ArtistCuratedShortcutsBulkTable', template: '@TablesFixtures/components/ArtistCuratedShortcutsBulkTable.html.twig')]
class ArtistCuratedShortcutsBulkTable implements InteractsWithActions, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasTable {
        getSelectedTableRecords as private selectedTableRecordsThroughTable;
    }

    #[LiveProp]
    public int $artistId = 1;

    #[LiveProp]
    public bool $fetchesRecords = false;

    /**
     * What the host answers instead of the table's own read: `'rows'` an array
     * where a record or a key belongs, `'stale'` a well-formed key naming no
     * record, and anything else the table.
     */
    #[LiveProp]
    public string $selectionOverride = '';

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function table(Table $table): Table
    {
        return $table
            ->relationship(function (): ?Relationship {
                $artist = $this->entityManager->find(Artist::class, $this->artistId);

                return $artist instanceof Artist ? new Relationship($artist, 'curatedShortcuts') : null;
            })
            ->recordTitleAttribute('label')
            ->columns([TextColumn::make('label')])
            ->toolbarActions([
                DetachBulkAction::make()->fetchSelectedRecords($this->fetchesRecords),
                DissociateBulkAction::make()->fetchSelectedRecords($this->fetchesRecords),
            ])
            ->paginated([10]);
    }

    /**
     * @return list<object|array<string, mixed>>|list<string>
     */
    public function getSelectedTableRecords(bool $shouldFetchSelectedRecords = true): array
    {
        return match ($this->selectionOverride) {
            'rows' => [['label' => 'not a record']],
            'stale' => ['spare~missing'],
            default => $this->selectedTableRecordsThroughTable($shouldFetchSelectedRecords),
        };
    }
}
