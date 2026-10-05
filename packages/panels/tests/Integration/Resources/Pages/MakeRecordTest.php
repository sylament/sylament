<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Countermark;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Mill;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Watermark;
use Sylament\Panels\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Panels\Tests\Fixtures\Resources\CountermarkResource;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\CreateCountermark;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\CreateCountermarkAsMill;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\CreateCountermarkUnderAnotherMill;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\CreateWatermark;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\CreateWatermarkAsDraft;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\CreateWatermarkByDefault;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditWatermark;
use Sylament\Panels\Tests\Fixtures\Resources\WatermarkDraft;
use Sylament\Panels\Tests\Fixtures\Resources\WatermarkResource;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * The create page builds its record in makeRecord(). On the `make` panel a
 * watermark is keyed by a mill and a pattern its constructor lower-cases and no
 * setter can change, and a countermark by the mill it nests under and its
 * initials, both set by its constructor. Mill "First" is `1` and "Other" `2`,
 * and the pair arches/laid is stored. Kernel reboot is disabled because the
 * fixture database lives in memory on a container service; no expectation
 * reads a platform-specific value, so every case runs on all three platforms.
 */

beforeEach(function (): void {
    CreateWatermark::$madeRecords = 0;
    CreateCountermark::$madeRecords = 0;

    CreateWatermarkAsDraft::$draft = null;
});

/**
 * @return array{KernelBrowser, EntityManagerInterface}
 */
function madeRecordClient(): array
{
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);

    EntityManagerFactory::seed(
        $entityManager,
        new Mill()->setName('First'),
        new Mill()->setName('Other'),
        new Watermark('arches', 'laid')->setNote('Stored'),
    );

    // A page is mounted in-process before its first live request, so the
    // panel it belongs to and the session a toast flashes into stand on the
    // stack the way a routed request would carry them.
    $request = new Request();
    $request->attributes->set('_sylament_panel', 'make');
    $request->setSession(test()->getContainer()->get('session.factory')->createSession());

    test()->getContainer()->get(RequestStack::class)->push($request);

    return [$client, $entityManager];
}

/**
 * Every field error on the page, keyed by the live model of the input its
 * field wrapper holds.
 *
 * @return array<string, list<string>>
 */
function madeRecordFieldErrors(string $html): array
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
function madeRecordRows(string $sql): array
{
    return test()->getContainer()->get(EntityManagerInterface::class)->getConnection()->fetchAllAssociative($sql);
}

it('creates a record whose constructor takes its key, building it once in the makeRecord() the page overrides', function (): void {
    [$client] = madeRecordClient();

    $component = $this->createLiveComponent(CreateWatermark::class, ['panelId' => 'make'], client: $client);
    $component->set('data', ['mill' => 'fabriano', 'pattern' => 'wove', 'note' => 'Made'])->call('create');

    expect($component->response()->isRedirection())->toBeTrue()
        ->and((string) $component->response()->headers->get('Location'))->toBe('http://localhost/make/watermarks/fabriano~wove/edit')
        ->and(CreateWatermark::$madeRecords)->toBe(1)
        ->and(madeRecordRows('SELECT mill, pattern, note FROM Watermark ORDER BY mill'))->toBe([
            ['mill' => 'arches', 'pattern' => 'laid', 'note' => 'Stored'],
            ['mill' => 'fabriano', 'pattern' => 'wove', 'note' => 'Made'],
        ]);
});

