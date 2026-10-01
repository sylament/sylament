<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Catalogue;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Colophon;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Edition;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Imprint;
use Sylament\Panels\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\CreateImprint;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\CreateYearlessImprint;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditEdition;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditImprint;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\ListImprints;
use Sylament\Support\Tests\Doctrine\RecordingMiddleware;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * Every key below is written as a literal: a test that asks the codec for the
 * value it expects passes against any grammar at all. The three imprints carry
 * `.`, `/`, a space, `~`, `=` and a non-ASCII letter between them, so the
 * escaped alphabet reaches a URL, a row id, a class token and the wire.
 *
 * Kernel reboot is disabled for the same reason `EditRecordTest` disables it:
 * the fixture database lives in memory on a container service, so a reboot
 * between two requests would hand the second one a freshly-migrated, empty one.
 */

/**
 * @return array{KernelBrowser, EntityManagerInterface}
 */
function compositeRecordClient(): array
{
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);

    $catalogue = new Catalogue()->setTitle('First');

    EntityManagerFactory::seed(
        $entityManager,
        $catalogue,
        new Colophon()->setCatalogue($catalogue)->setNote('Note'),
        new Imprint()->setHouse('www.example.com')->setYear('/blog 2026')->setLabel('Escaped')->setCatalogue($catalogue),
        new Imprint()->setHouse('plain')->setYear('about')->setLabel('Plain'),
        new Imprint()->setHouse('a~b')->setYear('c=d ž')->setLabel('Exotic'),
        new Edition()->setId(5)->setLocale('en-GB')->setTitle('Fifth'),
    );

    return [$client, $entityManager];
}

/**
 * The container-wide statement recorder, emptied, so what a case reads off it
 * afterwards is what ran since.
 */
function compositeRecordRecorder(): RecordingMiddleware
{
    $recorder = test()->getContainer()->get(RecordingMiddleware::class);
    assert($recorder instanceof RecordingMiddleware);

    $recorder->reset();

    return $recorder;
}

/**
 * The fixtures sit on the non-default `key` panel, so an in-process call has to
 * carry the panel the way a live request's `#[PostHydrate]` stamp does — and a
 * session besides, because a success notification is a session flash.
 */
function compositeRecordPushPanelRequest(): void
{
    $request = new Request();
    $request->attributes->set('_sylament_panel', 'key');
    $request->setSession(test()->getContainer()->get('session.factory')->createSession());

    test()->getContainer()->get(RequestStack::class)->push($request);
}

/**
 * @return array<string, mixed>
 */
function compositeRecordProps(string $html): array
{
    preg_match('/data-live-props-value="([^"]*)"/', $html, $matches);

    $props = json_decode(html_entity_decode($matches[1] ?? ''), true, flags: JSON_THROW_ON_ERROR);

    expect($props)->toBeArray();

    return $props;
}

/**
 * Every field error on the page, keyed by the live model of the input its
 * field wrapper holds — so a message is pinned to the field it renders under.
 *
 * @return array<string, list<string>>
 */
function compositeRecordFieldErrors(string $html): array
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
 * The statements the recorder saw that write, which a refusal must leave empty.
 *
 * @return list<string>
 */
function compositeRecordWrites(RecordingMiddleware $recorder): array
{
    return array_values(array_filter($recorder->statements(), static fn (string $sql): bool => ! str_starts_with($sql, 'SELECT')));
}

it('keys every composite list row and its edit link by the escaped key', function (): void {
    [$client] = compositeRecordClient();

    $crawler = $client->request('GET', '/key/imprints');

    $ids = $crawler->filter('tbody .sy-ta-row')->each(
        fn (Crawler $row): string => explode('.table.records.', (string) $row->attr('id'))[1] ?? '',
    );
    $hrefs = array_unique($crawler->filter('tbody a[href*="/edit"]')->each(fn (Crawler $link): string => (string) $link->attr('href')));

    sort($hrefs);

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($ids)->toEqualCanonicalizing(['www=2Eexample=2Ecom~=2Fblog=202026', 'plain~about', 'a=7Eb~c=3Dd=20=C5=BE'])
        ->and(array_values($hrefs))->toBe([
            'http://localhost/key/imprints/a=7Eb~c=3Dd=20=C5=BE/edit',
            'http://localhost/key/imprints/plain~about/edit',
            'http://localhost/key/imprints/www=2Eexample=2Ecom~=2Fblog=202026/edit',
        ]);
});

