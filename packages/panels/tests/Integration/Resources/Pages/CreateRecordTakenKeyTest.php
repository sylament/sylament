<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Pest\Expectation;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Catalogue;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Shelfmark;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Stamp;
use Sylament\Panels\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\CreateArticle;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\CreateHintedStamp;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\CreatePickedStamp;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\CreateShelfmark;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\CreateStamp;
use Sylament\Testing\Doctrine\RecordingMiddleware;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * A create page on a key a stored row already holds. Every case runs through
 * the live client with exceptions caught, so what it reads is the response a
 * browser gets; the entity manager is cleared before the create where the
 * identity map must be the cold one a fresh request builds, and read back into
 * it where the holder must already be managed.
 *
 * Catalogue "First" is `1` and "Second" `2`. Under `1` sit the stamps whose
 * codes read `A/B C` and `EN-GB` — stored `a/b c` and `en-gb`, since the column
 * holds the code in lower case — and under `2` the stamp `DUP`. Every key, URL
 * and statement below is a literal.
 *
 * Kernel reboot is disabled because the fixture database lives in memory on a
 * container service.
 */

/**
 * @return array{KernelBrowser, EntityManagerInterface, RecordingMiddleware}
 */
function createTakenKeyClient(string $panel): array
{
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);

    $first = new Catalogue()->setTitle('First');
    $second = new Catalogue()->setTitle('Second');

    EntityManagerFactory::seed(
        $entityManager,
        $first,
        $second,
        new Stamp()->setCatalogue($first)->setCode('A/B C')->setCaption('Alpha'),
        new Stamp()->setCatalogue($first)->setCode('en-gb')->setCaption('Beta'),
        new Stamp()->setCatalogue($second)->setCode('DUP')->setCaption('Elsewhere'),
        new Shelfmark()->setCode('alpha')->setLabel('A'),
    );

    // A page is mounted in-process before its first live request, so the
    // panel it belongs to — and the session a success notification flashes
    // into — stand on the stack the way a routed request would carry them.
    $request = new Request();
    $request->attributes->set('_sylament_panel', $panel);
    $request->setSession(test()->getContainer()->get('session.factory')->createSession());

    test()->getContainer()->get(RequestStack::class)->push($request);

    $recorder = test()->getContainer()->get(RecordingMiddleware::class);
    assert($recorder instanceof RecordingMiddleware);

    return [$client, $entityManager, $recorder];
}

/**
 * Every field error on the page, keyed by the live model of the input its
 * field wrapper holds.
 *
 * @return array<string, list<string>>
 */
function createTakenKeyFieldErrors(string $html): array
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
 * @return list<string>
 */
function createTakenKeyRecordErrors(string $html): array
{
    return new Crawler($html)->filter('ul.sy-pa-form-errors[data-record-errors] li')->each(static fn (Crawler $item): string => mb_trim($item->text()));
}

/**
 * @return list<string>
 */
function createTakenKeyWrites(RecordingMiddleware $recorder): array
{
    return array_values(array_filter($recorder->statements(), static fn (string $sql): bool => ! str_starts_with($sql, 'SELECT')));
}

it('refuses a single assigned key a row already holds under its field, writing nothing', function (bool $managed): void {
    [$client, $entityManager, $recorder] = createTakenKeyClient('write');

    $component = $this->createLiveComponent(CreateShelfmark::class, ['panelId' => 'write'], client: $client);
    $component->set('data', ['code' => 'alpha', 'label' => 'Duplicate']);

    $entityManager->clear();

    if ($managed) {
        $entityManager->find(Shelfmark::class, 'alpha');
    }

    $recorder->reset();
    $client->catchExceptions(true);

    $component->call('create');

    $html = (string) $client->getResponse()->getContent();

    expect($client->getResponse()->getStatusCode())->toBe(422)
        ->and(createTakenKeyFieldErrors($html))->toBe(['data.code' => ['This value is already used.']])
        ->and(mb_substr_count($html, 'This value is already used.'))->toBe(1)
        // A managed holder answers from the identity map; a stored one costs the
        // one primary-key read.
        ->and($recorder->normalizedStatements())->toHaveCount($managed ? 0 : 1)
        ->each->toContain('FROM Shelfmark p0 WHERE p0.code = ?')
        ->and($entityManager->isOpen())->toBeTrue()
        ->and($entityManager->getRepository(Shelfmark::class)->count([]))->toBe(1);
})->with([
    'the holder stored' => [false],
    'the holder managed' => [true],
]);

