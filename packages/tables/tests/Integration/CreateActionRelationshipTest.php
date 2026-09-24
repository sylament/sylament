<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Integration;

use LogicException;
use Sylament\Schemas\Exceptions\ValidationException;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Artist;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Genre;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Song;
use Sylament\Tables\Tests\Fixtures\Twig\Components\CreateIntoRelationshipTable;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

use function Sylament\Tables\Tests\seededGenresClient;

uses(InteractsWithLiveComponents::class);

/*
 * The end-to-end pin for `CreateAction`'s relationship arm: the action only
 * attaches anything when a relation-scoped table hands it a relationship, and
 * the actions test kernel registers neither tables nor forms — so, like
 * `PrebuiltDeleteActionTest`, the case sits here and the src-mirror rule is
 * deliberately relaxed for it.
 *
 * Every case runs as a real live request with kernel reboot disabled: the
 * fixture database lives in memory on a container service, and the success
 * notification is a session flash an in-process call cannot reach.
 */

it('creates a genre attached to the song', function (): void {
    [$client, $entityManager] = seededGenresClient();

    $component = $this->createLiveComponent(CreateIntoRelationshipTable::class, ['shape' => 'genres', 'songId' => 1], client: $client)
        ->call('mountAction', ['name' => 'create', 'arguments' => [], 'context' => ['table' => true]])
        ->set('mountedActionsData.0.id', '5')
        ->set('mountedActionsData.0.name', 'Folk')
        ->call('callMountedAction');

    $song = $entityManager->find(Song::class, 1) ?? throw new LogicException('The seeded song went missing.');
    $entityManager->refresh($song);

    expect($entityManager->find(Genre::class, 5)?->getName())->toBe('Folk')
        ->and($song->getGenres())->toHaveCount(4)
        ->and($component->component()->mountedActions)->toBe([])
        ->and($component->getDispatchedBrowserEvent($component->render(), 'sylament:notification'))->not->toBeNull();
});

it('creates a song pointing at the artist through the mapped-by side', function (): void {
    [$client, $entityManager] = seededGenresClient();
    $entityManager->persist($artist = new Artist(1, 'Radiohead'));
    $entityManager->flush();

    $this->createLiveComponent(CreateIntoRelationshipTable::class, ['shape' => 'songs', 'artistId' => 1], client: $client)
        ->call('mountAction', ['name' => 'create', 'arguments' => [], 'context' => ['table' => true]])
        ->set('mountedActionsData.0.id', '7')
        ->set('mountedActionsData.0.title', 'Bodysnatchers')
        ->set('mountedActionsData.0.plays', '300')
        ->call('callMountedAction');

    $song = $entityManager->find(Song::class, 7);
    $entityManager->refresh($artist);

    expect($song?->getTitle())->toBe('Bodysnatchers')
        ->and($song?->getArtist())->toBe($artist)
        ->and($artist->getSongs())->toHaveCount(1);
});

it('lets the action own relationship closure win over the table', function (): void {
    [$client, $entityManager] = seededGenresClient();

    $this->createLiveComponent(CreateIntoRelationshipTable::class, ['shape' => 'genres', 'songId' => 1], client: $client)
        ->call('mountAction', ['name' => 'createElsewhere', 'arguments' => [], 'context' => ['table' => true]])
        ->set('mountedActionsData.0.id', '6')
        ->set('mountedActionsData.0.name', 'Grunge')
        ->call('callMountedAction');

    $songs = [
        $entityManager->find(Song::class, 1) ?? throw new LogicException('The seeded song went missing.'),
        $entityManager->find(Song::class, 2) ?? throw new LogicException('The seeded song went missing.'),
    ];

    foreach ($songs as $song) {
        $entityManager->refresh($song);
    }

    expect($songs[1]->getGenres())->toHaveCount(3)
        ->and($songs[0]->getGenres())->toHaveCount(3);
});

it('attaches every record the create-another arm makes, staying mounted', function (): void {
    [$client, $entityManager] = seededGenresClient();

    $component = $this->createLiveComponent(CreateIntoRelationshipTable::class, ['shape' => 'genres', 'songId' => 1], client: $client)
        ->call('mountAction', ['name' => 'create', 'arguments' => [], 'context' => ['table' => true]])
        ->set('mountedActionsData.0.id', '5')
        ->set('mountedActionsData.0.name', 'Folk')
        ->call('callMountedAction', ['arguments' => ['another' => true]]);

    expect($component->component()->mountedActions)->toHaveCount(1);

    $component
        ->set('mountedActionsData.0.id', '6')
        ->set('mountedActionsData.0.name', 'Grunge')
        ->call('callMountedAction');

    $song = $entityManager->find(Song::class, 1) ?? throw new LogicException('The seeded song went missing.');
    $entityManager->refresh($song);

    expect($song->getGenres())->toHaveCount(5)
        ->and($entityManager->find(Genre::class, 6)?->getName())->toBe('Grunge');
});

