<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Pest\Expectation;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Catalogue;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Colophon;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Edition;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Imprint;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Stamp;
use Sylament\Panels\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditCatalogue;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditImprint;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditStamp;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\ListColophons;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\ListEditions;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\ManageCatalogueStamps;
use Sylament\Panels\Tests\Fixtures\Resources\RelationManagers\PlainStampsRelationManager;
use Sylament\Support\Tests\Doctrine\RecordingMiddleware;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * A create modal on a key a stored row already holds, on every host a panel
 * mounts one on: a record page's header, nested under a parent and flat; a
 * related-records page; a relation manager; and a resource list, over a scalar
 * pair and over a record keyed by one association alone. Where a relationship
 * supplies half the key, it is attached before the record check runs, so the
 * message lands on the half the modal lets the user type.
 *
 * Seeded: catalogues `1` and `2`; under `1` the stamps `AB-1` and `AB-2`;
 * the imprints `plain~about` and `plain~taken`; the edition `5~en`; the
 * colophon keyed by catalogue `1`. The frame is set in one request and the
 * entity manager cleared before the create, so the identity map is the one a
 * fresh action request builds — and, for a managed holder, the row is read back
 * into it first. Every key and statement is a literal. Kernel reboot is
 * disabled because the fixture database lives in memory on a container
 * service.
 */

/**
 * @return array{KernelBrowser, EntityManagerInterface, RecordingMiddleware}
 */
function compositeCreateActionClient(): array
{
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);

    $first = new Catalogue()->setTitle('First');

    EntityManagerFactory::seed(
        $entityManager,
        $first,
        new Catalogue()->setTitle('Second'),
        new Stamp()->setCatalogue($first)->setCode('AB-1')->setCaption('One'),
        new Stamp()->setCatalogue($first)->setCode('AB-2')->setCaption('Two'),
        new Imprint()->setHouse('plain')->setYear('about')->setLabel('Plain'),
        new Imprint()->setHouse('plain')->setYear('taken')->setLabel('Taken'),
        new Edition()->setId(5)->setLocale('en')->setTitle('Fifth'),
        new Colophon()->setCatalogue($first)->setNote('One'),
    );

    // Pages are mounted in-process, so the panel — and the session a success
    // notification flashes into — stand on the stack as a routed request's.
    $request = new Request();
    $request->attributes->set('_sylament_panel', 'key');
    $request->setSession(test()->getContainer()->get('session.factory')->createSession());

    test()->getContainer()->get(RequestStack::class)->push($request);

    $recorder = test()->getContainer()->get(RecordingMiddleware::class);
    assert($recorder instanceof RecordingMiddleware);

    return [$client, $entityManager, $recorder];
}

/**
 * @return array<string, list<string>>
 */
function compositeCreateActionFieldErrors(string $html): array
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
 * The live component a host's create modal opens on, its mount parameters and
 * the context the trigger sends.
 *
 * @return array{string, array<string, mixed>, array<string, mixed>}
 */
function compositeCreateActionHost(string $host): array
{
    return match ($host) {
        'nested header' => [EditStamp::class, ['record' => '1~AB-2', 'parentRecords' => ['catalogue' => '1'], 'panelId' => 'key'], []],
        'flat header' => [EditImprint::class, ['record' => 'plain~taken', 'panelId' => 'key'], []],
        'flat header on the holder' => [EditImprint::class, ['record' => 'plain~about', 'panelId' => 'key'], []],
        'related-records page' => [ManageCatalogueStamps::class, ['record' => '1', 'panelId' => 'key'], ['table' => true]],
        'relation manager' => [PlainStampsRelationManager::class, ['ownerRecord' => '1', 'pageClass' => EditCatalogue::class], ['table' => true]],
        'edition list' => [ListEditions::class, ['panelId' => 'key'], []],
        'colophon list' => [ListColophons::class, ['panelId' => 'key'], []],
    };
}

