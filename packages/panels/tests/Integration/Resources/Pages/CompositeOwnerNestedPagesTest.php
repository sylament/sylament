<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Resources\ResourceRegistry;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Binding;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Catalogue;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Edition;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Imprint;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Motif;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Stamp;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\StampMark;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Variant;
use Sylament\Panels\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Panels\Tests\Fixtures\Resources\ImprintBindingResource;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\CreateImprintBinding;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditEditionVariant;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditImprintBinding;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\LiveComponent\Exception\HydrationException;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * The nested tier with the composite record above rather than below: a parent
 * keyed by two plain fields and a parent whose first identifier field is named
 * `id`, each owning children of its own. Kernel reboot is disabled throughout,
 * because the fixture database lives in memory on a container service.
 *
 * Every key is a literal: the imprint `a~b` / `c=d ž` is
 * `a=7Eb~c=3Dd=20=C5=BE`, the imprint `plain` / `about` is `plain~about`, and
 * the two editions are `5~en-GB` and `5~sk`. Minting an expected value through
 * the codec would make a case pass against any format at all.
 */

/**
 * Two imprints and two editions, each owning children the other must not list:
 * the second imprint's binding and the second edition's variant are what a
 * scope reading half a parent key, or none of it, would let through.
 *
 * @return array{KernelBrowser, EntityManagerInterface}
 */
function compositeOwnerNestedClient(): array
{
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);

    $exotic = new Imprint()->setHouse('a~b')->setYear('c=d ž')->setLabel('Exotic');
    $plain = new Imprint()->setHouse('plain')->setYear('about')->setLabel('Plain');

    $fifth = new Edition()->setId(5)->setLocale('en-GB')->setTitle('Fifth');
    $slovak = new Edition()->setId(5)->setLocale('sk')->setTitle('Piate');

    EntityManagerFactory::seed(
        $entityManager,
        $exotic,
        $plain,
        new Binding()->setImprint($exotic)->setTitle('Quarter'),
        new Binding()->setImprint($exotic)->setTitle('Half'),
        new Binding()->setImprint($plain)->setTitle('Rival'),
        $fifth,
        $slovak,
        new Variant()->setEdition($fifth)->setName('Large'),
        new Variant()->setEdition($slovak)->setName('Small'),
    );

    return [$client, $entityManager];
}

/**
 * @param class-string<Resource> $class
 */
function compositeOwnerNestedResource(string $class): Resource
{
    $registry = test()->getContainer()->get(ResourceRegistry::class);

    expect($registry)->toBeInstanceOf(ResourceRegistry::class);

    return $registry->get($class);
}

function compositeOwnerNestedRecord(EntityManagerInterface $entityManager, string $class, array $criteria): object
{
    $record = $entityManager->getRepository($class)->findOneBy($criteria);

    expect($record)->toBeInstanceOf($class);
    assert(is_object($record));

    return $record;
}

/**
 * The imprint foreign key a binding's row really holds, read as columns rather
 * than through the mapping.
 *
 * @return array<string, mixed>
 */
function compositeOwnerNestedStoredColumns(EntityManagerInterface $entityManager, string $title): array
{
    $row = $entityManager->getConnection()->fetchAssociative(
        'SELECT imprint_house, imprint_year FROM Binding WHERE title = ?',
        [$title],
    );

    expect($row)->toBeArray();

    return is_array($row) ? $row : [];
}

/**
 * A page read outside a kernel request resolves the *default* panel, so a case
 * reading one in-process stands a request carrying this panel up first.
 */
function compositeOwnerNestedPushPanelRequest(): void
{
    $request = new Request();
    $request->attributes->set('_sylament_panel', 'key');

    test()->getContainer()->get(RequestStack::class)->push($request);
}

/**
 * The three-level rig: a catalogue, the composite stamp under it and a mark
 * under that — the chain whose middle level is the composite one.
 */
function compositeOwnerNestedThreeLevels(EntityManagerInterface $entityManager): void
{
    $catalogue = new Catalogue()->setTitle('First');
    $stamp = new Stamp()->setCatalogue($catalogue)->setCode('A/B C')->setCaption('Alpha');

    EntityManagerFactory::seed(
        $entityManager,
        $catalogue,
        $stamp,
        new StampMark()->setStamp($stamp)->setLabel('Mark A1'),
    );
}

/**
 * The live root carrying the parent chain — the page's own element rather than
 * the topbar search component, which is the first live root on the document.
 */
function compositeOwnerNestedChainRoot(Crawler $crawler): Crawler
{
    return $crawler->filter('[data-live-name-value]')->reduce(
        static fn (Crawler $node): bool => str_contains((string) $node->attr('data-live-props-value'), 'parentRecords'),
    );
}

