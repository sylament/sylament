<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Catalogue;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Imprint;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Stamp;
use Sylament\Panels\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditCatalogue;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\ListImprints;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\ManageCatalogueStamps;
use Sylament\Panels\Tests\Fixtures\Resources\RelationManagers\PlainStampsRelationManager;
use Sylament\Support\Tests\Doctrine\RecordingMiddleware;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * A row's edit modal handing its record a new key, on every host a resource
 * puts a table on: a resource list, a relation manager and a related-records
 * page. The key fields are ordinary inputs in these forms, so what is pinned
 * is the server's answer to a request that writes one.
 *
 * Seeded: the imprints `plain~about` and `plain~taken`, and under catalogue `1`
 * the stamps `AB-1` and `AB-2` (`1~AB-1`, `1~AB-2`). Every key is a literal.
 * Kernel reboot is disabled because the fixture database lives in memory on a
 * container service.
 */

/**
 * @return array{KernelBrowser, EntityManagerInterface, RecordingMiddleware}
 */
function compositeEditActionClient(): array
{
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);

    $catalogue = new Catalogue()->setTitle('First');

    EntityManagerFactory::seed(
        $entityManager,
        $catalogue,
        new Imprint()->setHouse('plain')->setYear('about')->setLabel('Plain'),
        new Imprint()->setHouse('plain')->setYear('taken')->setLabel('Taken'),
        new Stamp()->setCatalogue($catalogue)->setCode('AB-1')->setCaption('One'),
        new Stamp()->setCatalogue($catalogue)->setCode('AB-2')->setCaption('Two'),
    );

    $recorder = test()->getContainer()->get(RecordingMiddleware::class);
    assert($recorder instanceof RecordingMiddleware);

    return [$client, $entityManager, $recorder];
}

/**
 * @return array<string, list<string>>
 */
function compositeEditActionFieldErrors(string $html): array
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
 * The record keys the re-rendered table carries its rows under.
 *
 * @return list<string>
 */
function compositeEditActionRowKeys(string $html): array
{
    preg_match_all('/id="[^"]*\.table\.records\.([^"]+)"/', $html, $matches);

    return array_values(array_unique($matches[1]));
}

it("refuses a row edit that moves its record's key under the field, the row still keyed as it was", function (string $host, array $parameters, string $recordKey, string $path, string $value, array $rows, string $class, array $identifier): void {
    [$client, $entityManager, $recorder] = compositeEditActionClient();

    $component = $this->createLiveComponent($host, $parameters, client: $client)
        ->call('mountAction', ['name' => 'edit', 'arguments' => [], 'context' => ['table' => true, 'recordKey' => $recordKey]]);
    $component->set($path, $value);

    $recorder->reset();
    $client->catchExceptions(true);

    $component->call('callMountedAction');

    $html = (string) $client->getResponse()->getContent();

    expect($client->getResponse()->getStatusCode())->toBe(422)
        ->and(compositeEditActionFieldErrors($html))->toBe([$path => ['This value cannot be changed.']])
        ->and(compositeEditActionRowKeys($html))->toBe($rows)
        ->and(array_filter($recorder->statements(), static fn (string $sql): bool => ! str_starts_with($sql, 'SELECT')))->toBe([]);

    $entityManager->clear();

    expect($entityManager->find($class, $identifier))->toBeInstanceOf($class);
})->with([
    'a resource list' => [ListImprints::class, ['panelId' => 'key'], 'plain~about', 'mountedActionsData.0.house', 'moved', ['plain~about', 'plain~taken'], Imprint::class, ['house' => 'plain', 'year' => 'about']],
    'a resource list, onto a taken key' => [ListImprints::class, ['panelId' => 'key'], 'plain~about', 'mountedActionsData.0.year', 'taken', ['plain~about', 'plain~taken'], Imprint::class, ['house' => 'plain', 'year' => 'about']],
    'a relation manager' => [PlainStampsRelationManager::class, ['ownerRecord' => '1', 'pageClass' => EditCatalogue::class], '1~AB-1', 'mountedActionsData.0.code', 'AB-9', ['1~AB-1', '1~AB-2'], Stamp::class, ['catalogue' => 1, 'code' => 'AB-1']],
    'a relation manager, onto a taken key' => [PlainStampsRelationManager::class, ['ownerRecord' => '1', 'pageClass' => EditCatalogue::class], '1~AB-1', 'mountedActionsData.0.code', 'AB-2', ['1~AB-1', '1~AB-2'], Stamp::class, ['catalogue' => 1, 'code' => 'AB-1']],
    'a related-records page' => [ManageCatalogueStamps::class, ['record' => '1', 'panelId' => 'key'], '1~AB-1', 'mountedActionsData.0.code', 'AB-9', ['1~AB-1', '1~AB-2'], Stamp::class, ['catalogue' => 1, 'code' => 'AB-1']],
]);
