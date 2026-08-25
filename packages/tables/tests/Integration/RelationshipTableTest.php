<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Integration;

use LogicException;
use Sylament\Tables\Tests\Fixtures\Concerns\TableHost;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Artist;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Genre;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Song;
use Sylament\Tables\Tests\Fixtures\Twig\Components\ArtistSongsTable;
use Sylament\Tables\Tests\Fixtures\Twig\Components\SongGenresTable;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

use function Sylament\Tables\Tests\seededGenresClient;

uses(InteractsWithLiveComponents::class);

/*
 * The relation-scoped table end to end: the arm is a base query, so only a live
 * host over a real database shows that record listing, record resolution,
 * search, sort, pagination, the selection lane and the two prebuilt deletes all
 * pick the relationship up unchanged. That is also why the delete pins sit here
 * rather than in actions' suite, whose kernel has neither a table nor a
 * database. Kernel reboot is disabled throughout — the fixture database is an
 * in-memory SQLite held by a container service, so a reboot between two
 * requests would hand the second an empty one.
 *
 * Every delete case deletes songs, never genres: `Song.genres` is mapped on
 * `Song`, and this rig's SQLite enforces no foreign keys, so a deleted `Genre`
 * would leave its join rows behind.
 */

/**
 * @param object $host a table host, read through its table
 * @return list<string>
 */
function relatedGenreNames(object $host): array
{
    assert($host instanceof SongGenresTable);

    return array_values(array_map(static fn (object $genre): string => $genre instanceof Genre ? $genre->getName() : '', array_values($host->getTable()->getRecords())));
}

/**
 * @return list<string>
 */
function relatedSongTitles(object $host): array
{
    assert($host instanceof ArtistSongsTable);

    return array_values(array_map(static fn (object $song): string => $song instanceof Song ? $song->getTitle() : '', array_values($host->getTable()->getRecords())));
}

it('lists only the owner related records, rooted at the target', function (): void {
    [$client] = seededGenresClient();
    $component = $this->createLiveComponent(SongGenresTable::class, ['songId' => 1], client: $client);
    $host = $component->component();

    expect(relatedGenreNames($host))->toBe(['Rock', 'Pop', 'Jazz'])
        ->and($host->getTable()->getRecordClass())->toBe(Genre::class)
        ->and($host->getTableRecord('4'))->toBeNull()
        ->and($host->getTableRecord('1'))->toBeInstanceOf(Genre::class)
        ->and((string) $component->render())->toContain('Rock');
});

it('composes search, sort and pagination over the relationship query', function (): void {
    [$client] = seededGenresClient();

    $searched = $this->createLiveComponent(SongGenresTable::class, ['songId' => 1], client: $client)
        ->set('tableSearch', 'ro')
        ->component();

    expect(relatedGenreNames($searched))->toBe(['Rock']);

    $sorted = $this->createLiveComponent(SongGenresTable::class, ['songId' => 1], client: $client)
        ->call('sortTable', ['column' => 'name', 'direction' => 'desc'])
        ->component();

    expect(relatedGenreNames($sorted))->toBe(['Rock', 'Pop', 'Jazz']);

    $paginated = $this->createLiveComponent(SongGenresTable::class, ['songId' => 1], client: $client)
        ->set('tableRecordsPerPage', '2')
        ->component();

    expect($paginated->getTable()->getRecords())->toHaveCount(2)
        ->and($paginated->getTablePaginator()?->getTotalRecordCount())->toBe(3);
});

it('orders by the mapping OrderBy until a header sort wins', function (): void {
    [$client, $entityManager] = seededGenresClient();
    $artist = new Artist(1, 'Radiohead');
    $entityManager->persist($artist);

    foreach ([new Song(10, 'Paranoid Android', 300), new Song(11, 'Airbag', 100), new Song(12, 'Let Down', 200)] as $song) {
        $artist->addSong($song);
        $entityManager->persist($song);
    }

    $entityManager->flush();

    $component = $this->createLiveComponent(ArtistSongsTable::class, ['artistId' => 1], client: $client);

    expect(relatedSongTitles($component->component()))->toBe(['Airbag', 'Let Down', 'Paranoid Android'])
        ->and(relatedSongTitles($component->call('sortTable', ['column' => 'plays', 'direction' => 'desc'])->component()))
        ->toBe(['Paranoid Android', 'Let Down', 'Airbag']);
});

it('refuses a table with neither records(), query() nor relationship()', function (): void {
    expect(static fn (): mixed => new TableHost()->getTableRecords())
        ->toThrow(LogicException::class, 'must have [records()], [query()] or [relationship()]');
});

it('deletes related records through the no-fetch bulk arm over the relationship query', function (): void {
    [$client, $entityManager] = seededGenresClient();
    $artist = new Artist(1, 'Radiohead');
    $entityManager->persist($artist);

    foreach ([new Song(10, 'Karma Police', 300), new Song(11, 'No Surprises', 100), new Song(12, 'Lucky', 200)] as $song) {
        $artist->addSong($song);
        $entityManager->persist($song);
    }

    $entityManager->flush();

    $component = $this->createLiveComponent(ArtistSongsTable::class, ['artistId' => 1, 'fetchesRecords' => false], client: $client);

    $component->set('selectedTableRecords', ['10', '11'])
        ->call('mountAction', ['name' => 'delete', 'arguments' => [], 'context' => ['table' => true, 'bulk' => true]])
        ->call('callMountedAction');

    $entityManager->clear();

    expect($entityManager->getRepository(Song::class)->count(['artist' => 1]))->toBe(1)
        ->and($entityManager->getRepository(Song::class)->count([]))->toBe(3)
        ->and($component->component()->getTable()->getRecords())->toHaveCount(1);
});

it('resolves the entity manager of a record through the relationship table first', function (): void {
    [$client, $entityManager] = seededGenresClient();
    $artist = new Artist(1, 'Radiohead');
    $song = new Song(10, 'Karma Police', 300);
    $artist->addSong($song);
    $entityManager->persist($artist);
    $entityManager->persist($song);
    $entityManager->flush();

    $component = $this->createLiveComponent(ArtistSongsTable::class, ['artistId' => 1], client: $client);

    // getEntityManagerForRecord() is protected, so the table's manager is asserted
    // directly and the delete landing in this in-memory SQLite is what proves the
    // action reached it through the table rather than through the registry.
    expect($component->component()->getTable()->getQueryEntityManager())->toBe($entityManager);

    $component->call('mountAction', ['name' => 'delete', 'arguments' => [], 'context' => ['table' => true, 'recordKey' => '10']])
        ->call('callMountedAction');

    expect($entityManager->find(Song::class, 10))->toBeNull()
        ->and($component->component()->getTable()->getRecords())->toHaveCount(0);
});
