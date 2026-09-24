<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManager;
use Sylament\Schemas\State\Casts\Contracts\StateCast;
use Sylament\Tables\Columns\CheckboxColumn;
use Sylament\Tables\Columns\SelectColumn;
use Sylament\Tables\Columns\TextInputColumn;
use Sylament\Tables\Columns\ToggleColumn;
use Sylament\Tables\Exceptions\RecordWriteException;
use Sylament\Tables\Table;
use Sylament\Tables\Tests\Fixtures\Concerns\TableHost;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Artist;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Residency;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Shortcut;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Song;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Venue;
use Sylament\Tables\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Tables\Tests\Fixtures\Doctrine\Handle;
use Sylament\Tables\Tests\Fixtures\Doctrine\RecordingMiddleware;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Validation;

/**
 * A cast whose two directions are distinguishable from every other conversion the
 * cell makes, so removing either application leaves a failure behind.
 */
final class ShoutingStateCast implements StateCast
{
    public function get(mixed $state): mixed
    {
        return is_string($state) ? mb_strtoupper($state) : $state;
    }

    public function set(mixed $state): mixed
    {
        return is_string($state) ? mb_strtolower($state) : $state;
    }
}

/**
 * A cast that does not commute with the shouting one in either direction: its
 * marker carries a capital the shout would flatten on the way out, and prepending
 * before rather than after the shout would uppercase the marker on the way in.
 */
final class PrefixingStateCast implements StateCast
{
    private const string MARKER = 'The ';

    public function get(mixed $state): mixed
    {
        return is_string($state) ? self::MARKER . $state : $state;
    }

    public function set(mixed $state): mixed
    {
        return is_string($state) && str_starts_with($state, self::MARKER)
            ? mb_substr($state, mb_strlen(self::MARKER))
            : $state;
    }
}

final class CastingTextInputColumn extends TextInputColumn
{
    /**
     * @return array<StateCast>
     */
    public function getStateCasts(): array
    {
        return [new ShoutingStateCast(), new PrefixingStateCast()];
    }
}

/*
 * The write half of the editable seam, over a real in-memory entity manager the
 * table's query lane supplies — the same manager the assertions then read back
 * through, so a write that never reached Doctrine cannot pass.
 */

/**
 * @return array{EntityManager, Table}
 */
function songWriteRig(): array
{
    $entityManager = EntityManagerFactory::create();
    $radiohead = new Artist(1, 'Radiohead');

    EntityManagerFactory::seed($entityManager, $radiohead, new Song(1, 'Airbag', 100, $radiohead));

    $table = new TableHost(fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()->select('song')->from(Song::class, 'song')))->getTable();

    return [$entityManager, $table];
}

it('writes the state onto the record and flushes it', function (): void {
    [$entityManager, $table] = songWriteRig();
    $song = $entityManager->find(Song::class, 1);

    $returned = TextInputColumn::make('title')->table($table)->record($song)->updateState('Bones');
    $entityManager->clear();

    expect($returned)->toBe('Bones')
        ->and($entityManager->find(Song::class, 1)?->getTitle())->toBe('Bones');
});

it('normalises a blank input to null before anything the column does with it', function (): void {
    [$entityManager, $table] = songWriteRig();
    $seen = [];

    SelectColumn::make('mood')
        ->table($table)
        ->record($entityManager->find(Song::class, 1))
        ->beforeStateUpdated(function (mixed $state) use (&$seen): void {
            $seen[] = $state;
        })
        ->updateState('');

    $entityManager->clear();

    expect($seen)->toBe([null])
        ->and($entityManager->find(Song::class, 1)?->getMood())->toBeNull();
});

it('runs the before hook, then the write, then the after hook', function (): void {
    [$entityManager, $table] = songWriteRig();
    $calls = [];

    TextInputColumn::make('title')
        ->table($table)
        ->record($entityManager->find(Song::class, 1))
        ->beforeStateUpdated(function (Song $record) use (&$calls): void {
            $calls[] = 'before:' . $record->getTitle();
        })
        ->afterStateUpdated(function (Song $record, mixed $state) use (&$calls): void {
            $calls[] = 'after:' . $record->getTitle() . ':' . (is_string($state) ? $state : '');
        })
        ->updateState('Bones');

    expect($calls)->toBe(['before:Airbag', 'after:Bones:Bones']);
});

