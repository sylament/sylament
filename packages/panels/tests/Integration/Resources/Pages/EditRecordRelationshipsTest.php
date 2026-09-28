<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Portfolio;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\PortfolioClipping;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\PortfolioSeal;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\PortfolioSheet;
use Sylament\Panels\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditPortfolio;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditPortfolioResealing;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditPortfolioSealPaths;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditPortfolioSerialFirst;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditPortfolioStamped;
use Sylament\Schemas\Exceptions\ValidationException;
use Sylament\Testing\Doctrine\RecordingMiddleware;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;
use Symfony\UX\LiveComponent\Test\TestLiveComponent;

uses(InteractsWithLiveComponents::class);

/*
 * An edit page saving a record together with what it owns: two relationship
 * repeaters, a singular relationship group, dotted paths onto the same related
 * record, and a save hook that reaches past the form.
 *
 * Portfolio `1` owns the sheet `a` (item key `record-1~a`), the clipping `1`
 * (`record-1`) and the seal `P1`; portfolio `2` owns nothing. Every save
 * clears the entity manager first, as a separate browser request would find
 * it, and every expected key, row and statement is a literal. Kernel reboot is
 * disabled because the fixture database lives in memory on a container
 * service.
 */

/**
 * @return array{KernelBrowser, EntityManagerInterface, RecordingMiddleware}
 */
function portfolioSaveClient(): array
{
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);

    $portfolio = new Portfolio()->setTitle('Folio')->setSeal(new PortfolioSeal()->setSerial('P1')->setTitle('Seal'));
    $portfolio->addSheet(new PortfolioSheet()->setCode('a')->setCaption('A'));
    $portfolio->addClipping(new PortfolioClipping()->setText('First'));

    EntityManagerFactory::seed($entityManager, $portfolio, new Portfolio()->setTitle('Bare'));

    $recorder = test()->getContainer()->get(RecordingMiddleware::class);
    assert($recorder instanceof RecordingMiddleware);

    return [$client, $entityManager, $recorder];
}

/**
 * One save as its own request: the identity map emptied, the recorder reset,
 * exceptions caught into the response.
 *
 * @return array{int, string, list<string>}
 */
function portfolioSave(TestLiveComponent $component, KernelBrowser $client, EntityManagerInterface $entityManager, RecordingMiddleware $recorder): array
{
    $entityManager->clear();
    $recorder->reset();
    $client->catchExceptions(true);

    $component->call('save');

    return [
        $client->getResponse()->getStatusCode(),
        (string) $client->getResponse()->getContent(),
        array_values(array_filter($recorder->statements(), static fn (string $sql): bool => ! str_starts_with($sql, 'SELECT'))),
    ];
}

/**
 * @return array<string, mixed>
 */
function portfolioSaveProps(string $html): array
{
    preg_match('/data-live-props-value="([^"]*)"/', $html, $matches);

    $props = json_decode(html_entity_decode($matches[1] ?? ''), true, flags: JSON_THROW_ON_ERROR);

    expect($props)->toBeArray();

    return $props;
}

/**
 * The repeater's item keys as the page carries them on the wire.
 *
 * @return list<int|string>
 */
function portfolioSaveItemKeys(string $html, string $repeater): array
{
    $data = portfolioSaveProps($html)['data'] ?? [];
    $items = is_array($data) ? ($data[$repeater] ?? []) : [];

    return is_array($items) ? array_keys($items) : [];
}

/**
 * @return array<string, list<string>>
 */
function portfolioSaveFieldErrors(string $html): array
{
    $errors = [];

    new Crawler($html)->filter('li.sy-fo-field-error-message')->each(function (Crawler $message) use (&$errors): void {
        $wrapper = $message->ancestors()->filter('[data-field-wrapper]')->first();
        $model = explode('|', $wrapper->filter('[data-model]')->first()->attr('data-model') ?? '');

        $errors[end($model)][] = mb_trim($message->text());
    });

    return $errors;
}

/**
 * @return list<array<string, mixed>>
 */
function portfolioSaveRows(EntityManagerInterface $entityManager, string $sql): array
{
    return $entityManager->getConnection()->fetchAllAssociative($sql);
}

