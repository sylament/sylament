<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Actions\CreateAction;
use Sylament\Actions\EditAction;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Chapter;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Line;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Verse;
use Sylament\Panels\Tests\Fixtures\Resources\NestedResourceRig;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\CreateVerse;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditLine;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditVerse;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\ListLines;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * Kernel reboot is disabled for the same reason every other page suite disables
 * it: the fixture database lives in memory on a container service, so a reboot
 * between two requests would hand the second one a freshly-migrated, empty one.
 */

/**
 * @return array{KernelBrowser, EntityManagerInterface, Chapter, Verse, Line}
 */
function nestedPagesClient(): array
{
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);

    [$chapter, $verse, $line] = NestedResourceRig::records($entityManager);

    return [$client, $entityManager, $chapter, $verse, $line];
}

/**
 * A chapter of its own, with a verse and a line under it — the other branch
 * every cross-parent case is refused against.
 *
 * @return array{Chapter, Verse}
 */
function foreignChapter(EntityManagerInterface $entityManager): array
{
    $chapter = new Chapter()->setTitle('Exodus');
    $verse = new Verse()->setHeading('Foreign')->setChapter($chapter);

    $entityManager->persist($chapter);
    $entityManager->persist($verse);
    $entityManager->flush();

    return [$chapter, $verse];
}

/**
 * A page read outside a kernel request resolves the *default* panel, so a case
 * reading one in-process stands a request carrying this panel up first — the
 * page's own prop is what restores it on a live request in a browser.
 */
function pushNestedPanelRequest(): void
{
    $request = new Request();
    $request->attributes->set('_sylament_panel', 'nest');

    test()->getContainer()->get(RequestStack::class)->push($request);
}

/**
 * @return array<string, mixed>
 */
function nestedPageProps(string $html): array
{
    preg_match('/data-live-props-value="([^"]*)"/', $html, $matches);

    $props = json_decode(html_entity_decode($matches[1] ?? ''), true, flags: JSON_THROW_ON_ERROR);

    expect($props)->toBeArray();

    return $props;
}

it('serves a nested edit page under its parent', function (): void {
    [$client, , $chapter, $verse] = nestedPagesClient();

    $crawler = $client->request('GET', sprintf('/nest/chapters/%s/verses/%s/edit', $chapter->getId(), $verse->getId()));

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($crawler->filter('h1.sy-pa-header-heading')->text())->toBe('Edit In the beginning')
        ->and($crawler->filter('input[data-model$="data.heading"]')->attr('value'))->toBe('In the beginning');
});

it('404s a first GET naming a record of another parent', function (): void {
    // A plain GET is the only way to catch this: the record is resolved inside
    // `mount()`, so a chain arriving after the mount would scope nothing — and
    // a live component mounts with both values already in hand.
    [$client, $entityManager, , $verse] = nestedPagesClient();
    [$foreignChapter] = foreignChapter($entityManager);

    $client->catchExceptions(true);
    $client->request('GET', sprintf('/nest/chapters/%s/verses/%s/edit', $foreignChapter->getId(), $verse->getId()));

    expect($client->getResponse()->getStatusCode())->toBe(404);
});

it('404s a first GET naming an ancestor that does not exist', function (): void {
    [$client, , , $verse] = nestedPagesClient();

    $client->catchExceptions(true);
    $client->request('GET', sprintf('/nest/chapters/999999/verses/%s/edit', $verse->getId()));

    expect($client->getResponse()->getStatusCode())->toBe(404);
});

it('serves a depth-two page under both of its ancestors', function (): void {
    [$client, , $chapter, $verse, $line] = nestedPagesClient();

    $crawler = $client->request('GET', sprintf('/nest/chapters/%s/verses/%s/lines/%s/edit', $chapter->getId(), $verse->getId(), $line->getId()));

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($crawler->filter('input[data-model$="data.content"]')->attr('value'))->toBe('Let there be light');
});

it('404s a first GET naming a grandparent the chain does not run through', function (): void {
    [$client, $entityManager, , $verse, $line] = nestedPagesClient();
    [$foreignChapter] = foreignChapter($entityManager);

    // The verse is the line's and the line is real: only the step above the
    // verse is foreign, which is the step the walk refuses at.
    $client->catchExceptions(true);
    $client->request('GET', sprintf('/nest/chapters/%s/verses/%s/lines/%s/edit', $foreignChapter->getId(), $verse->getId(), $line->getId()));

    expect($client->getResponse()->getStatusCode())->toBe(404);
});