it('refuses a nested key its parent completes under the code, whatever spelling of a stored code the form types', function (bool $managed, string $code): void {
    [$client, $entityManager, $recorder] = createTakenKeyClient('key');

    $component = $this->createLiveComponent(CreateStamp::class, [
        'parentRecords' => ['catalogue' => '1'],
        'panelId' => 'key',
    ], client: $client);
    $component->set('data', ['code' => $code, 'caption' => 'Duplicate']);

    $entityManager->clear();

    if ($managed) {
        $entityManager->find(Stamp::class, ['catalogue' => 1, 'code' => 'A/B C']);
        $entityManager->find(Stamp::class, ['catalogue' => 1, 'code' => 'EN-GB']);
    }

    $recorder->reset();
    $client->catchExceptions(true);

    $component->call('create');

    $html = (string) $client->getResponse()->getContent();

    // The parent fills half the key before the record check runs, so the
    // message lands on the half the form lets the user type rather than on
    // the page's record-error list.
    expect($client->getResponse()->getStatusCode())->toBe(422)
        ->and(createTakenKeyFieldErrors($html))->toBe(['data.code' => ['This value is already used.']])
        ->and(createTakenKeyRecordErrors($html))->toBe([])
        ->and(createTakenKeyWrites($recorder))->toBe([])
        ->and($entityManager->isOpen())->toBeTrue()
        ->and($entityManager->getRepository(Stamp::class)->count([]))->toBe(3);
})->with([
    'the holder stored' => [false],
    'the holder managed' => [true],
])->with([
    'the code as the record reads it' => ['A/B C'],
    'the code as the column stores it' => ['en-gb'],
    'the stored code read back' => ['EN-GB'],
]);

it('reads the key once before an assigned-key insert and never before a generated one', function (string $page, string $panel, array $parameters, array $data, array $lookups, array $writes): void {
    [$client, $entityManager, $recorder] = createTakenKeyClient($panel);

    $component = $this->createLiveComponent($page, $parameters, client: $client);
    $component->set('data', $data);

    $entityManager->clear();
    $recorder->reset();

    $component->call('create');

    expect($component->response()->isRedirection())->toBeTrue()
        ->and($recorder->normalizedStatements())->toHaveCount(count($lookups) + count($writes))
        ->and(array_slice($recorder->normalizedStatements(), 0, count($lookups)))->each(static fn (Expectation $statement, int $index): Expectation => $statement->toContain($lookups[$index]))
        ->and(array_slice($recorder->normalizedStatements(), count($lookups)))->toBe($writes);
})->with([
    'a generated identifier' => [CreateArticle::class, 'admin', [], ['title' => 'Generated', 'slug' => 'generated'], [], [
        'BEGIN',
        'INSERT INTO Article (title, slug, status, author_id) VALUES (?, ?, ?, ?)',
        'COMMIT',
    ]],
    'a single assigned identifier' => [CreateShelfmark::class, 'write', ['panelId' => 'write'], ['code' => 'gamma', 'label' => 'G'], [
        'FROM Shelfmark p0 WHERE p0.code = ?',
    ], [
        'BEGIN',
        'INSERT INTO Shelfmark (code, label) VALUES (?, ?)',
        'COMMIT',
    ]],
    'an identifier its parent completes' => [CreateStamp::class, 'key', ['parentRecords' => ['catalogue' => '1'], 'panelId' => 'key'], ['code' => 'NEW/1', 'caption' => 'N'], [
        'FROM Catalogue a0_ WHERE a0_.id = ?',
        'FROM Stamp p0 WHERE p0.catalogue_id = ? AND p0.code = ?',
    ], [
        'BEGIN',
        'INSERT INTO Stamp (code, caption, catalogue_id) VALUES (?, ?, ?)',
        'COMMIT',
    ]],
]);