it('refuses a taken pair under both key fields, re-rendering the form bound to its record class again', function (): void {
    [$client] = madeRecordClient();

    $component = $this->createLiveComponent(CreateWatermark::class, ['panelId' => 'make'], client: $client);
    $component->set('data', ['mill' => 'arches', 'pattern' => 'laid', 'note' => 'Duplicate']);

    $client->catchExceptions(true);

    $component->call('create');

    $html = (string) $client->getResponse()->getContent();

    // The hint shows only while the form is bound to no record, so it is still
    // there only if the record the mapping bound was put back.
    expect($client->getResponse()->getStatusCode())->toBe(422)
        ->and(madeRecordFieldErrors($html))->toBe([
            'data.mill' => ['This value is already used.'],
            'data.pattern' => ['This value is already used.'],
        ])
        ->and(new Crawler($html)->filter('input[data-model$="data.hint"]')->count())->toBe(1)
        ->and(new Crawler($html)->filter('input[data-model$="data.classHint"]')->count())->toBe(1)
        ->and(CreateWatermark::$madeRecords)->toBe(1)
        ->and(madeRecordRows('SELECT mill, pattern, note FROM Watermark'))->toBe([['mill' => 'arches', 'pattern' => 'laid', 'note' => 'Stored']]);
});

it('refuses by name a key the constructor keeps in another spelling than the form typed, writing nothing', function (): void {
    [$client] = madeRecordClient();

    $component = $this->createLiveComponent(CreateWatermark::class, ['panelId' => 'make'], client: $client);
    $component->set('data', ['mill' => 'Fabriano', 'pattern' => 'wove', 'note' => 'Shouted']);

    expect(fn (): mixed => $component->call('create'))->toThrow(new LogicException(sprintf(
        "Cannot map state path \"mill\" onto %s: no setter can change that property, and it already holds another value (the record holds [string], the write [string]). Hand the constructor the form's value as it is, make the field's state match what the constructor keeps with dehydrateStateUsing(), or leave the field out of the form.",
        Watermark::class,
    )));

    expect(madeRecordRows('SELECT mill, pattern, note FROM Watermark'))->toBe([['mill' => 'arches', 'pattern' => 'laid', 'note' => 'Stored']]);
});

it('refuses by name a record class whose constructor takes arguments while the page keeps the default makeRecord()', function (): void {
    [$client] = madeRecordClient();

    $component = $this->createLiveComponent(CreateWatermarkByDefault::class, ['panelId' => 'make'], client: $client);
    $component->set('data', ['mill' => 'fabriano', 'pattern' => 'wove', 'note' => 'Default']);

    expect(fn (): mixed => $component->call('create'))->toThrow(new LogicException(sprintf(
        'Cannot map a new record on the resource page [%s]: %s requires constructor arguments, so the mapper cannot build it. Give the constructor defaults, or override makeRecord() to build it from the form data.',
        CreateWatermarkByDefault::class,
        Watermark::class,
    )));

    expect(madeRecordRows('SELECT mill, pattern, note FROM Watermark'))->toBe([['mill' => 'arches', 'pattern' => 'laid', 'note' => 'Stored']]);
});

it('refuses a makeRecord() that returns another class, before mapping anything', function (): void {
    [$client] = madeRecordClient();

    $draftPage = $this->createLiveComponent(CreateWatermarkAsDraft::class, ['panelId' => 'make'], client: $client);
    $draftPage->set('data', ['mill' => 'fabriano', 'pattern' => 'wove', 'note' => 'Drafted']);

    expect(fn (): mixed => $draftPage->call('create'))->toThrow(new LogicException(sprintf(
        'The resource page [%s] built a [%s] in makeRecord(), but its resource [%s] creates [%s] records, so the form was not mapped onto it. Answer a new [%s] from makeRecord().',
        CreateWatermarkAsDraft::class,
        WatermarkDraft::class,
        WatermarkResource::class,
        Watermark::class,
        Watermark::class,
    )));

    // Every property of the draft is writable, so a mapping would have filled it.
    expect(CreateWatermarkAsDraft::$draft)->toEqual(new WatermarkDraft())
        ->and(madeRecordRows('SELECT mill FROM Watermark'))->toBe([['mill' => 'arches']]);

    $parentPage = $this->createLiveComponent(CreateCountermarkAsMill::class, [
        'parentRecords' => ['mill' => '1'],
        'panelId' => 'make',
    ], client: $client);
    $parentPage->set('data', ['initials' => 'AB', 'caption' => 'Parent']);

    expect(fn (): mixed => $parentPage->call('create'))->toThrow(new LogicException(sprintf(
        'The resource page [%s] built a [%s] in makeRecord(), but its resource [%s] creates [%s] records, so the form was not mapped onto it. Answer a new [%s] from makeRecord().',
        CreateCountermarkAsMill::class,
        Mill::class,
        CountermarkResource::class,
        Countermark::class,
        Countermark::class,
    )));

    expect(madeRecordRows('SELECT name FROM Mill ORDER BY name'))->toBe([['name' => 'First'], ['name' => 'Other']])
        ->and(madeRecordRows('SELECT initials FROM Countermark'))->toBe([]);
});

