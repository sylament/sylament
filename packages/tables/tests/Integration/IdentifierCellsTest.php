<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Support\Tests\Doctrine\RecordingMiddleware;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Artist;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Residency;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Shortcut;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Song;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Venue;
use Sylament\Tables\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Tables\Tests\Fixtures\Doctrine\Handle;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;
use Symfony\UX\LiveComponent\Test\TestLiveComponent;

uses(InteractsWithLiveComponents::class);

/*
 * An editable cell over an identifier field, through the host's live action:
 * a changed key is refused as the cell's own error rather than written, over
 * every flavour a cell can reach — either half of the scalar pair `Shortcut`,
 * the scalar half of the derived `Residency`, and the single assigned id of
 * `Song`. The refusal puts the record's key back before anything flushes, so
 * the re-render still keys the row by the key the browser posted and the error
 * lands under the cell it belongs to; a taken key meets the same refusal
 * rather than the database's unique violation, and the entity manager stays
 * open. Keys, rows and SQL are written as literals.
 *
 * Kernel reboot is disabled for `CompositeKeyTableTest`'s reason: the fixture
 * database is an in-memory SQLite held by a container service.
 */

/**
 * @return array{TestLiveComponent, EntityManagerInterface, RecordingMiddleware}
 */
function identifierCellsRig(string $over): array
{
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);
    $records = [];

    foreach ([['example.org', 'home', 'Home'], ['example.org', 'about', 'About'], ['sylament.test', 'home', 'Other']] as $position => [$host, $path, $label]) {
        $shortcut = new Shortcut();
        $shortcut->setHost($host);
        $shortcut->setPath($path);
        $shortcut->setLabel($label);
        $shortcut->setPosition($position);
        $shortcut->setActive(true);
        $records[] = $shortcut;
    }

    $north = new Venue(new Handle('north.hall'), 'North Hall');
    $astra = new Venue(new Handle('astra-hall'), 'Astra Hall');
    $residency = new Residency();
    $residency->setVenue($north);
    $residency->setSeason('summer');
    $residency->setLabel('Summer');
    $residency->setPosition(1);
    $residency->setActive(true);

    $radiohead = new Artist(1, 'Radiohead');

    EntityManagerFactory::seed($entityManager, ...[...$records, $north, $astra, $residency, $radiohead, new Song(1, 'Airbag', 100, $radiohead), new Song(2, 'Paranoid Android', 200, $radiohead)]);

    $component = test()->createLiveComponent('IdentifierCellsTable', ['over' => $over], client: $client);
    $recorder = test()->getContainer()->get(RecordingMiddleware::class);
    $recorder->reset();

    return [$component, $entityManager, $recorder];
}

/**
 * @return list<string>
 */
function identifierCellsWrites(RecordingMiddleware $recorder): array
{
    return array_values(array_filter($recorder->statements(), static fn (string $sql): bool => !str_starts_with($sql, 'SELECT')));
}

/**
 * @return list<string>
 */
function identifierCellsRowKeys(Crawler $crawler): array
{
    return $crawler->filter('tr.sy-ta-row')->each(static fn (Crawler $row): string => (string) preg_replace('/^.*\.table\.records\./', '', (string) $row->attr('id')));
}

function identifierCellsCell(Crawler $crawler, string $column, string $record): Crawler
{
    return $crawler->filter(sprintf('[data-sylament--tables--editable-cell-column-value="%s"][data-sylament--tables--editable-cell-record-value="%s"]', $column, $record));
}

/**
 * @return list<array<string, mixed>>
 */
function identifierCellsStoredRows(EntityManagerInterface $entityManager, string $sql): array
{
    return array_values($entityManager->getConnection()->fetchAllAssociative($sql));
}