it('lists only the children of the scalar-pair parent named in the url', function (): void {
    [$client] = compositeOwnerNestedClient();

    $crawler = $client->request('GET', '/key/binderies/a=7Eb~c=3Dd=20=C5=BE/imprint-bindings');
    $rows = $crawler->filter('tbody .sy-ta-row');
    $text = implode(' ', $rows->each(static fn (Crawler $row): string => $row->text()));

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($rows->count())->toBe(2)
        ->and($text)->toContain('Half')
        ->and($text)->toContain('Quarter')
        ->and($text)->not->toContain('Rival');
});

it('404s a child of another scalar-pair parent and serves the same child under its own', function (): void {
    [$client] = compositeOwnerNestedClient();

    $client->catchExceptions(true);
    $client->request('GET', '/key/binderies/plain~about/imprint-bindings/1/edit');

    expect($client->getResponse()->getStatusCode())->toBe(404);

    $crawler = $client->request('GET', '/key/binderies/a=7Eb~c=3Dd=20=C5=BE/imprint-bindings/1/edit');

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($crawler->filter('input[data-model$="data.title"]')->attr('value'))->toBe('Quarter');
});

it('404s a parent segment spelling half the key of an id-plus-locale parent', function (): void {
    [$client] = compositeOwnerNestedClient();

    $client->catchExceptions(true);

    // `5` is the value of the field literally named `id`, and both editions
    // carry it — so a partial parent key has to miss rather than pick one.
    $client->request('GET', '/key/editions/5/edition-variants/1/edit');

    expect($client->getResponse()->getStatusCode())->toBe(404);

    $client->request('GET', '/key/editions/5~sk/edition-variants/1/edit');

    expect($client->getResponse()->getStatusCode())->toBe(404);

    $crawler = $client->request('GET', '/key/editions/5~en-GB/edition-variants/1/edit');

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($crawler->filter('input[data-model$="data.name"]')->attr('value'))->toBe('Large');
});

it('scopes the id-plus-locale parent list to the locale the url names', function (): void {
    [$client] = compositeOwnerNestedClient();

    $crawler = $client->request('GET', '/key/editions/5~sk/edition-variants');
    $rows = $crawler->filter('tbody .sy-ta-row');

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($rows->count())->toBe(1)
        ->and($rows->first()->text())->toContain('Small');
});

it('stores both fields of the scalar-pair parent on the row a nested create page writes', function (): void {
    [$client, $entityManager] = compositeOwnerNestedClient();

    $component = $this->createLiveComponent(CreateImprintBinding::class, [
        'parentRecords' => ['imprint' => 'a=7Eb~c=3Dd=20=C5=BE'],
        'panelId' => 'key',
    ], client: $client);

    $component->set('data', ['title' => 'Created']);
    $component->call('create');

    $entityManager->clear();

    // Both identifier fields carry a character the key grammar escapes, so a
    // row written from a half-decoded key would not match either imprint.
    expect(compositeOwnerNestedStoredColumns($entityManager, 'Created'))
        ->toBe(['imprint_house' => 'a~b', 'imprint_year' => 'c=d ž'])
        ->and($component->response()->isRedirection())->toBeTrue()
        ->and((string) $component->response()->headers->get('Location'))
        ->toEndWith('/key/binderies/a=7Eb~c=3Dd=20=C5=BE/imprint-bindings/4/edit');
});

it('saves an edit made on a nested page under a scalar-pair parent', function (): void {
    [$client, $entityManager] = compositeOwnerNestedClient();

    $component = $this->createLiveComponent(EditImprintBinding::class, [
        'record' => '1',
        'parentRecords' => ['imprint' => 'a=7Eb~c=3Dd=20=C5=BE'],
        'panelId' => 'key',
    ], client: $client);

    $component->set('data.title', 'Renamed')->call('save');

    $entityManager->clear();

    expect($component->response()->getStatusCode())->toBe(200)
        ->and(compositeOwnerNestedRecord($entityManager, Binding::class, ['title' => 'Renamed'])->getImprint()?->getLabel())
        ->toBe('Exotic');
});

it('sends a delete on a nested page back to the parent page that lists it', function (): void {
    [$client, $entityManager] = compositeOwnerNestedClient();

    $response = $this->createLiveComponent(EditImprintBinding::class, [
        'record' => '1',
        'parentRecords' => ['imprint' => 'a=7Eb~c=3Dd=20=C5=BE'],
        'panelId' => 'key',
    ], client: $client)
        ->call('mountAction', ['name' => 'delete', 'arguments' => [], 'context' => []])
        ->call('callMountedAction')
        ->response();

    $entityManager->clear();

    // A nested resource's index URL is the parent's own page for the
    // relationship, so the composite key travels on the redirect rather than
    // being dropped with the listing it came from.
    expect($response->isRedirection())->toBeTrue()
        ->and((string) $response->headers->get('Location'))
        ->toEndWith('/key/binderies/a=7Eb~c=3Dd=20=C5=BE/bindings')
        ->and($entityManager->getRepository(Binding::class)->findOneBy(['title' => 'Quarter']))->toBeNull();
});

