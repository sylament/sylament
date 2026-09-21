<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Actions\CreateAction;
use Sylament\Actions\EditAction;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Catalogue;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Stamp;
use Sylament\Panels\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\CreateStamp;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditStamp;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * Kernel reboot is disabled for the same reason `NestedResourcePagesTest`
 * disables it: the fixture database lives in memory on a container service, so
 * a reboot between two requests would hand the second one a freshly-migrated,
 * empty one.
 *
 * Every key below is a literal. `Catalogue` "First" is `1`, and the stamp whose
 * code reads `A/B C` under it is `1~A=2FB=20C` — the parent's key, the
 * separator, and the code with its slash and space escaped. Minting the
 * expected value through the codec would make these cases pass against any
 * format at all.
 */

/**
 * @return array{KernelBrowser, EntityManagerInterface}
 */
function compositeNestedClient(): array
{
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);

    $catalogue = new Catalogue()->setTitle('First');

    EntityManagerFactory::seed(
        $entityManager,
        $catalogue,
        new Stamp()->setCatalogue($catalogue)->setCode('A/B C')->setCaption('Alpha'),
    );

    return [$client, $entityManager];
}

/**
 * A catalogue of its own, carrying a stamp whose code is the same string — so
 * the two stamps differ in nothing but the half of the identifier the parent
 * supplies, and `2~A=2FB=20C` is the other branch every cross-parent case is
 * refused against.
 */
function compositeNestedForeignCatalogue(EntityManagerInterface $entityManager): void
{
    $catalogue = new Catalogue()->setTitle('Second');

    EntityManagerFactory::seed(
        $entityManager,
        $catalogue,
        new Stamp()->setCatalogue($catalogue)->setCode('A/B C')->setCaption('Foreign'),
    );
}

function compositeNestedStamp(EntityManagerInterface $entityManager, string $caption): Stamp
{
    $stamp = $entityManager->getRepository(Stamp::class)->findOneBy(['caption' => $caption]);

    expect($stamp)->toBeInstanceOf(Stamp::class);

    return $stamp;
}

/**
 * A page read outside a kernel request resolves the *default* panel, so a case
 * reading one in-process stands a request carrying this panel up first.
 */
function compositeNestedPushPanelRequest(): void
{
    $request = new Request();
    $request->attributes->set('_sylament_panel', 'key');

    test()->getContainer()->get(RequestStack::class)->push($request);
}

/**
 * @return array<string, mixed>
 */
function compositeNestedProps(string $html): array
{
    preg_match('/data-live-props-value="([^"]*)"/', $html, $matches);

    $props = json_decode(html_entity_decode($matches[1] ?? ''), true, flags: JSON_THROW_ON_ERROR);

    expect($props)->toBeArray();

    return $props;
}

it('serves a nested edit page whose record is keyed by its parent and a converted field', function (): void {
    [$client] = compositeNestedClient();

    $crawler = $client->request('GET', '/key/catalogues/1/stamps/1~A=2FB=20C/edit');

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($crawler->filter('h1.sy-pa-header-heading')->text())->toBe('Edit Alpha')
        ->and($crawler->filter('input[data-model$="data.code"]')->attr('value'))->toBe('A/B C');
});

it('404s a first GET naming a stamp of another catalogue', function (): void {
    // A plain GET is the only way to catch this: the record is resolved inside
    // `mount()`, so a chain arriving after the mount would scope nothing.
    [$client, $entityManager] = compositeNestedClient();
    compositeNestedForeignCatalogue($entityManager);

    $client->catchExceptions(true);

    // The other branch's key under this page's parent: the two stamps differ
    // in nothing but the half of the identifier their catalogue supplies.
    $client->request('GET', '/key/catalogues/1/stamps/2~A=2FB=20C/edit');

    expect($client->getResponse()->getStatusCode())->toBe(404);

    // And the mirror: this catalogue's stamp under the other catalogue.
    $client->request('GET', '/key/catalogues/2/stamps/1~A=2FB=20C/edit');

    expect($client->getResponse()->getStatusCode())->toBe(404);
});

it('404s a first GET whose parent key is spelled in a way the decode refuses', function (): void {
    [$client] = compositeNestedClient();

    $client->catchExceptions(true);
    $client->request('GET', '/key/catalogues/01/stamps/1~A=2FB=20C/edit');

    expect($client->getResponse()->getStatusCode())->toBe(404);
});

it('keys a nested record by the value its record reads, not the one its column holds', function (): void {
    [$client] = compositeNestedClient();

    $client->catchExceptions(true);
    $client->request('GET', '/key/catalogues/1/stamps/1~a=2Fb=20c/edit');

    expect($client->getResponse()->getStatusCode())->toBe(404);

    $client->request('GET', '/key/catalogues/1/stamps/1~A=2FB=20C/edit');

    expect($client->getResponse()->getStatusCode())->toBe(200);
});

it('keeps a composite key and its parent key on the wire and resolves them again on a live request', function (): void {
    [$client, $entityManager] = compositeNestedClient();

    $component = $this->createLiveComponent(EditStamp::class, [
        'record' => '1~A=2FB=20C',
        'parentRecords' => ['catalogue' => '1'],
        'panelId' => 'key',
    ], client: $client);

    $props = compositeNestedProps((string) $component->render());

    expect($props['record'])->toBe('1~A=2FB=20C')
        ->and($props['parentRecords'])->toBe(['catalogue' => '1']);

    $component->set('data.caption', 'Renamed')->call('save');

    $entityManager->clear();

    expect($component->response()->getStatusCode())->toBe(200)
        ->and(compositeNestedStamp($entityManager, 'Renamed')->getCode())->toBe('A/B C');
});

