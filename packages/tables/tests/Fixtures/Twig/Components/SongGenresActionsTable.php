<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Twig\Components;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Actions\AttachAction;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Actions\DetachAction;
use Sylament\Actions\DetachBulkAction;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\State\Relationship;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Genre;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Song;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * The attach family's host: one song's genres, carrying the three many-to-many
 * actions. It is a schema host as well as an action host because the attach
 * modal carries a record select, and it declares no schemas of its own — the
 * mounted action's is the only one.
 */
#[AsLiveComponent(name: 'SongGenresActionsTable', template: '@TablesFixtures/components/SongGenresActionsTable.html.twig')]
class SongGenresActionsTable implements InteractsWithActions, InteractsWithSchemas, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasSchemas;
    use HasTable;

    #[LiveProp]
    public int $songId = 1;

    #[LiveProp]
    public bool $fetchesRecords = true;

    #[LiveProp]
    public bool $multiple = false;

    #[LiveProp]
    public bool $preloads = false;

    #[LiveProp]
    public bool $titlesByClosure = false;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function table(Table $table): Table
    {
        $table
            ->relationship(function (): ?Relationship {
                $song = $this->entityManager->find(Song::class, $this->songId);

                return $song instanceof Song ? new Relationship($song, 'genres') : null;
            })
            ->recordTitleAttribute('name')
            ->columns([TextColumn::make('name')])
            ->headerActions([
                AttachAction::make()
                    ->multiple($this->multiple)
                    ->preloadRecordSelect($this->preloads),
            ])
            ->recordActions([DetachAction::make()])
            ->toolbarActions([DetachBulkAction::make()->fetchSelectedRecords($this->fetchesRecords)])
            ->paginated([10]);

        if ($this->titlesByClosure) {
            $table->recordTitle(fn (Genre $record): string => mb_strtoupper($record->getName()));
        }

        return $table;
    }

    /**
     * @return array<string>
     */
    public function schemas(): array
    {
        return [];
    }
}