it('refuses a changed key as an error on its own cell, keeps the row under its posted key and writes nothing', function (string $over, string $column, string $record, string $input, array $rowKeys, string $sql, array $rows): void {
    [$component, $entityManager, $recorder] = identifierCellsRig($over);

    $crawler = new Crawler((string) $component
        ->call('updateTableColumnState', ['column' => $column, 'record' => $record, 'input' => $input])
        ->render());

    $cell = identifierCellsCell($crawler, $column, $record);

    expect($crawler->filter('p.sy-ta-cell-error')->each(static fn (Crawler $error): string => $error->text()))->toBe(['This value cannot be changed.'])
        ->and($cell->filter('p.sy-ta-cell-error')->count())->toBe(1)
        ->and($cell->attr('data-sylament--tables--editable-cell-state-value'))->toBe($input)
        ->and(identifierCellsRowKeys($crawler))->toBe($rowKeys)
        ->and(identifierCellsWrites($recorder))->toBe([])
        ->and($entityManager->isOpen())->toBeTrue()
        ->and(identifierCellsStoredRows($entityManager, $sql))->toBe($rows);
})->with([
    'the typed half of a scalar pair' => ['shortcut', 'path', 'example=2Eorg~home', 'index', ['example=2Eorg~about', 'example=2Eorg~home', 'sylament=2Etest~home'], 'SELECT host, path FROM Shortcut ORDER BY host, path', [['host' => 'example.org', 'path' => 'about'], ['host' => 'example.org', 'path' => 'home'], ['host' => 'sylament.test', 'path' => 'home']]],
    'the typed half of a scalar pair, onto a taken key' => ['shortcut', 'path', 'example=2Eorg~home', 'about', ['example=2Eorg~about', 'example=2Eorg~home', 'sylament=2Etest~home'], 'SELECT host, path FROM Shortcut ORDER BY host, path', [['host' => 'example.org', 'path' => 'about'], ['host' => 'example.org', 'path' => 'home'], ['host' => 'sylament.test', 'path' => 'home']]],
    'the picked half of a scalar pair' => ['shortcut', 'host', 'example=2Eorg~home', 'moved.test', ['example=2Eorg~about', 'example=2Eorg~home', 'sylament=2Etest~home'], 'SELECT host, path FROM Shortcut ORDER BY host, path', [['host' => 'example.org', 'path' => 'about'], ['host' => 'example.org', 'path' => 'home'], ['host' => 'sylament.test', 'path' => 'home']]],
    'the picked half of a scalar pair, onto a taken key' => ['shortcut', 'host', 'example=2Eorg~home', 'sylament.test', ['example=2Eorg~about', 'example=2Eorg~home', 'sylament=2Etest~home'], 'SELECT host, path FROM Shortcut ORDER BY host, path', [['host' => 'example.org', 'path' => 'about'], ['host' => 'example.org', 'path' => 'home'], ['host' => 'sylament.test', 'path' => 'home']]],
    'the scalar half of a derived identity' => ['residency', 'season', 'north=2Ehall~summer', 'autumn', ['north=2Ehall~summer'], 'SELECT venue_id, season FROM Residency', [['venue_id' => 'north.hall', 'season' => 'summer']]],
    'a single assigned id' => ['song', 'id', '1', '9', ['1', '2'], 'SELECT id, title FROM Song ORDER BY id', [['id' => 1, 'title' => 'Airbag'], ['id' => 2, 'title' => 'Paranoid Android']]],
    'a single assigned id, onto a taken one' => ['song', 'id', '1', '2', ['1', '2'], 'SELECT id, title FROM Song ORDER BY id', [['id' => 1, 'title' => 'Airbag'], ['id' => 2, 'title' => 'Paranoid Android']]],
]);

it('writes the same key back in another type without refusing it', function (string $over, string $column, string $record, string $input, array $rowKeys): void {
    [$component, $entityManager, $recorder] = identifierCellsRig($over);

    $crawler = new Crawler((string) $component
        ->call('updateTableColumnState', ['column' => $column, 'record' => $record, 'input' => $input])
        ->render());

    expect($crawler->filter('p.sy-ta-cell-error')->count())->toBe(0)
        ->and(identifierCellsRowKeys($crawler))->toBe($rowKeys)
        ->and(identifierCellsWrites($recorder))->toBe([])
        ->and($entityManager->isOpen())->toBeTrue();
})->with([
    'a scalar pair half' => ['shortcut', 'path', 'example=2Eorg~home', 'home', ['example=2Eorg~about', 'example=2Eorg~home', 'sylament=2Etest~home']],
    'a single integer id posted as a string' => ['song', 'id', '1', '1', ['1', '2']],
]);

it('keeps refusing the association half of a derived identity by its type', function (): void {
    [$component, $entityManager, $recorder] = identifierCellsRig('residency');

    $crawler = new Crawler((string) $component
        ->call('updateTableColumnState', ['column' => 'venue', 'record' => 'north=2Ehall~summer', 'input' => 'astra-hall'])
        ->render());

    expect(identifierCellsCell($crawler, 'venue', 'north=2Ehall~summer')->filter('p.sy-ta-cell-error')->text())
        ->toBe('The editable column [venue] cannot write to its record: Expected argument of type "?Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Venue", "string" given at property path "venue".')
        ->and(identifierCellsWrites($recorder))->toBe([])
        ->and(identifierCellsStoredRows($entityManager, 'SELECT venue_id, season FROM Residency'))->toBe([['venue_id' => 'north.hall', 'season' => 'summer']]);
});

it('writes a cell that is no identifier on a keyed record as before', function (string $over, string $column, string $record, string $input, array $writes, string $sql, array $rows): void {
    [$component, $entityManager, $recorder] = identifierCellsRig($over);

    $crawler = new Crawler((string) $component
        ->call('updateTableColumnState', ['column' => $column, 'record' => $record, 'input' => $input])
        ->render());

    expect($crawler->filter('p.sy-ta-cell-error')->count())->toBe(0)
        ->and(identifierCellsWrites($recorder))->toBe($writes)
        ->and(identifierCellsStoredRows($entityManager, $sql))->toBe($rows);
})->with([
    'a scalar pair' => ['shortcut', 'label', 'example=2Eorg~home', 'Start', ['BEGIN', 'UPDATE Shortcut SET label = ? WHERE host = ? AND path = ?', 'COMMIT'], "SELECT label FROM Shortcut WHERE host = 'example.org' AND path = 'home'", [['label' => 'Start']]],
    'a derived identity' => ['residency', 'label', 'north=2Ehall~summer', 'Midsummer', ['BEGIN', 'UPDATE Residency SET label = ? WHERE venue_id = ? AND season = ?', 'COMMIT'], 'SELECT label FROM Residency', [['label' => 'Midsummer']]],
    'a single assigned id' => ['song', 'title', '1', 'Bones', ['BEGIN', 'UPDATE Song SET title = ? WHERE id = ?', 'COMMIT'], 'SELECT title FROM Song WHERE id = 1', [['title' => 'Bones']]],
]);