it('lets updateStateUsing replace the write, still running the after hook', function (): void {
    [$entityManager, $table] = songWriteRig();
    $calls = [];

    $returned = TextInputColumn::make('title')
        ->table($table)
        ->record($entityManager->find(Song::class, 1))
        ->updateStateUsing(function (mixed $state) use (&$calls): string {
            $calls[] = 'using';

            return is_string($state) ? mb_strtoupper($state) : '';
        })
        ->afterStateUpdated(function () use (&$calls): void {
            $calls[] = 'after';
        })
        ->updateState('Bones');

    $entityManager->clear();

    expect($returned)->toBe('BONES')
        ->and($calls)->toBe(['using', 'after'])
        ->and($entityManager->find(Song::class, 1)?->getTitle())->toBe('Airbag');
});

it('still runs the after hook when the override arm throws', function (): void {
    [$entityManager, $table] = songWriteRig();
    $calls = [];

    $column = TextInputColumn::make('title')
        ->table($table)
        ->record($entityManager->find(Song::class, 1))
        ->updateStateUsing(function (): never {
            throw new RuntimeException('The override blew up.');
        })
        ->afterStateUpdated(function () use (&$calls): void {
            $calls[] = 'after';
        });

    expect(fn (): mixed => $column->updateState('Bones'))->toThrow(RuntimeException::class, 'blew up')
        ->and($calls)->toBe(['after']);
});

it('refuses an array record instead of silently writing nothing', function (): void {
    $column = TextInputColumn::make('title')
        ->table(new TableHost(fn (Table $table): Table => $table->records(fn (): array => []))->getTable())
        ->record(['title' => 'Airbag']);

    expect(fn (): mixed => $column->updateState('Bones'))
        ->toThrow(LogicException::class, 'has no record to write to on the records() lane; give it updateStateUsing().');
});

it('writes a dotted path through the to-one association', function (): void {
    [$entityManager, $table] = songWriteRig();

    TextInputColumn::make('artist.name')
        ->table($table)
        ->record($entityManager->find(Song::class, 1))
        ->updateState('Thom');

    $entityManager->clear();

    expect($entityManager->find(Artist::class, 1)?->getName())->toBe('Thom');
});

it('refuses a collection leaf in the column vocabulary', function (): void {
    [$entityManager, $table] = songWriteRig();

    $column = TextInputColumn::make('genres')
        ->table($table)
        ->record($entityManager->find(Song::class, 1));

    expect(fn (): mixed => $column->updateState('Rock'))
        ->toThrow(LogicException::class, 'The editable column [genres] cannot write to its record');
});

it('refuses to persist without a manager anywhere', function (): void {
    $column = TextInputColumn::make('title')
        ->table(new TableHost(fn (Table $table): Table => $table->records(fn (): array => []))->getTable())
        ->record(new Song(1, 'Airbag', 100));

    expect(fn (): mixed => $column->updateState('Bones'))
        ->toThrow(LogicException::class, 'needs the container-built instance');
});

it('publishes a boolean cell state as one or zero', function (): void {
    [$entityManager, $table] = songWriteRig();
    $song = $entityManager->find(Song::class, 1);

    $toggle = ToggleColumn::make('isFeatured')->table($table)->record($song)->recordKey('1');
    $checkbox = CheckboxColumn::make('isFeatured')->table($table)->record($song)->recordKey('1');

    expect($toggle->getClientState())->toBe('0')
        ->and($checkbox->getClientState())->toBe('0');

    $toggle->updateState(true);
    $toggle->clearCachedState();

    expect($toggle->getClientState())->toBe('1');
});

it('publishes a null cell state as the empty string and a scalar as its string form', function (): void {
    [$entityManager, $table] = songWriteRig();
    $song = $entityManager->find(Song::class, 1);

    expect(SelectColumn::make('mood')->table($table)->record($song)->recordKey('1')->getClientState())->toBe('')
        ->and(TextInputColumn::make('plays')->table($table)->record($song)->recordKey('1')->getClientState())->toBe('100');
});

it('has no cell state error, and no client state to draw from one, without a record key', function (): void {
    [$entityManager, $table] = songWriteRig();

    $column = TextInputColumn::make('title')->table($table)->record($entityManager->find(Song::class, 1));

    expect($column->getUpdateStateError())->toBeNull()
        ->and($column->getClientState())->toBe('Airbag');
});