it('refuses a create modal on a taken key under the first key field it lets the user type', function (string $host, array $data, string $path, bool $managed, ?array $holder, string $entity): void {
    [$client, $entityManager, $recorder] = compositeCreateActionClient();

    [$class, $parameters, $context] = compositeCreateActionHost($host);

    $component = $this->createLiveComponent($class, $parameters, client: $client)
        ->call('mountAction', ['name' => 'create', 'arguments' => [], 'context' => $context])
        ->set('mountedActionsData.0', $data);

    $entityManager->clear();

    if ($managed && $holder !== null) {
        $entityManager->find($entity, $holder);
    }

    $rows = $entityManager->getRepository($entity)->count([]);

    $recorder->reset();
    $client->catchExceptions(true);

    $component->call('callMountedAction');

    $html = (string) $client->getResponse()->getContent();

    expect($client->getResponse()->getStatusCode())->toBe(422)
        ->and(compositeCreateActionFieldErrors($html))->toBe([$path => ['This value is already used.']])
        ->and(mb_substr_count($html, 'This value is already used.'))->toBe(1)
        ->and(array_filter($recorder->statements(), static fn (string $sql): bool => ! str_starts_with($sql, 'SELECT')))->toBe([])
        ->and($entityManager->isOpen())->toBeTrue()
        ->and($entityManager->getRepository($entity)->count([]))->toBe($rows);
})->with([
    'a nested record page header, stored' => ['nested header', ['code' => 'AB-1', 'caption' => 'Dup'], 'mountedActionsData.0.code', false, null, Stamp::class],
    'a nested record page header, managed' => ['nested header', ['code' => 'AB-1', 'caption' => 'Dup'], 'mountedActionsData.0.code', true, ['catalogue' => 1, 'code' => 'AB-1'], Stamp::class],
    'a related-records page, stored' => ['related-records page', ['code' => 'AB-1', 'caption' => 'Dup'], 'mountedActionsData.0.code', false, null, Stamp::class],
    'a related-records page, managed' => ['related-records page', ['code' => 'AB-1', 'caption' => 'Dup'], 'mountedActionsData.0.code', true, ['catalogue' => 1, 'code' => 'AB-1'], Stamp::class],
    'a relation manager, stored' => ['relation manager', ['code' => 'AB-1', 'caption' => 'Dup'], 'mountedActionsData.0.code', false, null, Stamp::class],
    'a relation manager, managed' => ['relation manager', ['code' => 'AB-1', 'caption' => 'Dup'], 'mountedActionsData.0.code', true, ['catalogue' => 1, 'code' => 'AB-1'], Stamp::class],
    'a flat record page header, stored' => ['flat header', ['house' => 'plain', 'year' => 'about', 'label' => 'Dup'], 'mountedActionsData.0.house', false, null, Imprint::class],
    'a flat record page header, managed' => ['flat header', ['house' => 'plain', 'year' => 'about', 'label' => 'Dup'], 'mountedActionsData.0.house', true, ['house' => 'plain', 'year' => 'about'], Imprint::class],
    'a flat record page header whose own record holds the key' => ['flat header on the holder', ['house' => 'plain', 'year' => 'about', 'label' => 'Dup'], 'mountedActionsData.0.house', false, null, Imprint::class],
    'a resource list over a scalar pair, stored' => ['edition list', ['id' => '5', 'locale' => 'en', 'title' => 'Dup'], 'mountedActionsData.0.id', false, null, Edition::class],
    'a resource list over a scalar pair, managed' => ['edition list', ['id' => '5', 'locale' => 'en', 'title' => 'Dup'], 'mountedActionsData.0.id', true, ['id' => 5, 'locale' => 'en'], Edition::class],
    'a resource list over a record keyed by its association, stored' => ['colophon list', ['catalogue' => '1', 'note' => 'Dup'], 'mountedActionsData.0.catalogue', false, null, Colophon::class],
    'a resource list over a record keyed by its association, managed' => ['colophon list', ['catalogue' => '1', 'note' => 'Dup'], 'mountedActionsData.0.catalogue', true, ['catalogue' => 1], Colophon::class],
]);