it('keeps the parent keys on the wire and resolves them again on a live request', function (): void {
    [$client, , $chapter, $verse] = nestedPagesClient();

    $component = $this->createLiveComponent(EditVerse::class, [
        'record' => (string) $verse->getId(),
        'parentRecords' => ['chapter' => (string) $chapter->getId()],
        'panelId' => 'nest',
    ], client: $client);

    $props = nestedPageProps((string) $component->render());

    expect($props['record'])->toBe((string) $verse->getId())
        ->and($props['parentRecords'])->toBe(['chapter' => (string) $chapter->getId()])
        ->and($component->component()->getRecord())->toBe($verse)
        ->and($component->component()->getParentRecord())->toBe($chapter);
});

it('trails every ancestor above a nested record page', function (): void {
    [$client, , $chapter, $verse] = nestedPagesClient();

    $host = $this->createLiveComponent(EditVerse::class, [
        'record' => (string) $verse->getId(),
        'parentRecords' => ['chapter' => (string) $chapter->getId()],
        'panelId' => 'nest',
    ], client: $client)->component();

    pushNestedPanelRequest();

    // `Verses` is int-keyed because the verses are listed by the chapter's own
    // edit page — the crumb right above it — so linking it would repeat a URL
    // the trail already spends.
    expect($host->getBreadcrumbs())->toBe([
        'http://localhost/nest/chapters' => 'Chapters',
        sprintf('http://localhost/nest/chapters/%s/edit', $chapter->getId()) => 'Genesis',
        0 => 'Verses',
        sprintf('http://localhost/nest/chapters/%s/verses/%s/edit', $chapter->getId(), $verse->getId()) => 'In the beginning',
        1 => 'Edit',
    ]);
});

it('leaves an untitled record out of the trail', function (): void {
    [$client, , $chapter, $verse, $line] = nestedPagesClient();

    $host = $this->createLiveComponent(EditLine::class, [
        'record' => (string) $line->getId(),
        'parentRecords' => [
            'chapter' => (string) $chapter->getId(),
            'verse' => (string) $verse->getId(),
        ],
        'panelId' => 'nest',
    ], client: $client)->component();

    pushNestedPanelRequest();

    // The line resource names no title attribute, so the record crumb is left
    // out — and `Lines` is unlinked for the same reason `Verses` is.
    expect($host->getBreadcrumbs())->toBe([
        'http://localhost/nest/chapters' => 'Chapters',
        sprintf('http://localhost/nest/chapters/%s/edit', $chapter->getId()) => 'Genesis',
        0 => 'Verses',
        sprintf('http://localhost/nest/chapters/%s/verses/%s/edit', $chapter->getId(), $verse->getId()) => 'In the beginning',
        1 => 'Lines',
        2 => 'Edit',
    ]);
});

it('lists only the records of the parent a nested list page hangs under', function (): void {
    [$client, $entityManager, $chapter, $verse] = nestedPagesClient();

    $sibling = new Verse()->setHeading('Second')->setChapter($chapter);
    $foreignLine = new Line()->setContent('Under another verse')->setVerse($sibling);

    $entityManager->persist($sibling);
    $entityManager->persist($foreignLine);
    $entityManager->flush();

    $crawler = $client->request('GET', sprintf('/nest/chapters/%s/verses/%s/lines', $chapter->getId(), $verse->getId()));
    $rows = $crawler->filter('tbody .sy-ta-row');

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($rows->count())->toBe(1)
        ->and($rows->text())->toContain('Let there be light');
});

it('creates a record under the parent the create page was opened from', function (): void {
    [$client, $entityManager, $chapter] = nestedPagesClient();

    $component = $this->createLiveComponent(CreateVerse::class, [
        'parentRecords' => ['chapter' => (string) $chapter->getId()],
        'panelId' => 'nest',
    ], client: $client);

    $component->set('data', ['heading' => 'A new verse']);
    $component->call('create');

    $entityManager->clear();
    $created = $entityManager->getRepository(Verse::class)->findOneBy(['heading' => 'A new verse']);

    // The join column refuses null, so a record that reached the database at all
    // is one the page attached to its parent before the flush.
    expect($created)->toBeInstanceOf(Verse::class)
        ->and($created?->getChapter()?->getId())->toBe($chapter->getId())
        ->and($component->response()->isRedirection())->toBeTrue()
        ->and((string) $component->response()->headers->get('Location'))
        ->toEndWith(sprintf('/nest/chapters/%s/verses/%s/edit', $chapter->getId(), $created?->getId()));
});