it('writes nothing when a page saves again the item its last save created', function (): void {
    [$client, $entityManager, $recorder] = portfolioSaveClient();

    $component = $this->createLiveComponent(EditPortfolio::class, ['record' => '1', 'panelId' => 'write'], client: $client);
    $component->set('data.sheets', [
        'record-1~a' => ['code' => 'a', 'caption' => 'A'],
        'added' => ['code' => 'b', 'caption' => 'B'],
    ]);

    [$status, $html, $writes] = portfolioSave($component, $client, $entityManager, $recorder);

    // The save reloads what it just wrote, so the new item is keyed by the
    // identifier its flush gave it rather than by the client's placeholder —
    // already by the time `afterSave` runs.
    expect($status)->toBe(200)
        ->and($writes)->toBe(['BEGIN', 'INSERT INTO PortfolioSheet (code, caption, portfolio_id) VALUES (?, ?, ?)', 'COMMIT'])
        ->and(portfolioSaveItemKeys($html, 'sheets'))->toBe(['record-1~a', 'record-1~b'])
        ->and(portfolioSaveProps($html)['sheetKeysAfterSave'])->toBe(['record-1~a', 'record-1~b']);

    [$status, $html, $writes] = portfolioSave($component, $client, $entityManager, $recorder);

    expect($status)->toBe(200)
        ->and($writes)->toBe([])
        ->and(portfolioSaveItemKeys($html, 'sheets'))->toBe(['record-1~a', 'record-1~b'])
        ->and(portfolioSaveRows($entityManager, 'SELECT portfolio_id, code, caption FROM PortfolioSheet ORDER BY code'))->toBe([
            ['portfolio_id' => 1, 'code' => 'a', 'caption' => 'A'],
            ['portfolio_id' => 1, 'code' => 'b', 'caption' => 'B'],
        ]);
});

it('keeps the id the database gave a new item across later saves', function (): void {
    [$client, $entityManager, $recorder] = portfolioSaveClient();

    $component = $this->createLiveComponent(EditPortfolio::class, ['record' => '1', 'panelId' => 'write'], client: $client);
    $component->set('data.clippings', [
        'record-1' => ['text' => 'First'],
        'added' => ['text' => 'Second'],
    ]);

    [$status, $html, $writes] = portfolioSave($component, $client, $entityManager, $recorder);

    expect($status)->toBe(200)
        ->and($writes)->toBe(['BEGIN', 'INSERT INTO PortfolioClipping (text, portfolio_id) VALUES (?, ?)', 'COMMIT'])
        ->and(portfolioSaveItemKeys($html, 'clippings'))->toBe(['record-1', 'record-2']);

    foreach (['second', 'third'] as $save) {
        [$status, $html, $writes] = portfolioSave($component, $client, $entityManager, $recorder);

        expect($status)->toBe(200, $save)
            ->and($writes)->toBe([], $save)
            ->and(portfolioSaveItemKeys($html, 'clippings'))->toBe(['record-1', 'record-2'], $save);
    }

    expect(portfolioSaveRows($entityManager, 'SELECT id, text FROM PortfolioClipping ORDER BY id'))->toBe([
        ['id' => 1, 'text' => 'First'],
        ['id' => 2, 'text' => 'Second'],
    ]);
});

it('saves an item a refused save carried once the refusal is corrected', function (): void {
    [$client, $entityManager, $recorder] = portfolioSaveClient();

    $component = $this->createLiveComponent(EditPortfolio::class, ['record' => '1', 'panelId' => 'write'], client: $client);
    $component->set('data.title', 'refuse')->set('data.sheets', [
        'record-1~a' => ['code' => 'a', 'caption' => 'A'],
        'added' => ['code' => 'b', 'caption' => 'B'],
    ]);

    [$status, $html, $writes] = portfolioSave($component, $client, $entityManager, $recorder);

    // Nothing was written, so nothing is reloaded: the new item keeps the key
    // the client gave it.
    expect($status)->toBe(422)
        ->and(portfolioSaveFieldErrors($html))->toBe(['data.title' => ['This portfolio title is refused.']])
        ->and($writes)->toBe([])
        ->and(portfolioSaveItemKeys($html, 'sheets'))->toBe(['record-1~a', 'added']);

    $component->set('data.title', 'Corrected');

    [$status, $html, $writes] = portfolioSave($component, $client, $entityManager, $recorder);

    expect($status)->toBe(200)
        ->and($writes)->toBe([
            'BEGIN',
            'INSERT INTO PortfolioSheet (code, caption, portfolio_id) VALUES (?, ?, ?)',
            'UPDATE Portfolio SET title = ? WHERE id = ?',
            'COMMIT',
        ])
        ->and(portfolioSaveItemKeys($html, 'sheets'))->toBe(['record-1~a', 'record-1~b']);
});