it('serves the composite edit page from a cold GET with the record filled in', function (): void {
    [$client] = compositeRecordClient();

    $crawler = $client->request('GET', '/key/imprints/www=2Eexample=2Ecom~=2Fblog=202026/edit');

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($crawler->filter('h1.sy-pa-header-heading')->text())->toBe('Edit Escaped')
        ->and($crawler->filter('.sy-pa-resource-edit-record-page input[data-model$="data.house"]')->attr('value'))->toBe('www.example.com')
        ->and($crawler->filter('.sy-pa-resource-edit-record-page input[data-model$="data.year"]')->attr('value'))->toBe('/blog 2026')
        ->and($crawler->filter('.sy-pa-resource-edit-record-page input[data-model$="data.label"]')->attr('value'))->toBe('Escaped');
});

it('stamps a composite key onto the page as one class token', function (): void {
    [$client] = compositeRecordClient();

    $crawler = $client->request('GET', '/key/imprints/a=7Eb~c=3Dd=20=C5=BE/edit');

    $classes = preg_split('/\s+/', mb_trim((string) $crawler->filter('.sy-pa-page')->attr('class'))) ?: [];

    // The whole key is one token: the grammar emits no whitespace, so nothing
    // splits `sy-pa-resource-record-…` into a class that selects other records.
    expect($classes)->toContain('sy-pa-resource-record-a=7Eb~c=3Dd=20=C5=BE');
});