it('creates a nested record whose constructor takes its parent, keeping that parent through both attaches and building it once', function (): void {
    [$client, $entityManager] = madeRecordClient();

    $component = $this->createLiveComponent(CreateCountermark::class, [
        'parentRecords' => ['mill' => '1'],
        'panelId' => 'make',
    ], client: $client);
    $component->set('data', ['initials' => 'AB', 'caption' => 'Under the first'])->call('create');

    $entityManager->clear();

    // The resource has no edit page and its parent no record page, so the
    // create lands on the parent's listing.
    expect($component->response()->isRedirection())->toBeTrue()
        ->and((string) $component->response()->headers->get('Location'))->toBe('http://localhost/make/mills')
        ->and(CreateCountermark::$madeRecords)->toBe(1)
        ->and(madeRecordRows('SELECT initials, caption FROM Countermark'))->toBe([['initials' => 'AB', 'caption' => 'Under the first']])
        ->and($entityManager->find(Countermark::class, ['mill' => 1, 'initials' => 'AB'])?->getMill()->getName())->toBe('First');
});

it('refuses a made record whose constructor set another parent', function (): void {
    [$client] = madeRecordClient();

    $component = $this->createLiveComponent(CreateCountermarkUnderAnotherMill::class, [
        'parentRecords' => ['mill' => '1'],
        'panelId' => 'make',
    ], client: $client);
    $component->set('data', ['initials' => 'AB', 'caption' => 'Misplaced']);

    expect(fn (): mixed => $component->call('create'))->toThrow(new LogicException(sprintf(
        'Cannot write association "mill" onto %s: no setter can change that property, and it already holds another value (the record holds [%s], the write [%s]). Build the record around the record it is attached to, or give the property a setter.',
        Countermark::class,
        Mill::class,
        Mill::class,
    )));

    expect(madeRecordRows('SELECT initials FROM Countermark'))->toBe([]);
});

it('saves an edit whose form maps the key no setter can change, as the record holds it', function (): void {
    [$client] = madeRecordClient();

    $component = $this->createLiveComponent(EditWatermark::class, ['record' => 'arches~laid', 'panelId' => 'make'], client: $client);
    $component->set('data', ['mill' => 'arches', 'pattern' => 'laid', 'note' => 'Edited'])->call('save');

    expect($component->response()->getStatusCode())->toBe(200)
        ->and(madeRecordRows('SELECT mill, pattern, note FROM Watermark'))->toBe([['mill' => 'arches', 'pattern' => 'laid', 'note' => 'Edited']]);
});

it('refuses by name an edit that moves the key no setter can change, writing nothing', function (): void {
    [$client] = madeRecordClient();

    $component = $this->createLiveComponent(EditWatermark::class, ['record' => 'arches~laid', 'panelId' => 'make'], client: $client);
    $component->set('data', ['mill' => 'fabriano', 'pattern' => 'laid', 'note' => 'Moved']);

    expect(fn (): mixed => $component->call('save'))->toThrow(new LogicException(sprintf(
        "Cannot map state path \"mill\" onto %s: no setter can change that property, and it already holds another value (the record holds [string], the write [string]). Hand the constructor the form's value as it is, make the field's state match what the constructor keeps with dehydrateStateUsing(), or leave the field out of the form.",
        Watermark::class,
    )));

    expect(madeRecordRows('SELECT mill, pattern, note FROM Watermark'))->toBe([['mill' => 'arches', 'pattern' => 'laid', 'note' => 'Stored']]);
});