it('renders a violation inside a singular relationship under its field', function (string $record, array $seal, string $path, string $message): void {
    [$client, $entityManager, $recorder] = portfolioSaveClient();

    $component = $this->createLiveComponent(EditPortfolio::class, ['record' => $record, 'panelId' => 'write'], client: $client);
    $component->set('data.seal', $seal);

    [$status, $html, $writes] = portfolioSave($component, $client, $entityManager, $recorder);

    expect($status)->toBe(422)
        ->and(portfolioSaveFieldErrors($html))->toBe([$path => [$message]])
        ->and(mb_substr_count($html, $message))->toBe(1)
        ->and($writes)->toBe([]);
})->with([
    'a check the related record runs on its field' => ['1', ['title' => 'refuse', 'serial' => 'P1'], 'data.seal.title', 'This seal title is refused.'],
    'a constraint on the related field' => ['1', ['title' => 'too long a title', 'serial' => 'P1'], 'data.seal.title', 'This seal title is too long.'],
    'a new related record on a taken key' => ['2', ['title' => 'New', 'serial' => 'P1'], 'data.seal.serial', 'This value is already used.'],
    'the related record re-keyed' => ['1', ['title' => 'Seal', 'serial' => 'P2'], 'data.seal.serial', 'This value cannot be changed.'],
]);

it("characterizes: a violation on a singular relationship's record as a whole renders nowhere", function (): void {
    [$client, $entityManager, $recorder] = portfolioSaveClient();

    $component = $this->createLiveComponent(EditPortfolio::class, ['record' => '1', 'panelId' => 'write'], client: $client);
    $component->set('data.seal', ['title' => 'whole', 'serial' => 'P1']);

    $client->catchExceptions(false);
    $entityManager->clear();

    try {
        $component->call('save');
        $errors = null;
    } catch (ValidationException $validationException) {
        $errors = $validationException->getErrors();
    }

    // It folds onto the group's own path, and a group renders no error of its
    // own path.
    expect($errors)->toBe(['data.seal' => ['This seal is refused as a whole.']]);

    [$status, $html, $writes] = portfolioSave($component, $client, $entityManager, $recorder);

    expect($status)->toBe(422)
        ->and(mb_substr_count($html, 'This seal is refused as a whole.'))->toBe(0)
        ->and($writes)->toBe([]);
});

it('refuses a dotted path that re-keys a loaded related record under its field', function (): void {
    [$client, $entityManager, $recorder] = portfolioSaveClient();

    $component = $this->createLiveComponent(EditPortfolioSealPaths::class, ['record' => '1', 'panelId' => 'write'], client: $client);
    $component->set('data.seal', ['title' => 'Seal', 'serial' => 'P2']);

    [$status, $html, $writes] = portfolioSave($component, $client, $entityManager, $recorder);

    expect($status)->toBe(422)
        ->and(portfolioSaveFieldErrors($html))->toBe(['data.seal.serial' => ['This value cannot be changed.']])
        ->and($writes)->toBe([])
        ->and(portfolioSaveRows($entityManager, 'SELECT id, seal_serial FROM Portfolio ORDER BY id'))->toBe([
            ['id' => 1, 'seal_serial' => 'P1'],
            ['id' => 2, 'seal_serial' => null],
        ]);
});

it('writes through dotted paths onto a related record that keep or construct its key', function (string $record, array $seal, array $statements): void {
    [$client, $entityManager, $recorder] = portfolioSaveClient();

    $component = $this->createLiveComponent(EditPortfolioSealPaths::class, ['record' => $record, 'panelId' => 'write'], client: $client);
    $component->set('data.seal', $seal);

    [$status, , $writes] = portfolioSave($component, $client, $entityManager, $recorder);

    expect($status)->toBe(200)
        ->and($writes)->toBe($statements);
})->with([
    'the key kept, the title edited' => ['1', ['title' => 'Renamed', 'serial' => 'P1'], [
        'BEGIN',
        'UPDATE PortfolioSeal SET title = ? WHERE serial = ?',
        'COMMIT',
    ]],
    'a new related record on a free key' => ['2', ['title' => 'New', 'serial' => 'P9'], [
        'BEGIN',
        'INSERT INTO PortfolioSeal (serial, title) VALUES (?, ?)',
        'UPDATE Portfolio SET seal_serial = ? WHERE id = ?',
        'COMMIT',
    ]],
]);