it('falls a nested delete back to the parent edit page where the parent lists nothing', function (): void {
    [$client, $entityManager] = compositeOwnerNestedClient();

    $response = $this->createLiveComponent(EditEditionVariant::class, [
        'record' => '1',
        'parentRecords' => ['edition' => '5~en-GB'],
        'panelId' => 'key',
    ], client: $client)
        ->call('mountAction', ['name' => 'delete', 'arguments' => [], 'context' => []])
        ->call('callMountedAction')
        ->response();

    $entityManager->clear();

    // `EditionResource` registers neither a `variants` page nor a view one, so
    // the fallback walks to its edit page — carrying the parent key, which is
    // the half of the redirect a partial key would strand.
    expect($response->isRedirection())->toBeTrue()
        ->and((string) $response->headers->get('Location'))->toEndWith('/key/editions/5~en-GB/edit')
        ->and($entityManager->getRepository(Variant::class)->findOneBy(['name' => 'Large']))->toBeNull();
});

it('attaches what a create modal on a nested page writes to the parent it was opened under', function (): void {
    [$client, $entityManager] = compositeOwnerNestedClient();

    $this->createLiveComponent(EditImprintBinding::class, [
        'record' => '1',
        'parentRecords' => ['imprint' => 'a=7Eb~c=3Dd=20=C5=BE'],
        'panelId' => 'key',
    ], client: $client)
        ->call('mountAction', ['name' => 'create', 'arguments' => [], 'context' => []])
        ->set('mountedActionsData.0.title', 'From the modal')
        ->call('callMountedAction');

    $entityManager->clear();

    // The page answers the parent relationship for the mounted action, so the
    // modal's write is attached rather than left an orphan.
    expect(compositeOwnerNestedStoredColumns($entityManager, 'From the modal'))
        ->toBe(['imprint_house' => 'a~b', 'imprint_year' => 'c=d ž']);
});

it('trails a child through the composite parent above it', function (): void {
    [$client, $entityManager] = compositeOwnerNestedClient();

    $host = $this->createLiveComponent(EditImprintBinding::class, [
        'record' => '1',
        'parentRecords' => ['imprint' => 'a=7Eb~c=3Dd=20=C5=BE'],
        'panelId' => 'key',
    ], client: $client)->component();

    compositeOwnerNestedPushPanelRequest();

    expect($entityManager)->toBeInstanceOf(EntityManagerInterface::class)
        ->and($host->getBreadcrumbs())->toBe([
            'http://localhost/key/binderies' => 'Binderies',
            'http://localhost/key/binderies/a=7Eb~c=3Dd=20=C5=BE/edit' => 'Exotic',
            'http://localhost/key/binderies/a=7Eb~c=3Dd=20=C5=BE/bindings' => 'Bindings',
            'http://localhost/key/binderies/a=7Eb~c=3Dd=20=C5=BE/imprint-bindings/1/edit' => 'Quarter',
            0 => 'Edit',
        ]);
});

it('mints a nested url from a composite ancestor passed as an object and as a key', function (): void {
    [, $entityManager] = compositeOwnerNestedClient();

    $imprint = compositeOwnerNestedRecord($entityManager, Imprint::class, ['label' => 'Exotic']);
    $binding = compositeOwnerNestedRecord($entityManager, Binding::class, ['title' => 'Quarter']);

    compositeOwnerNestedPushPanelRequest();

    $resource = compositeOwnerNestedResource(ImprintBindingResource::class);

    expect($resource->getUrl('edit', ['record' => $binding, 'imprint' => $imprint], isAbsolute: false))
        ->toBe('/key/binderies/a=7Eb~c=3Dd=20=C5=BE/imprint-bindings/1/edit')
        ->and($resource->getUrl('edit', ['record' => $binding, 'imprint' => 'a=7Eb~c=3Dd=20=C5=BE'], isAbsolute: false))
        ->toBe('/key/binderies/a=7Eb~c=3Dd=20=C5=BE/imprint-bindings/1/edit')
        ->and($resource->getIndexUrl(['imprint' => $imprint], isAbsolute: false))
        ->toBe('/key/binderies/a=7Eb~c=3Dd=20=C5=BE/bindings');
});

it('guesses a composite ancestor key from a child record alone', function (): void {
    [, $entityManager] = compositeOwnerNestedClient();

    $binding = compositeOwnerNestedRecord($entityManager, Binding::class, ['title' => 'Quarter']);

    compositeOwnerNestedPushPanelRequest();

    // Nothing names the imprint here: the walk reads it off the child and keys
    // it through the parent resource, which is the only way a row action on a
    // nested listing mints its own link.
    expect(compositeOwnerNestedResource(ImprintBindingResource::class)->getUrl('edit', ['record' => $binding], isAbsolute: false, shouldGuessMissingParameters: true))
        ->toBe('/key/binderies/a=7Eb~c=3Dd=20=C5=BE/imprint-bindings/1/edit');
});