it('attaches what a create modal on a nested page writes', function (): void {
    [$client, $entityManager, $chapter, $verse] = nestedPagesClient();

    $this->createLiveComponent(EditVerse::class, [
        'record' => (string) $verse->getId(),
        'parentRecords' => ['chapter' => (string) $chapter->getId()],
        'panelId' => 'nest',
    ], client: $client)
        ->call('mountAction', ['name' => 'create', 'arguments' => [], 'context' => []])
        ->set('mountedActionsData.0.heading', 'From the modal')
        ->call('callMountedAction');

    $entityManager->clear();
    $created = $entityManager->getRepository(Verse::class)->findOneBy(['heading' => 'From the modal']);

    expect($created)->toBeInstanceOf(Verse::class)
        ->and($created?->getChapter()?->getId())->toBe($chapter->getId());
});

it('sends a delete on a nested page back to the parent page that lists it', function (): void {
    [$client, $entityManager, $chapter, $verse] = nestedPagesClient();

    $response = $this->createLiveComponent(EditVerse::class, [
        'record' => (string) $verse->getId(),
        'parentRecords' => ['chapter' => (string) $chapter->getId()],
        'panelId' => 'nest',
    ], client: $client)
        ->call('mountAction', ['name' => 'delete', 'arguments' => [], 'context' => []])
        ->call('callMountedAction')
        ->response();

    $entityManager->clear();

    // The verses have no index page of their own: their listing is the chapter's
    // own record page, which is where the redirect lands.
    expect($response->isRedirection())->toBeTrue()
        ->and((string) $response->headers->get('Location'))->toEndWith(sprintf('/nest/chapters/%s/edit', $chapter->getId()))
        ->and($entityManager->getRepository(Verse::class)->find((int) $verse->getId()))->toBeNull();
});

it('carries the chain into the urls a link-style action on a nested page mints', function (): void {
    [$client, , $chapter, $verse, $line] = nestedPagesClient();

    $host = $this->createLiveComponent(ListLines::class, [
        'parentRecords' => [
            'chapter' => (string) $chapter->getId(),
            'verse' => (string) $verse->getId(),
        ],
        'panelId' => 'nest',
    ], client: $client)->component();

    pushNestedPanelRequest();

    $prefix = sprintf('/nest/chapters/%s/verses/%s/lines', $chapter->getId(), $verse->getId());

    expect($host->getDefaultActionUrl(CreateAction::make()))->toEndWith($prefix . '/create')
        ->and($host->getDefaultActionUrl(EditAction::make()->record($line)))->toEndWith(sprintf('%s/%s/edit', $prefix, $line->getId()));
});

it('404s a mounted frame naming a record of another parent', function (): void {
    [$client, $entityManager, $chapter, $verse] = nestedPagesClient();
    [, $foreignVerse] = foreignChapter($entityManager);

    $host = $this->createLiveComponent(EditVerse::class, [
        'record' => (string) $verse->getId(),
        'parentRecords' => ['chapter' => (string) $chapter->getId()],
        'panelId' => 'nest',
    ], client: $client)->component();

    // The descriptor's key is client-supplied and carries no checksum, so it
    // goes through the same parent scope a route key does.
    expect(fn (): mixed => $host->mountAction('edit', [], ['recordKey' => (string) $foreignVerse->getId()]))
        ->toThrow(NotFoundHttpException::class, 'No record matching');
});

it('mounts a frame naming a record of its own parent', function (): void {
    [$client, $entityManager, $chapter, $verse] = nestedPagesClient();

    $sibling = new Verse()->setHeading('Sibling')->setChapter($chapter);

    $entityManager->persist($sibling);
    $entityManager->flush();

    $host = $this->createLiveComponent(EditVerse::class, [
        'record' => (string) $verse->getId(),
        'parentRecords' => ['chapter' => (string) $chapter->getId()],
        'panelId' => 'nest',
    ], client: $client)->component();

    $host->mountAction('edit', [], ['recordKey' => (string) $sibling->getId()]);

    expect($host->mountedActions)->toHaveCount(1);
});