it('trails a composite record through the parent record above it', function (): void {
    [$client] = compositeNestedClient();

    $host = $this->createLiveComponent(EditStamp::class, [
        'record' => '1~A=2FB=20C',
        'parentRecords' => ['catalogue' => '1'],
        'panelId' => 'key',
    ], client: $client)->component();

    compositeNestedPushPanelRequest();

    // `Stamps` is linked, unlike the single-id vertical's `Verses`: the
    // catalogue registers a page for the relationship, so the crumb spends a
    // URL the trail has not already spent.
    expect($host->getBreadcrumbs())->toBe([
        'http://localhost/key/catalogues' => 'Catalogues',
        'http://localhost/key/catalogues/1/edit' => 'First',
        'http://localhost/key/catalogues/1/stamps' => 'Stamps',
        'http://localhost/key/catalogues/1/stamps/1~A=2FB=20C/edit' => 'Alpha',
        0 => 'Edit',
    ]);
});

it('creates a composite record under the parent the create page was opened from', function (): void {
    [$client, $entityManager] = compositeNestedClient();

    $component = $this->createLiveComponent(CreateStamp::class, [
        'parentRecords' => ['catalogue' => '1'],
        'panelId' => 'key',
    ], client: $client);

    $component->set('data', ['code' => 'NEW/1', 'caption' => 'Created']);
    $component->call('create');

    $entityManager->clear();

    expect(compositeNestedStamp($entityManager, 'Created')->getCatalogue()?->getId())->toBe(1)
        ->and($component->response()->isRedirection())->toBeTrue()
        ->and((string) $component->response()->headers->get('Location'))
        ->toEndWith('/key/catalogues/1/stamps/1~NEW=2F1/edit');
});

it('attaches what a create modal on a nested composite page writes before the record is keyed', function (): void {
    [$client, $entityManager] = compositeNestedClient();

    $this->createLiveComponent(EditStamp::class, [
        'record' => '1~A=2FB=20C',
        'parentRecords' => ['catalogue' => '1'],
        'panelId' => 'key',
    ], client: $client)
        ->call('mountAction', ['name' => 'create', 'arguments' => [], 'context' => []])
        ->set('mountedActionsData.0.code', 'MODAL/2')
        ->set('mountedActionsData.0.caption', 'From the modal')
        ->call('callMountedAction');

    $entityManager->clear();

    // Half this record's identifier is the parent link, so a write that
    // persisted before it attached could not have reached the database at all.
    expect(compositeNestedStamp($entityManager, 'From the modal')->getCatalogue()?->getId())->toBe(1);
});

it('sends a delete on a nested composite page back to the parent page that lists it', function (): void {
    [$client, $entityManager] = compositeNestedClient();

    $response = $this->createLiveComponent(EditStamp::class, [
        'record' => '1~A=2FB=20C',
        'parentRecords' => ['catalogue' => '1'],
        'panelId' => 'key',
    ], client: $client)
        ->call('mountAction', ['name' => 'delete', 'arguments' => [], 'context' => []])
        ->call('callMountedAction')
        ->response();

    $entityManager->clear();

    expect($response->isRedirection())->toBeTrue()
        ->and((string) $response->headers->get('Location'))->toEndWith('/key/catalogues/1/stamps')
        ->and($entityManager->getRepository(Stamp::class)->findOneBy(['caption' => 'Alpha']))->toBeNull();
});

it('carries the chain and the composite key into the urls a link-style action on a nested page mints', function (): void {
    [$client, $entityManager] = compositeNestedClient();

    $stamp = compositeNestedStamp($entityManager, 'Alpha');

    $host = $this->createLiveComponent(CreateStamp::class, [
        'parentRecords' => ['catalogue' => '1'],
        'panelId' => 'key',
    ], client: $client)->component();

    compositeNestedPushPanelRequest();

    expect($host->getDefaultActionUrl(CreateAction::make()))->toEndWith('/key/catalogues/1/stamps/create')
        ->and($host->getDefaultActionUrl(EditAction::make()->record($stamp)))
        ->toEndWith('/key/catalogues/1/stamps/1~A=2FB=20C/edit');
});

it('404s a mounted frame naming a composite record of another parent', function (): void {
    [$client, $entityManager] = compositeNestedClient();
    compositeNestedForeignCatalogue($entityManager);

    $host = $this->createLiveComponent(EditStamp::class, [
        'record' => '1~A=2FB=20C',
        'parentRecords' => ['catalogue' => '1'],
        'panelId' => 'key',
    ], client: $client)->component();

    // The descriptor's key is client-supplied and carries no checksum, so it
    // goes through the same parent scope a route key does.
    expect(fn (): mixed => $host->mountAction('edit', [], ['recordKey' => '2~A=2FB=20C']))
        ->toThrow(NotFoundHttpException::class, 'No record matching [2~A=2FB=20C]');
});

it('mounts a frame naming a composite record of its own parent', function (): void {
    [$client, $entityManager] = compositeNestedClient();

    $catalogue = $entityManager->getRepository(Catalogue::class)->find(1);

    expect($catalogue)->toBeInstanceOf(Catalogue::class);

    EntityManagerFactory::seed(
        $entityManager,
        new Stamp()->setCatalogue($catalogue)->setCode('SIB')->setCaption('Sibling'),
    );

    $host = $this->createLiveComponent(EditStamp::class, [
        'record' => '1~A=2FB=20C',
        'parentRecords' => ['catalogue' => '1'],
        'panelId' => 'key',
    ], client: $client)->component();

    $host->mountAction('edit', [], ['recordKey' => '1~SIB']);

    expect($host->mountedActions)->toHaveCount(1);
});