it('refuses an id another record holds as already used under the id field, writing nothing', function (string $shape, array $data, string $sql, array $rows): void {
    [$client, $entityManager, $recorder] = seededGenresClient();

    if ($shape === 'songs') {
        $entityManager->persist(new Artist(1, 'Radiohead'));
        $entityManager->flush();
    }

    $component = $this->createLiveComponent(CreateIntoRelationshipTable::class, ['shape' => $shape, 'songId' => 1, 'artistId' => 1], client: $client)
        ->call('mountAction', ['name' => 'create', 'arguments' => [], 'context' => ['table' => true]])
        ->set('mountedActionsData.0', $data);

    $recorder->reset();
    $errors = null;

    try {
        $component->call('callMountedAction');
    } catch (ValidationException $validationException) {
        $errors = $validationException->getErrors();
    }

    expect($errors)->toBe(['mountedActionsData.0.id' => ['This value is already used.']])
        ->and($recorder->statements())->toBe([])
        ->and($entityManager->isOpen())->toBeTrue()
        ->and($entityManager->getConnection()->fetchAllAssociative($sql))->toBe($rows);
})->with([
    'a genre the song does not carry' => ['genres', ['id' => '4', 'name' => 'Dup'], 'SELECT (SELECT COUNT(*) FROM Genre) AS genres, (SELECT COUNT(*) FROM song_genre) AS links', [['genres' => 4, 'links' => 5]]],
    'a genre the song already carries' => ['genres', ['id' => '1', 'name' => 'Dup'], 'SELECT (SELECT COUNT(*) FROM Genre) AS genres, (SELECT COUNT(*) FROM song_genre) AS links', [['genres' => 4, 'links' => 5]]],
    'a song of another artist' => ['songs', ['id' => '2', 'title' => 'Dup', 'plays' => '1'], 'SELECT id, title, artist_id FROM Song ORDER BY id', [['id' => 1, 'title' => 'Airbag', 'artist_id' => null], ['id' => 2, 'title' => 'Paranoid Android', 'artist_id' => null]]],
]);

it('finds a taken id the entity manager does not hold with one primary-key select, keeping the manager open', function (): void {
    [$client, $entityManager, $recorder] = seededGenresClient();

    $component = $this->createLiveComponent(CreateIntoRelationshipTable::class, ['shape' => 'genres', 'songId' => 1], client: $client)
        ->call('mountAction', ['name' => 'create', 'arguments' => [], 'context' => ['table' => true]])
        ->set('mountedActionsData.0', ['id' => '4', 'name' => 'Dup']);

    $entityManager->clear();
    $recorder->reset();
    $errors = null;

    try {
        $component->call('callMountedAction');
    } catch (ValidationException $validationException) {
        $errors = $validationException->getErrors();
    }

    $probes = array_filter($recorder->statements(), static fn (string $sql): bool => $sql === 'SELECT t0.id AS id_1, t0.name AS name_2 FROM Genre t0 WHERE t0.id = ?');
    $writes = array_filter($recorder->statements(), static fn (string $sql): bool => !str_starts_with($sql, 'SELECT'));

    expect($errors)->toBe(['mountedActionsData.0.id' => ['This value is already used.']])
        ->and($probes)->toHaveCount(1)
        ->and($writes)->toBe([])
        ->and($entityManager->isOpen())->toBeTrue()
        ->and($entityManager->getConnection()->fetchAllAssociative('SELECT (SELECT COUNT(*) FROM Genre) AS genres, (SELECT COUNT(*) FROM song_genre) AS links'))->toBe([['genres' => 4, 'links' => 5]]);
});

it('creates on a free id after one primary-key select', function (): void {
    [$client, , $recorder] = seededGenresClient();

    $component = $this->createLiveComponent(CreateIntoRelationshipTable::class, ['shape' => 'genres', 'songId' => 1], client: $client)
        ->call('mountAction', ['name' => 'create', 'arguments' => [], 'context' => ['table' => true]])
        ->set('mountedActionsData.0', ['id' => '5', 'name' => 'Folk']);

    $recorder->reset();
    $component->call('callMountedAction');

    expect(array_slice($recorder->statements(), 0, 5))->toBe([
        'SELECT t0.id AS id_1, t0.name AS name_2 FROM Genre t0 WHERE t0.id = ?',
        'BEGIN',
        'INSERT INTO Genre (id, name) VALUES (?, ?)',
        'INSERT INTO song_genre (song_id, genre_id) VALUES (?, ?)',
        'COMMIT',
    ]);
});
