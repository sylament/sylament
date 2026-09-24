<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Panels\Tests\Fixtures\Doctrine\AccessionNumberGenerator;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Accession;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Catalogue;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Edition;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Reprint;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Shelfmark;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Stamp;
use Sylament\Panels\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Panels\Tests\Fixtures\Doctrine\RecordingMiddleware;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditAccession;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditEdition;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditFlatStamp;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditReprint;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditShelfmark;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * An edit page whose form hands the record a new identifier. The key fields
 * here are ordinary inputs on purpose: `disabledOn('edit')` is the interface,
 * and what these cases pin is the server's answer when a request writes the
 * key anyway — under every generator, a database-generated id the form happens
 * to expose included.
 *
 * Seeded: shelfmarks `alpha` and `beta` (assigned), accessions `A-1` and `A-2`
 * (a custom generator), reprints `1` and `2` (the database's), the edition
 * `5~en`, and under catalogue `1` the stamp `AB-1`, which the column stores as
 * `ab-1`. Every key is a literal. Kernel reboot is disabled because the
 * fixture database lives in memory on a container service.
 */

/**
 * @return array{KernelBrowser, EntityManagerInterface, RecordingMiddleware}
 */
function changedKeyClient(): array
{
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);

    AccessionNumberGenerator::$next = 1;

    $catalogue = new Catalogue()->setTitle('First');

    EntityManagerFactory::seed(
        $entityManager,
        $catalogue,
        new Stamp()->setCatalogue($catalogue)->setCode('AB-1')->setCaption('One'),
        new Shelfmark()->setCode('alpha')->setLabel('A'),
        new Shelfmark()->setCode('beta')->setLabel('B'),
        new Accession()->setLabel('First'),
        new Accession()->setLabel('Second'),
        new Reprint()->setLabel('First'),
        new Reprint()->setLabel('Second'),
        new Edition()->setId(5)->setLocale('en')->setTitle('Fifth'),
    );

    $recorder = test()->getContainer()->get(RecordingMiddleware::class);
    assert($recorder instanceof RecordingMiddleware);

    return [$client, $entityManager, $recorder];
}

/**
 * @return array<string, list<string>>
 */
function changedKeyFieldErrors(string $html): array
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
 * @return array<string, mixed>
 */
function changedKeyProps(string $html): array
{
    preg_match('/data-live-props-value="([^"]*)"/', $html, $matches);

    $props = json_decode(html_entity_decode($matches[1] ?? ''), true, flags: JSON_THROW_ON_ERROR);

    expect($props)->toBeArray();

    return $props;
}

/**
 * @return list<string>
 */
function changedKeyWrites(RecordingMiddleware $recorder): array
{
    return array_values(array_filter($recorder->statements(), static fn (string $sql): bool => ! str_starts_with($sql, 'SELECT')));
}

it('refuses an edited identifier under its field and writes nothing, whatever generator fills it', function (string $page, string $panel, string $key, string $path, string $value, string $class, mixed $identifier): void {
    [$client, $entityManager, $recorder] = changedKeyClient();

    $component = $this->createLiveComponent($page, ['record' => $key, 'panelId' => $panel], client: $client);
    $component->set($path, $value);

    $recorder->reset();
    $client->catchExceptions(true);

    $component->call('save');

    $html = (string) $client->getResponse()->getContent();

    expect($client->getResponse()->getStatusCode())->toBe(422)
        ->and(changedKeyFieldErrors($html))->toBe([$path => ['This value cannot be changed.']])
        ->and(changedKeyProps($html)['record'])->toBe($key)
        ->and($entityManager->isOpen())->toBeTrue();

    // The old key was put back in memory, so a later flush in the same request
    // writes nothing either.
    $entityManager->flush();
    $entityManager->clear();

    expect(changedKeyWrites($recorder))->toBe([])
        ->and($entityManager->find($class, $identifier))->toBeInstanceOf($class);
})->with([
    'an assigned string' => [EditShelfmark::class, 'write', 'alpha', 'data.code', 'gamma', Shelfmark::class, 'alpha'],
    'an assigned string, onto a taken key' => [EditShelfmark::class, 'write', 'alpha', 'data.code', 'beta', Shelfmark::class, 'alpha'],
    'a custom generator' => [EditAccession::class, 'write', 'A-1', 'data.id', 'A-9', Accession::class, 'A-1'],
    'a custom generator, onto a taken key' => [EditAccession::class, 'write', 'A-1', 'data.id', 'A-2', Accession::class, 'A-1'],
    'a database-generated integer' => [EditReprint::class, 'write', '1', 'data.id', '99', Reprint::class, 1],
    'a database-generated integer, onto a taken key' => [EditReprint::class, 'write', '1', 'data.id', '2', Reprint::class, 1],
    'another spelling of a converted key' => [EditFlatStamp::class, 'key', '1~AB-1', 'data.code', 'ab-1', Stamp::class, ['catalogue' => 1, 'code' => 'AB-1']],
]);

it('saves the page it refused once the key is put back', function (): void {
    [$client, $entityManager] = changedKeyClient();

    $component = $this->createLiveComponent(EditShelfmark::class, ['record' => 'alpha', 'panelId' => 'write'], client: $client);
    $component->set('data.code', 'gamma');

    $client->catchExceptions(true);
    $component->call('save');

    expect($client->getResponse()->getStatusCode())->toBe(422);

    $component->set('data.code', 'alpha')->set('data.label', 'Relabelled')->call('save');

    $entityManager->clear();

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($entityManager->find(Shelfmark::class, 'alpha')?->getLabel())->toBe('Relabelled')
        ->and($entityManager->find(Shelfmark::class, 'gamma'))->toBeNull();
});

it('takes the same identifier written back in another type as no change', function (string $page, string $panel, string $key, string $path, string $value, string $field, array $statements): void {
    [$client, , $recorder] = changedKeyClient();

    $component = $this->createLiveComponent($page, ['record' => $key, 'panelId' => $panel], client: $client);
    $component->set($path, $value)->set('data.' . $field, 'Retitled');

    $recorder->reset();
    $component->call('save');

    expect($component->response()->getStatusCode())->toBe(200)
        ->and(changedKeyWrites($recorder))->toBe($statements);
})->with([
    'an integer half posted as a string' => [EditEdition::class, 'key', '5~en', 'data.id', '5', 'title', ['BEGIN', 'UPDATE Edition SET title = ? WHERE id = ? AND locale = ?', 'COMMIT']],
    'a generated integer posted as a string' => [EditReprint::class, 'write', '1', 'data.id', '1', 'label', ['BEGIN', 'UPDATE Reprint SET label = ? WHERE id = ?', 'COMMIT']],
]);