it('casts a posted value into the record vocabulary, outermost first, before the hook and the write', function (): void {
    [$entityManager, $table] = songWriteRig();
    $seen = [];

    CastingTextInputColumn::make('title')
        ->table($table)
        ->record($entityManager->find(Song::class, 1))
        ->beforeStateUpdated(function (mixed $state) use (&$seen): void {
            $seen[] = $state;
        })
        ->updateState('bones');

    $entityManager->clear();

    expect($seen)->toBe(['The BONES'])
        ->and($entityManager->find(Song::class, 1)?->getTitle())->toBe('The BONES');
});

it('echoes a refused input back as it was posted, without unwinding the casts over it', function (): void {
    $entityManager = EntityManagerFactory::create();
    EntityManagerFactory::seed($entityManager, new Song(1, 'Airbag', 100));

    $host = new TableHost(fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()->select('song')->from(Song::class, 'song'))
        ->columns([CastingTextInputColumn::make('title')->constraint(new Length(min: 30))]));

    $column = $host->getTable()->getColumn('title');

    assert($column instanceof CastingTextInputColumn);
    $column->setValidator(Validation::createValidator());

    $host->updateTableColumnState('title', '1', 'The AB');

    expect($column->getClientState())->toBe('The AB');
});

it('reverses the casts for the client state, unwinding the outermost one last', function (): void {
    [, $table] = songWriteRig();

    $column = CastingTextInputColumn::make('title')
        ->table($table)
        ->record(new Song(2, 'The AIRBAG', 100))
        ->recordKey('2');

    expect($column->getClientState())->toBe('airbag');
});

/*
 * An identifier field written from a cell: every flavour a cell reaches, over a
 * manager of the rig's own rather than the container's, so the check has to
 * read the record through the manager that owns it. The recorder is reset once
 * the record is loaded, so a refusal that let anything through shows as SQL.
 */

/**
 * @param class-string $class
 *
 * @return array{EntityManager, RecordingMiddleware, Table}
 */
function identifierWriteRig(string $class): array
{
    $recorder = new RecordingMiddleware();
    $entityManager = EntityManagerFactory::create($recorder);
    $records = [];

    foreach ([['example.org', 'home'], ['example.org', 'about'], ['sylament.test', 'home']] as $position => [$host, $path]) {
        $shortcut = new Shortcut();
        $shortcut->setHost($host);
        $shortcut->setPath($path);
        $shortcut->setLabel(ucfirst($path));
        $shortcut->setPosition($position);
        $shortcut->setActive(true);
        $records[] = $shortcut;
    }

    $north = new Venue(new Handle('north.hall'), 'North Hall');
    $residency = new Residency();
    $residency->setVenue($north);
    $residency->setSeason('summer');
    $residency->setLabel('Summer');
    $residency->setPosition(1);
    $residency->setActive(true);

    $radiohead = new Artist(1, 'Radiohead');

    EntityManagerFactory::seed($entityManager, ...[...$records, $north, $residency, $radiohead, new Song(1, 'Airbag', 100, $radiohead), new Song(2, 'Paranoid Android', 200, $radiohead)]);

    $table = new TableHost(fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()->select('record')->from($class, 'record')))->getTable();

    return [$entityManager, $recorder, $table];
}