it('refuses a dotted path that constructs a related record on a taken key under its field', function (): void {
    [$client, $entityManager, $recorder] = portfolioSaveClient();

    $component = $this->createLiveComponent(EditPortfolioSealPaths::class, ['record' => '2', 'panelId' => 'write'], client: $client);
    $component->set('data.seal', ['title' => 'New', 'serial' => 'P1']);

    [$status, $html, $writes] = portfolioSave($component, $client, $entityManager, $recorder);

    expect($status)->toBe(422)
        ->and(portfolioSaveFieldErrors($html))->toBe(['data.seal.serial' => ['This value is already used.']])
        ->and($writes)->toBe([])
        ->and($entityManager->isOpen())->toBeTrue();
});

it('characterizes: a key written onto a related record not yet loaded is dropped', function (): void {
    [$client, $entityManager, $recorder] = portfolioSaveClient();

    $component = $this->createLiveComponent(EditPortfolioSerialFirst::class, ['record' => '1', 'panelId' => 'write'], client: $client);
    $component->set('data.seal', ['serial' => 'P2', 'title' => 'Seal']);

    [$status, , $writes] = portfolioSave($component, $client, $entityManager, $recorder);

    // The serial reaches an unloaded reference, which the title's write then
    // loads over it: Doctrine's own drop, which no walk of loaded records sees.
    expect($status)->toBe(200)
        ->and($writes)->toBe([])
        ->and(portfolioSaveRows($entityManager, 'SELECT serial, title FROM PortfolioSeal'))->toBe([['serial' => 'P1', 'title' => 'Seal']]);
});

it("refuses a hook that re-keys a related record the form never maps on the page's record-error list", function (): void {
    [$client, $entityManager, $recorder] = portfolioSaveClient();

    $component = $this->createLiveComponent(EditPortfolioResealing::class, ['record' => '1', 'panelId' => 'write'], client: $client);
    $component->set('data.title', 'Retitled');

    [$status, $html, $writes] = portfolioSave($component, $client, $entityManager, $recorder);

    expect($status)->toBe(422)
        ->and(new Crawler($html)->filter('ul.sy-pa-form-errors[data-record-errors] li')->each(static fn (Crawler $item): string => mb_trim($item->text())))
        ->toBe(['This value cannot be changed.'])
        ->and(portfolioSaveFieldErrors($html))->toBe([])
        ->and($writes)->toBe([])
        ->and(portfolioSaveRows($entityManager, 'SELECT serial, title FROM PortfolioSeal'))->toBe([['serial' => 'P1', 'title' => 'Seal']]);
});

it('characterizes: a save hands a relationship item and a related record back as the record gives them, a UI-only field inside each null, as in Filament', function (): void {
    [$client, $entityManager, $recorder] = portfolioSaveClient();

    $component = $this->createLiveComponent(EditPortfolioStamped::class, ['record' => '1', 'panelId' => 'write'], client: $client);
    $component->set('data.clippings.record-1.text', 'Edited');
    $component->set('data.clippings.record-1.scratch', 'typed, not saved');
    $component->set('data.seal.scratch', 'typed, not saved');

    [$status, $html, $writes] = portfolioSave($component, $client, $entityManager, $recorder);

    $data = portfolioSaveProps($html)['data'] ?? [];

    // The reload reads each relationship back off the record, and neither
    // record holds the UI-only field: what was typed there is gone.
    expect($status)->toBe(200)
        ->and($writes)->toBe(['BEGIN', 'UPDATE PortfolioClipping SET text = ? WHERE id = ?', 'COMMIT'])
        ->and($data)->toBeArray()
        ->and($data['clippings'] ?? null)->toBe(['record-1' => ['text' => 'Edited', 'scratch' => null]])
        ->and($data['seal'] ?? null)->toBe(['title' => 'Seal', 'serial' => 'P1', 'scratch' => null]);
});