it('refuses the second create of create-another on the key the first one wrote', function (): void {
    [$client, $entityManager] = compositeCreateActionClient();

    $component = $this->createLiveComponent(ListEditions::class, ['panelId' => 'key'], client: $client)
        ->call('mountAction', ['name' => 'create', 'arguments' => [], 'context' => []])
        ->set('mountedActionsData.0', ['id' => '6', 'locale' => 'en', 'title' => 'One']);

    $entityManager->clear();
    $client->catchExceptions(true);

    $component->call('callMountedAction', ['arguments' => ['another' => true]]);

    expect($client->getResponse()->getStatusCode())->toBe(200);

    $component->set('mountedActionsData.0', ['id' => '6', 'locale' => 'en', 'title' => 'Two']);

    $entityManager->clear();

    $component->call('callMountedAction');

    expect($client->getResponse()->getStatusCode())->toBe(422)
        ->and(compositeCreateActionFieldErrors((string) $client->getResponse()->getContent()))->toBe(['mountedActionsData.0.id' => ['This value is already used.']])
        ->and($entityManager->find(Edition::class, ['id' => 6, 'locale' => 'en'])?->getTitle())->toBe('One');
});

it('reads a relation-scoped key once before its insert', function (string $host, array $lookups, array $writes): void {
    [$client, $entityManager, $recorder] = compositeCreateActionClient();

    [$class, $parameters, $context] = compositeCreateActionHost($host);

    $component = $this->createLiveComponent($class, $parameters, client: $client)
        ->call('mountAction', ['name' => 'create', 'arguments' => [], 'context' => $context])
        ->set('mountedActionsData.0', ['code' => 'AB-7', 'caption' => 'Free']);

    $entityManager->clear();
    $recorder->reset();

    $component->call('callMountedAction');

    $recorded = $recorder->normalizedStatements();
    $commit = array_search('COMMIT', $recorded, true);

    // What follows the commit is the host re-rendering its table.
    expect($component->response()->getStatusCode())->toBe(200)
        ->and($commit)->toBeInt()
        ->and(array_slice($recorded, 0, is_int($commit) ? $commit + 1 : 0))->toHaveCount(count($lookups) + count($writes))
        ->and(array_slice($recorded, 0, count($lookups)))->each(static fn (Expectation $statement, int $index): Expectation => $statement->toContain($lookups[$index]))
        ->and(array_slice($recorded, count($lookups), count($writes)))->toBe($writes);

    $entityManager->clear();

    expect($entityManager->find(Stamp::class, ['catalogue' => 1, 'code' => 'AB-7'])?->getCaption())->toBe('Free');
})->with([
    'a nested record page header, which resolves its own record first' => ['nested header', [
        'FROM Catalogue a0_ WHERE a0_.id = ?',
        'FROM Stamp a0_ WHERE a0_.catalogue_id = ? AND (a0_.catalogue_id = ? AND a0_.code = ?)',
        'FROM Stamp p0 WHERE p0.catalogue_id = ? AND p0.code = ?',
    ], [
        'BEGIN',
        'INSERT INTO Stamp (code, caption, catalogue_id) VALUES (?, ?, ?)',
        'COMMIT',
    ]],
    'a related-records page' => ['related-records page', [
        'FROM Catalogue a0_ WHERE a0_.id = ?',
        'FROM Stamp p0 WHERE p0.catalogue_id = ? AND p0.code = ?',
    ], [
        'BEGIN',
        'INSERT INTO Stamp (code, caption, catalogue_id) VALUES (?, ?, ?)',
        'COMMIT',
    ]],
    'a relation manager' => ['relation manager', [
        'FROM Catalogue a0_ WHERE a0_.id = ?',
        'FROM Stamp p0 WHERE p0.catalogue_id = ? AND p0.code = ?',
    ], [
        'BEGIN',
        'INSERT INTO Stamp (code, caption, catalogue_id) VALUES (?, ?, ?)',
        'COMMIT',
    ]],
]);
