<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Twig\Components;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Actions\DissociateBulkAction;
use Sylament\Schemas\State\Relationship;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Artist;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Locker;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * The dissociate host, carrying {@see DissociateBulkAction} over the two
 * one-to-manys a locker takes part in: the artist's `keptLockers`, whose target
 * is keyed by a converting single identifier, and the locker's own `leases`,
 * whose mapped-by is half the target's identifier and which the reader refuses.
 * The owner is a prop because those two arms are owned by different classes.
 */
#[AsLiveComponent(name: 'ArtistKeptLockersBulkTable', template: '@TablesFixtures/components/ArtistKeptLockersBulkTable.html.twig')]
class ArtistKeptLockersBulkTable implements InteractsWithActions, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasTable;

    /**
     * `'artist'` keys by an int, `'locker'` by the converting code string.
     */
    #[LiveProp]
    public string $ownerClass = 'artist';

    #[LiveProp]
    public string $ownerKey = '1';

    #[LiveProp]
    public string $association = 'keptLockers';

    #[LiveProp]
    public bool $fetchesRecords = false;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function table(Table $table): Table
    {
        return $table
            ->relationship(function (): ?Relationship {
                $owner = $this->ownerClass === 'locker'
                    ? $this->entityManager->find(Locker::class, $this->ownerKey)
                    : $this->entityManager->find(Artist::class, (int) $this->ownerKey);

                return $owner === null ? null : new Relationship($owner, $this->association);
            })
            ->recordTitleAttribute('label')
            ->columns([TextColumn::make('label')])
            ->toolbarActions([DissociateBulkAction::make()->fetchSelectedRecords($this->fetchesRecords)])
            ->paginated([10]);
    }
}