it('redirects a create whose code its type rewrites to the key the row reads back', function (): void {
    [$client, $entityManager] = createTakenKeyClient('key');

    $component = $this->createLiveComponent(CreateStamp::class, [
        'parentRecords' => ['catalogue' => '1'],
        'panelId' => 'key',
    ], client: $client);

    $component->set('data', ['code' => 'new/1', 'caption' => 'Lower'])->call('create');

    $location = (string) $component->response()->headers->get('Location');

    expect($location)->toBe('http://localhost/key/catalogues/1/stamps/1~NEW=2F1/edit')
        ->and($entityManager->getConnection()->fetchOne("SELECT code FROM Stamp WHERE caption = 'Lower'"))->toBe('new/1');

    $client->catchExceptions(true);
    $client->request('GET', $location);

    expect($client->getResponse()->getStatusCode())->toBe(200);
});

it('constructs the record of a create with no parent once', function (): void {
    [$client] = createTakenKeyClient('write');

    $component = $this->createLiveComponent(CreateShelfmark::class, ['panelId' => 'write'], client: $client);
    $component->set('data', ['code' => 'gamma', 'label' => 'G']);

    Shelfmark::$constructions = 0;

    $component->call('create');

    expect($component->response()->isRedirection())->toBeTrue()
        ->and(Shelfmark::$constructions)->toBe(1);
});

it('re-renders a refused nested create with the form bound to its record class again', function (): void {
    [$client] = createTakenKeyClient('key');

    $component = $this->createLiveComponent(CreateHintedStamp::class, [
        'parentRecords' => ['catalogue' => '1'],
        'panelId' => 'key',
    ], client: $client);

    expect(new Crawler((string) $component->render())->filter('input[data-model$="data.classHint"]')->count())->toBe(1);

    $component->set('data', ['code' => 'A/B C', 'caption' => 'Duplicate']);

    $client->catchExceptions(true);

    $component->call('create');

    $html = (string) $client->getResponse()->getContent();

    // The hint shows only while the form is bound to no record and the class
    // hint only while it is bound to the record class, so both are still there
    // only if the binding the mapping replaced was put back as it was.
    expect($client->getResponse()->getStatusCode())->toBe(422)
        ->and(createTakenKeyFieldErrors($html))->toBe(['data.code' => ['This value is already used.']])
        ->and(new Crawler($html)->filter('input[data-model$="data.hint"]')->count())->toBe(1)
        ->and(new Crawler($html)->filter('input[data-model$="data.classHint"]')->count())->toBe(1);
});

it("characterizes: a nested create whose form picks another parent still lands under the page's parent", function (): void {
    [$client, $entityManager] = createTakenKeyClient('key');

    $this->createLiveComponent(CreatePickedStamp::class, [
        'parentRecords' => ['catalogue' => '1'],
        'panelId' => 'key',
    ], client: $client)
        ->set('data', ['catalogue' => '2', 'code' => 'PICK', 'caption' => 'Picked'])
        ->call('create');

    $entityManager->clear();

    $stamp = $entityManager->getRepository(Stamp::class)->findOneBy(['caption' => 'Picked']);

    expect($stamp?->getCatalogue()?->getId())->toBe(1);
});

it('characterizes: a nested create whose form picks another parent is probed on the pick', function (): void {
    [$client, $entityManager, $recorder] = createTakenKeyClient('key');

    $component = $this->createLiveComponent(CreatePickedStamp::class, [
        'parentRecords' => ['catalogue' => '1'],
        'panelId' => 'key',
    ], client: $client);
    $component->set('data', ['catalogue' => '2', 'code' => 'DUP', 'caption' => 'Picked']);

    $recorder->reset();
    $client->catchExceptions(true);

    $component->call('create');

    // `DUP` is free under the page's parent and taken under the pick, and the
    // record check runs while the form's pick still stands.
    expect($client->getResponse()->getStatusCode())->toBe(422)
        ->and(createTakenKeyFieldErrors((string) $client->getResponse()->getContent()))->toBe(['data.code' => ['This value is already used.']])
        ->and(createTakenKeyWrites($recorder))->toBe([])
        ->and($entityManager->getRepository(Stamp::class)->count([]))->toBe(3);
});
