<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManager;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Sylament\Tables\Tests\Fixtures\Concerns\KeyOverridingTableHost;
use Sylament\Tables\Tests\Fixtures\Concerns\TableHost;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Song;
use Sylament\Tables\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Tables\Tests\Fixtures\Records\Song as RecordSong;
use Sylament\Tables\Tests\Fixtures\Records\SongRow;

/*
 * A host overriding getTableRecordKey(): on the query lane every read that
 * turns a key back into rows decodes through RecordKey and never asks the
 * override, so an override answering another key is refused when the rows are
 * first keyed; on the records() lane the override keys and reads back object
 * rows alone, and array rows never ask it.
 */

function keyOverrideSongs(): EntityManager
{
    $entityManager = EntityManagerFactory::create();

    EntityManagerFactory::seed(
        $entityManager,
        new Song(1, 'Alpha', 10),
        new Song(2, 'Beta', 20),
        new Song(3, 'Gamma', 30),
    );

    return $entityManager;
}

it('refuses a query-lane override whose key the codec does not answer, when the rows are first keyed', function (bool $paginated): void {
    $entityManager = keyOverrideSongs();
    $host = new KeyOverridingTableHost(
        fn (Table $table): Table => $table
            ->query($entityManager->createQueryBuilder()->select('song')->from(Song::class, 'song'))
            ->columns([TextColumn::make('title')])
            ->paginated($paginated),
        fn (object|array $record, string $key): string => 'S' . $key,
    );

    expect(fn (): mixed => $host->getTableRecords())->toThrow(LogicException::class, sprintf(
        'Table [%s] keys a [%s] row as [S1] through getTableRecordKey(), but its query reads every key back through RecordKey, which keys that row as [1], so a selection, an action or a reorder would miss the row, and a select-all with that row unticked would act on it too. Answer the key RecordKey::of() answers, or list the rows through records(), where an object row is keyed and read back by getTableRecordKey() alone.',
        KeyOverridingTableHost::class,
        Song::class,
    ));
})->with([
    'paginated' => [true],
    'unpaginated' => [false],
]);

it('keys a query-lane table by an override that answers the codec key', function (): void {
    $entityManager = keyOverrideSongs();
    $host = new KeyOverridingTableHost(
        fn (Table $table): Table => $table
            ->query($entityManager->createQueryBuilder()->select('song')->from(Song::class, 'song'))
            ->columns([TextColumn::make('title')]),
        fn (object|array $record, string $key): string => $record instanceof Song ? (string) $record->getId() : $key,
    );

    $record = $host->getTableRecord('2');

    expect(array_keys($host->getTableRecords()->getRecords()))->toBe([1, 2, 3])
        ->and($record)->toBeInstanceOf(Song::class)
        ->and($record instanceof Song ? $record->getTitle() : null)->toBe('Beta');
});

it('keys, resolves and selects records()-lane object rows by the override alone', function (): void {
    $host = new KeyOverridingTableHost(
        fn (Table $table): Table => $table->records(fn (): array => [
            new RecordSong('Airbag', 'OK Computer', id: 1),
            new RecordSong('Let Down', 'OK Computer', id: 2),
            new RecordSong('Karma Police', 'OK Computer', id: 3),
        ]),
        fn (object|array $record, string $key): string => 'R' . $key,
    );

    $record = $host->getTableRecord('R2');
    $host->selectedTableRecordKeys = ['R1', 'R3'];

    expect(array_keys($host->getTableRecords()))->toBe(['R1', 'R2', 'R3'])
        ->and($record)->toBeInstanceOf(RecordSong::class)
        ->and($record instanceof RecordSong ? $record->title : null)->toBe('Let Down')
        ->and(array_map(static fn (object|array $row): ?string => $row instanceof RecordSong ? $row->title : null, $host->getSelectedTableRecords()))->toBe(['Airbag', 'Karma Police'])
        ->and($host->getAllSelectableTableRecordKeys())->toBe(['R1', 'R2', 'R3']);
});

it('keys records()-lane array rows by their __key, never asking the override', function (): void {
    $asked = 0;
    $host = new KeyOverridingTableHost(
        fn (Table $table): Table => $table->records(fn (): array => [
            ['__key' => 'a', 'title' => 'Airbag'],
            ['__key' => 'b', 'title' => 'Let Down'],
        ]),
        function (object|array $record, string $key) use (&$asked): string {
            $asked++;

            return 'X' . $key;
        },
    );

    expect(array_keys($host->getTableRecords()))->toBe(['a', 'b'])
        ->and($asked)->toBe(0);
});

it('keys a SELECT NEW projection on the query lane by its id, with or without an override', function (): void {
    $entityManager = keyOverrideSongs();
    // Doctrine's paginator cannot window a NEW projection, so the table lists it unpaginated.
    $configure = fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()->select(sprintf('NEW %s(song.id, song.title)', SongRow::class))->from(Song::class, 'song'))
        ->columns([TextColumn::make('title')])
        ->paginated(false);

    $plain = new TableHost($configure)->getTableRecords();
    $overridden = new KeyOverridingTableHost($configure, fn (object|array $record, string $key): string => 'D' . $key)->getTableRecords();

    expect($plain)->toBeArray()
        ->and(array_keys($plain))->toBe([1, 2, 3])
        ->and(array_first($plain))->toBeInstanceOf(SongRow::class)
        ->and($overridden)->toBeArray()
        ->and(array_keys($overridden))->toBe(['D1', 'D2', 'D3']);
});
