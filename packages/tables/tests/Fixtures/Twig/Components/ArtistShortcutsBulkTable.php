<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Twig\Components;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Actions\DetachBulkAction;
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
 * One artist's many-to-many onto a target of any identifier width, carrying
 * {@see DetachBulkAction} alone. The association is a prop because the owning
 * arm, the derived-identity arm and the inverse arm are the same host with a
 * different mapping under it, and every target here titles itself by `label`.
 */
#[AsLiveComponent(name: 'ArtistShortcutsBulkTable', template: '@TablesFixtures/components/ArtistShortcutsBulkTable.html.twig')]
class ArtistShortcutsBulkTable implements InteractsWithActions, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasTable;

    #[LiveProp]
    public int $artistId = 1;

    #[LiveProp]
    public string $association = 'shortcuts';

    #[LiveProp]
    public bool $fetchesRecords = true;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function table(Table $table): Table
    {
        return $table
            ->relationship(function (): ?Relationship {
                $artist = $this->entityManager->find(Artist::class, $this->artistId);

                return $artist instanceof Artist ? new Relationship($artist, $this->association) : null;
            })
            ->recordTitleAttribute('label')
            ->columns([TextColumn::make('label')])
            ->toolbarActions([DetachBulkAction::make()->fetchSelectedRecords($this->fetchesRecords)])
            ->paginated([10]);
    }
}