it('carries a composite parent key in the middle of a three-level chain into a second live request', function (): void {
    [$client, $entityManager] = compositeOwnerNestedClient();

    compositeOwnerNestedThreeLevels($entityManager);

    $client->catchExceptions(false);

    $root = compositeOwnerNestedChainRoot($client->request('GET', '/key/catalogues/1/stamps/1~A=2FB=20C/stamp-marks/1/edit'));
    $props = json_decode((string) $root->attr('data-live-props-value'), true, flags: JSON_THROW_ON_ERROR);

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($props)->toBeArray()
        ->and($props['record'])->toBe('1')
        ->and($props['parentRecords'])->toBe(['catalogue' => '1', 'stamp' => '1~A=2FB=20C']);

    $second = $client->request(
        'POST',
        (string) $root->attr('data-live-url-value'),
        ['data' => json_encode(['props' => $props], JSON_THROW_ON_ERROR)],
        [],
        ['HTTP_X-Requested-With' => 'XMLHttpRequest'],
    );

    // The composite level is the middle one, so the chain has to survive both
    // the dehydration that wrote it and the hydration that reads it back.
    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($second->text())->toContain('Mark A1');
});

it('refuses a three-level chain whose composite level was rewritten on the wire', function (): void {
    [$client, $entityManager] = compositeOwnerNestedClient();

    compositeOwnerNestedThreeLevels($entityManager);

    $client->catchExceptions(false);

    $root = compositeOwnerNestedChainRoot($client->request('GET', '/key/catalogues/1/stamps/1~A=2FB=20C/stamp-marks/1/edit'));
    $props = json_decode((string) $root->attr('data-live-props-value'), true, flags: JSON_THROW_ON_ERROR);

    expect($props)->toBeArray();

    $props['parentRecords']['stamp'] = '1~bee';

    // The chain is not client-writable, so a rewritten parent key is a
    // checksum refusal rather than a silently re-scoped page.
    expect(fn (): mixed => $client->request(
        'POST',
        (string) $root->attr('data-live-url-value'),
        ['data' => json_encode(['props' => $props], JSON_THROW_ON_ERROR)],
        [],
        ['HTTP_X-Requested-With' => 'XMLHttpRequest'],
    ))->toThrow(HydrationException::class, 'Invalid checksum');
});

it('lists the scalar-pair owner as the related resource parent of every url the routed host mints', function (): void {
    [$client] = compositeOwnerNestedClient();

    $crawler = $client->request('GET', '/key/binderies/a=7Eb~c=3Dd=20=C5=BE/bindings');
    $hrefs = array_values(array_unique($crawler->filter('a[href*="imprint-bindings"]')->each(static fn (Crawler $link): string => (string) $link->attr('href'))));

    sort($hrefs);

    // The owner is handed to the related resource as its own parent parameter,
    // so every row and header link carries the composite key it was keyed with.
    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($hrefs)->toBe([
            'http://localhost/key/binderies/a=7Eb~c=3Dd=20=C5=BE/imprint-bindings/1/edit',
            'http://localhost/key/binderies/a=7Eb~c=3Dd=20=C5=BE/imprint-bindings/2/edit',
            'http://localhost/key/binderies/a=7Eb~c=3Dd=20=C5=BE/imprint-bindings/create',
        ]);
});

it('keeps the index-less nested resource of a many-to-many parent scoped to that parent', function (): void {
    [$client, $entityManager] = compositeOwnerNestedClient();

    $exotic = compositeOwnerNestedRecord($entityManager, Imprint::class, ['label' => 'Exotic']);
    $plain = compositeOwnerNestedRecord($entityManager, Imprint::class, ['label' => 'Plain']);

    assert($exotic instanceof Imprint && $plain instanceof Imprint);

    $rosette = new Motif()->setName('Rosette');
    $fleuron = new Motif()->setName('Fleuron');

    $exotic->addMotif($rosette);
    $plain->addMotif($fleuron);

    EntityManagerFactory::seed($entityManager, $rosette, $fleuron, $exotic, $plain);

    $client->catchExceptions(true);
    $client->request('GET', '/key/binderies/plain~about/imprint-motives/1/edit');

    expect($client->getResponse()->getStatusCode())->toBe(404);

    $crawler = $client->request('GET', '/key/binderies/a=7Eb~c=3Dd=20=C5=BE/imprint-motives/1/edit');

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($crawler->filter('input[data-model$="data.name"]')->attr('value'))->toBe('Rosette');
});