it('refuses a changed identifier field with the bare message, puts the key back and runs no statement', function (string $class, array $criteria, Closure $column, string $input, Closure $key, mixed $storedKey, string $sql, array $rows): void {
    [$entityManager, $recorder, $table] = identifierWriteRig($class);
    $record = $entityManager->getRepository($class)->findOneBy($criteria) ?? throw new LogicException('The seeded record went missing.');
    $recorder->reset();
    $refusal = null;

    try {
        $column()->table($table)->record($record)->updateState($input);
    } catch (RecordWriteException $recordWriteException) {
        $refusal = $recordWriteException;
    }

    expect($refusal)->toBeInstanceOf(RecordWriteException::class)
        ->and($refusal?->getMessage())->toBe('This value cannot be changed.')
        ->and($key($record))->toBe($storedKey)
        ->and($recorder->statements())->toBe([])
        ->and($entityManager->isOpen())->toBeTrue()
        ->and($entityManager->getConnection()->fetchAllAssociative($sql))->toBe($rows);
})->with([
    'the typed half of a scalar pair' => [Shortcut::class, ['host' => 'example.org', 'path' => 'home'], fn (): TextInputColumn => TextInputColumn::make('path'), 'index', fn (Shortcut $record): string => $record->getPath(), 'home', 'SELECT host, path FROM Shortcut ORDER BY host, path', [['host' => 'example.org', 'path' => 'about'], ['host' => 'example.org', 'path' => 'home'], ['host' => 'sylament.test', 'path' => 'home']]],
    'the typed half of a scalar pair, onto a taken key' => [Shortcut::class, ['host' => 'example.org', 'path' => 'home'], fn (): TextInputColumn => TextInputColumn::make('path'), 'about', fn (Shortcut $record): string => $record->getPath(), 'home', 'SELECT host, path FROM Shortcut ORDER BY host, path', [['host' => 'example.org', 'path' => 'about'], ['host' => 'example.org', 'path' => 'home'], ['host' => 'sylament.test', 'path' => 'home']]],
    'the picked half of a scalar pair' => [Shortcut::class, ['host' => 'example.org', 'path' => 'home'], fn (): SelectColumn => SelectColumn::make('host')->options(['example.org' => 'example.org', 'moved.test' => 'moved.test']), 'moved.test', fn (Shortcut $record): string => $record->getHost(), 'example.org', 'SELECT host, path FROM Shortcut ORDER BY host, path', [['host' => 'example.org', 'path' => 'about'], ['host' => 'example.org', 'path' => 'home'], ['host' => 'sylament.test', 'path' => 'home']]],
    'the scalar half of a derived identity' => [Residency::class, ['season' => 'summer'], fn (): TextInputColumn => TextInputColumn::make('season'), 'autumn', fn (Residency $record): string => $record->getSeason(), 'summer', 'SELECT venue_id, season FROM Residency', [['venue_id' => 'north.hall', 'season' => 'summer']]],
    'a single assigned id' => [Song::class, ['id' => 1], fn (): TextInputColumn => TextInputColumn::make('id'), '9', fn (Song $record): int => $record->getId(), 1, 'SELECT id, title FROM Song ORDER BY id', [['id' => 1, 'title' => 'Airbag'], ['id' => 2, 'title' => 'Paranoid Android']]],
    'a single assigned id, onto a taken one' => [Song::class, ['id' => 1], fn (): TextInputColumn => TextInputColumn::make('id'), '2', fn (Song $record): int => $record->getId(), 1, 'SELECT id, title FROM Song ORDER BY id', [['id' => 1, 'title' => 'Airbag'], ['id' => 2, 'title' => 'Paranoid Android']]],
]);

it('writes an integer id back as the string a cell posts without refusing it', function (): void {
    [$entityManager, $recorder, $table] = identifierWriteRig(Song::class);
    $song = $entityManager->find(Song::class, 1) ?? throw new LogicException('The seeded song went missing.');
    $recorder->reset();

    $returned = TextInputColumn::make('id')->table($table)->record($song)->updateState('1');

    expect($returned)->toBe('1')
        ->and($song->getId())->toBe(1)
        ->and($recorder->statements())->toBe([]);
});

it('files a refused identifier write against the cell, echoing the posted input', function (): void {
    [$entityManager] = identifierWriteRig(Shortcut::class);

    $host = new TableHost(fn (Table $table): Table => $table
        ->query($entityManager->createQueryBuilder()->select('shortcut')->from(Shortcut::class, 'shortcut'))
        ->columns([TextInputColumn::make('path')]));

    $column = $host->getTable()->getColumn('path');

    assert($column instanceof TextInputColumn);
    $column->setValidator(Validation::createValidator());

    $host->updateTableColumnState('path', 'example=2Eorg~home', 'index');

    expect($host->getTableColumnStateError('path', 'example=2Eorg~home'))->toBe(['message' => 'This value cannot be changed.', 'input' => 'index'])
        ->and($entityManager->getConnection()->fetchAllAssociative('SELECT host, path FROM Shortcut ORDER BY host, path'))->toBe([['host' => 'example.org', 'path' => 'about'], ['host' => 'example.org', 'path' => 'home'], ['host' => 'sylament.test', 'path' => 'home']]);
});