it('carries a composite key on the wire rather than the record', function (): void {
    [$client] = compositeRecordClient();

    $crawler = $client->request('GET', '/key/imprints/www=2Eexample=2Ecom~=2Fblog=202026/edit');

    // The page is not the first live root on a panel page — the topbar's search
    // component is — so the cold props are read off the page's own element.
    $cold = json_decode(
        (string) $crawler->filter('.sy-pa-page[data-live-props-value]')->attr('data-live-props-value'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    $component = $this->createLiveComponent(
        EditImprint::class,
        ['record' => 'www=2Eexample=2Ecom~=2Fblog=202026', 'panelId' => 'key'],
        client: $client,
    );

    expect($cold['record'])->toBe('www=2Eexample=2Ecom~=2Fblog=202026')
        ->and(compositeRecordProps((string) $component->render())['record'])->toBe('www=2Eexample=2Ecom~=2Fblog=202026');
});

it('accepts a second live request on a composite key and saves through it', function (): void {
    [$client, $entityManager] = compositeRecordClient();

    $component = $this->createLiveComponent(
        EditImprint::class,
        ['record' => 'www=2Eexample=2Ecom~=2Fblog=202026', 'panelId' => 'key'],
        client: $client,
    );

    // The second request re-hydrates the key off the checksummed prop, so a
    // grammar the checksum or the JSON encoder mangled would 400 here rather
    // than write.
    $component->set('data.label', 'Renamed')->call('save');

    $entityManager->clear();

    expect($component->response()->getStatusCode())->toBe(200)
        ->and(compositeRecordProps((string) $component->render())['record'])->toBe('www=2Eexample=2Ecom~=2Fblog=202026')
        ->and($entityManager->find(Imprint::class, ['house' => 'www.example.com', 'year' => '/blog 2026'])?->getLabel())->toBe('Renamed');
});

it('deletes a composite record from the header and redirects to the index', function (): void {
    [$client, $entityManager] = compositeRecordClient();

    compositeRecordPushPanelRequest();

    $host = $this->createLiveComponent(
        EditImprint::class,
        ['record' => 'plain~about', 'panelId' => 'key'],
        client: $client,
    )->component();

    $host->mountAction('delete', [], []);

    $redirect = $host->callMountedAction();

    $entityManager->clear();

    expect($redirect)->toBeInstanceOf(RedirectResponse::class)
        ->and($redirect instanceof RedirectResponse ? $redirect->getTargetUrl() : null)->toEndWith('/key/imprints')
        ->and($entityManager->find(Imprint::class, ['house' => 'plain', 'year' => 'about']))->toBeNull();
});

it('redirects a created composite record to its escaped edit url', function (): void {
    [$client, $entityManager] = compositeRecordClient();

    compositeRecordPushPanelRequest();

    $component = $this->createLiveComponent(CreateImprint::class, ['panelId' => 'key'], client: $client);

    $component->set('data', ['house' => 'new.host', 'year' => '20/26', 'label' => 'Made'])->call('create');

    $entityManager->clear();

    expect($component->response()->isRedirection())->toBeTrue()
        ->and((string) $component->response()->headers->get('Location'))
        ->toBe('http://localhost/key/imprints/new=2Ehost~20=2F26/edit')
        ->and($entityManager->find(Imprint::class, ['house' => 'new.host', 'year' => '20/26'])?->getLabel())->toBe('Made');
});

it('refuses by name a composite create whose form leaves a key half out, writing nothing', function (): void {
    [$client, $entityManager] = compositeRecordClient();

    compositeRecordPushPanelRequest();

    $component = $this->createLiveComponent(CreateYearlessImprint::class, ['panelId' => 'key'], client: $client);
    $component->set('data', ['house' => 'new.host', 'label' => 'Yearless']);

    $recorder = compositeRecordRecorder();

    expect(fn (): mixed => $component->call('create'))->toThrow(new LogicException(sprintf(
        'The resource page [%s] built a [%s] whose identifier is still blank after persist(), so it was not saved. Give every identifier field a form field — a Hidden one for a value the user should not type — or fill it in a prePersist listener, or in handleRecordCreation() before calling the parent.',
        CreateYearlessImprint::class,
        Imprint::class,
    )));

    $entityManager->flush();

    expect(compositeRecordWrites($recorder))->toBe([])
        ->and($entityManager->isOpen())->toBeTrue()
        ->and($entityManager->getRepository(Imprint::class)->count([]))->toBe(3);
});

it('saves a composite create whose key half a global prePersist listener fills, since the page reads the key after persist()', function (): void {
    [$client, $entityManager] = compositeRecordClient();

    compositeRecordPushPanelRequest();

    $listener = new class {
        public function prePersist(PrePersistEventArgs $arguments): void
        {
            $imprint = $arguments->getObject();

            if ($imprint instanceof Imprint && $imprint->getYear() === '') {
                $imprint->setYear('2027');
            }
        }
    };

    $eventManager = $entityManager->getEventManager();
    $eventManager->addEventListener([Events::prePersist], $listener);

    try {
        $this->createLiveComponent(CreateYearlessImprint::class, ['panelId' => 'key'], client: $client)
            ->set('data', ['house' => 'new.host', 'label' => 'Listened'])
            ->call('create');
    } finally {
        $eventManager->removeEventListener([Events::prePersist], $listener);
    }

    expect($entityManager->getConnection()->fetchAllAssociative("SELECT house, year, label FROM Imprint WHERE house = 'new.host'"))
        ->toBe([['house' => 'new.host', 'year' => '2027', 'label' => 'Listened']]);
});

it('mounts a row view modal and a row delete on a composite list through the table arm', function (): void {
    [$client, $entityManager] = compositeRecordClient();

    $view = $this->createLiveComponent(ListImprints::class, ['panelId' => 'key'], client: $client)
        ->call('mountAction', ['name' => 'view', 'arguments' => [], 'context' => ['table' => true, 'recordKey' => 'a=7Eb~c=3Dd=20=C5=BE']])
        ->component();

    expect($view->getMountedActionData(0))->toBe(['house' => 'a~b', 'year' => 'c=d ž', 'label' => 'Exotic'])
        ->and($view->getMountedActionSchema()?->isDisabled())->toBeTrue();

    $delete = $this->createLiveComponent(ListImprints::class, ['panelId' => 'key'], client: $client)
        ->call('mountAction', ['name' => 'delete', 'arguments' => [], 'context' => ['table' => true, 'recordKey' => 'plain~about']]);

    $rows = $delete->call('callMountedAction')->render()->crawler()->filter('tbody .sy-ta-row');

    $entityManager->clear();

    expect($rows->count())->toBe(2)
        ->and($entityManager->find(Imprint::class, ['house' => 'plain', 'year' => 'about']))->toBeNull();
});

it('mounts a composite header action with an empty context, bound to the page record', function (): void {
    [$client] = compositeRecordClient();

    compositeRecordPushPanelRequest();

    $host = $this->createLiveComponent(
        EditImprint::class,
        ['record' => 'www=2Eexample=2Ecom~=2Fblog=202026', 'panelId' => 'key'],
        client: $client,
    )->component();

    // A record with no field named `id` mints nothing for the frame, so the
    // declared record is what the mount binds.
    expect($host->getAction('edit')?->getContext())->toBe([]);

    $host->mountAction('edit', [], []);

    expect($host->getMountedAction()?->getRecord())->toBeInstanceOf(Imprint::class)
        ->and($host->getMountedAction()?->getRecord()?->getLabel())->toBe('Escaped');
});

it('404s for a malformed composite route key without running a statement', function (string $key): void {
    [$client] = compositeRecordClient();
    $recorder = compositeRecordRecorder();

    $client->request('GET', sprintf('/key/imprints/%s/edit', $key));

    expect($client->getResponse()->getStatusCode())->toBe(404)
        ->and($recorder->statements())->toBe([]);
})->with([
    'one component where the identifier takes two' => ['abc'],
    'a blank component' => ['plain~'],
    'three components' => ['plain~about~extra'],
]);

it('404s for a well-formed composite route key no record mints, after asking the database', function (): void {
    [$client] = compositeRecordClient();
    $recorder = compositeRecordRecorder();

    $client->request('GET', '/key/imprints/05~about/edit');

    expect($client->getResponse()->getStatusCode())->toBe(404)
        ->and($recorder->statements())->toHaveCount(1);
});

it('404s when a mounted frame names a malformed composite key, without running a statement', function (): void {
    [$client] = compositeRecordClient();

    $host = $this->createLiveComponent(
        EditImprint::class,
        ['record' => 'plain~about', 'panelId' => 'key'],
        client: $client,
    )->component();

    $recorder = compositeRecordRecorder();

    // The page is no table host, so the frame's key reaches the resource's
    // scoped binding, where a key of the wrong arity answers nothing.
    expect(fn (): mixed => $host->mountAction('edit', [], ['recordKey' => 'abc']))
        ->toThrow(NotFoundHttpException::class, 'No record matching [abc]')
        ->and($recorder->statements())->toBe([]);
});

it('lists and edits a record keyed by one association alone', function (): void {
    [$client] = compositeRecordClient();

    $crawler = $client->request('GET', '/key/colophons');
    $hrefs = array_unique($crawler->filter('tbody a[href*="/edit"]')->each(fn (Crawler $link): string => (string) $link->attr('href')));

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and(array_values($hrefs))->toBe(['http://localhost/key/colophons/1/edit']);

    $client->request('GET', '/key/colophons/1/edit');

    expect($client->getResponse()->getStatusCode())->toBe(200);

    // A single derived identity is keyed by the association's own identifier, so
    // it decodes as strictly as any other: `01` is a second spelling, not a key.
    $client->request('GET', '/key/colophons/01/edit');

    expect($client->getResponse()->getStatusCode())->toBe(404);
});

it('mints no record key for a header action on an identifier whose field is named id', function (): void {
    [$client] = compositeRecordClient();

    $crawler = $client->request('GET', '/key/editions/5~en-GB/edit');
    $trigger = $crawler->filter('.sy-pa-header-actions button')->first();

    compositeRecordPushPanelRequest();

    $host = $this->createLiveComponent(
        EditEdition::class,
        ['record' => '5~en-GB', 'panelId' => 'key'],
        client: $client,
    )->component();

    // `id` is one field of two, so the literal reading would mint the partial
    // key `5` — which the strict decode refuses. Nothing is minted instead, and
    // the record the action declares stands.
    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($trigger->attr('data-live-name-param'))->toBe('edit')
        ->and($trigger->attr('data-live-context-param'))->toBeNull()
        ->and($host->getAction('edit')?->getContext())->toBe([]);

    // The frame is mounted with what the rendered trigger carries, the way a
    // click sends it, rather than with a context written by hand.
    $host->mountAction(
        (string) $trigger->attr('data-live-name-param'),
        [],
        json_decode($trigger->attr('data-live-context-param') ?? '[]', true, flags: JSON_THROW_ON_ERROR),
    );

    expect($host->getMountedAction()?->getRecord())->toBeInstanceOf(Edition::class)
        ->and($host->getMountedAction()?->getRecord()?->getTitle())->toBe('Fifth');
});

it('refuses an edited key field under the field, writes nothing and keeps the page on its key', function (): void {
    [$client, $entityManager] = compositeRecordClient();

    $component = $this->createLiveComponent(
        EditImprint::class,
        ['record' => 'plain~about', 'panelId' => 'key'],
        client: $client,
    );

    $recorder = compositeRecordRecorder();
    $client->catchExceptions(true);

    $component->set('data.house', 'moved')->call('save');

    $html = (string) $client->getResponse()->getContent();

    expect($client->getResponse()->getStatusCode())->toBe(422)
        ->and(compositeRecordFieldErrors($html))->toBe(['data.house' => ['This value cannot be changed.']])
        ->and(compositeRecordProps($html)['record'])->toBe('plain~about')
        ->and(compositeRecordWrites($recorder))->toBe([]);

    // The refused key was put back in memory, so a later flush in the same
    // request writes nothing either.
    $entityManager->flush();
    $entityManager->clear();

    expect(compositeRecordWrites($recorder))->toBe([])
        ->and($entityManager->find(Imprint::class, ['house' => 'plain', 'year' => 'about'])?->getLabel())->toBe('Plain')
        ->and($entityManager->find(Imprint::class, ['house' => 'moved', 'year' => 'about']))->toBeNull();
});

it('refuses a composite record on a taken key under every key field the form maps, writing nothing', function (): void {
    [$client, $entityManager] = compositeRecordClient();

    compositeRecordPushPanelRequest();

    $component = $this->createLiveComponent(CreateImprint::class, ['panelId' => 'key'], client: $client);
    $component->set('data', ['house' => 'plain', 'year' => 'about', 'label' => 'Duplicate']);

    $recorder = compositeRecordRecorder();
    $client->catchExceptions(true);

    $component->call('create');

    $html = (string) $client->getResponse()->getContent();

    expect($client->getResponse()->getStatusCode())->toBe(422)
        ->and(compositeRecordFieldErrors($html))->toBe([
            'data.house' => ['This value is already used.'],
            'data.year' => ['This value is already used.'],
        ])
        ->and(mb_substr_count($html, 'This value is already used.'))->toBe(2)
        ->and(compositeRecordWrites($recorder))->toBe([])
        ->and($entityManager->isOpen())->toBeTrue()
        ->and($entityManager->getRepository(Imprint::class)->count([]))->toBe(3);
});
