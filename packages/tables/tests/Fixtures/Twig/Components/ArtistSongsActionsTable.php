<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Twig\Components;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Actions\AssociateAction;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Actions\DissociateAction;
use Sylament\Actions\DissociateBulkAction;
use Sylament\Forms\Components\Select;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
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
 * The one-to-many host for the associate family: one artist's songs, carrying
 * the associate/dissociate trio. It is a schema host too, because the associate
 * modal's record select is a schema — and `$association` swings the table onto
 * `Artist::$albums`, the orphan-removal mapping the dissociate refusals fire on.
 */
#[AsLiveComponent(name: 'ArtistSongsActionsTable', template: '@TablesFixtures/components/ArtistSongsActionsTable.html.twig')]
class ArtistSongsActionsTable implements InteractsWithActions, InteractsWithSchemas, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasSchemas;
    use HasTable;

    #[LiveProp]
    public int $artistId = 1;

    #[LiveProp]
    public bool $fetchesRecords = true;

    #[LiveProp]
    public bool $multiple = false;

    #[LiveProp]
    public bool $preloads = false;

    #[LiveProp]
    public string $association = 'songs';

    /**
     * Makes the record select label every submitted key, so a key the candidate
     * query excludes clears the select's own membership check and reaches the
     * action's re-resolution guard behind it.
     */
    #[LiveProp]
    public bool $offersEverySong = false;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * The host declares no schema of its own — the only schema here is the one
     * the associate modal mounts.
     *
     * @return array<string>
     */
    public function schemas(): array
    {
        return [];
    }

    public function table(Table $table): Table
    {
        return $table
            ->relationship(function (): ?Relationship {
                $artist = $this->entityManager->find(Artist::class, $this->artistId);

                return $artist instanceof Artist ? new Relationship($artist, $this->association) : null;
            })
            ->recordTitleAttribute('title')
            ->columns([TextColumn::make('title')])
            ->headerActions([
                AssociateAction::make()
                    ->multiple($this->multiple)
                    ->preloadRecordSelect($this->preloads)
                    ->recordSelect($this->offersEverySong ? static fn (Select $select): Select => $select->getOptionLabelUsing(static fn (): string => 'Karma Police') : null),
            ])
            ->recordActions([DissociateAction::make()])
            ->toolbarActions([DissociateBulkAction::make()->fetchSelectedRecords($this->fetchesRecords)])
            ->paginated([10]);
    }
}
