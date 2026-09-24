<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Catalogue;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Colophon;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Imprint;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Stamp;
use Sylament\Panels\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditCatalogue;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\ListColophons;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\ListImprints;
use Sylament\Panels\Tests\Fixtures\Resources\RelationManagers\PlainStampsRelationManager;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * A replicate over an assigned key, on the two hosts a resource puts it on: a
 * list row and a relation-manager row. The modal is filled from the original,
 * key included, so the copy is only written once the user changes it.
 *
 * Catalogue "First" is `1`; under it sit the colophon keyed by it alone and the
 * stamp `A/B C` (`1~A=2FB=20C`). Every key is a literal. Kernel reboot is
 * disabled because the fixture database lives in memory on a container
 * service.
 */

/**
 * @return array{KernelBrowser, EntityManagerInterface}
 */
function compositeReplicateClient(): array
{
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);

    $first = new Catalogue()->setTitle('First');

    EntityManagerFactory::seed(
        $entityManager,
        $first,
        new Colophon()->setCatalogue($first)->setNote('Note'),
        new Imprint()->setHouse('plain')->setYear('about')->setLabel('Plain'),
        new Stamp()->setCatalogue($first)->setCode('A/B C')->setCaption('Alpha'),
    );

    return [$client, $entityManager];
}

/**
 * @return array<string, list<string>>
 */
function compositeReplicateFieldErrors(string $html): array
{
    $errors = [];

    new Crawler($html)->filter('li.sy-fo-field-error-message')->each(function (Crawler $message) use (&$errors): void {
        $wrapper = $message->ancestors()->filter('[data-field-wrapper]')->first();
        $model = explode('|', $wrapper->filter('[data-model]')->first()->attr('data-model') ?? '');

        $errors[end($model)][] = mb_trim($message->text());
    });

    return $errors;
}

it('replicates a scalar pair from a list row through the resource form, the key taken from the modal', function (): void {
    [$client, $entityManager] = compositeReplicateClient();

    $component = $this->createLiveComponent(ListImprints::class, ['panelId' => 'key'], client: $client)
        ->call('mountAction', ['name' => 'replicate', 'arguments' => [], 'context' => ['table' => true, 'recordKey' => 'plain~about']]);

    expect($component->component()->getMountedActionData(0))->toBe(['house' => 'plain', 'year' => 'about', 'label' => 'Plain']);

    $component->set('mountedActionsData.0.year', 'later')->call('callMountedAction');

    $entityManager->clear();

    expect($entityManager->find(Imprint::class, ['house' => 'plain', 'year' => 'later'])?->getLabel())->toBe('Plain')
        ->and($entityManager->getRepository(Imprint::class)->count([]))->toBe(2);
});

it('replicates a derived identity inside its own owner from a relation manager, keeping the parent', function (): void {
    [$client, $entityManager] = compositeReplicateClient();

    $this->createLiveComponent(PlainStampsRelationManager::class, ['ownerRecord' => '1', 'pageClass' => EditCatalogue::class], client: $client)
        ->call('mountAction', ['name' => 'replicate', 'arguments' => [], 'context' => ['table' => true, 'recordKey' => '1~A=2FB=20C']])
        ->set('mountedActionsData.0.code', 'COPY')
        ->call('callMountedAction');

    $entityManager->clear();

    expect($entityManager->find(Stamp::class, ['catalogue' => 1, 'code' => 'COPY'])?->getCaption())->toBe('Alpha')
        ->and($entityManager->getRepository(Stamp::class)->count(['catalogue' => 1]))->toBe(2);
});

it("answers a replica left on its original's key under the first key field the modal lets the user type", function (string $host, array $parameters, string $recordKey, string $path, string $class): void {
    [$client, $entityManager] = compositeReplicateClient();

    $component = $this->createLiveComponent($host, $parameters, client: $client)
        ->call('mountAction', ['name' => 'replicate', 'arguments' => [], 'context' => ['table' => true, 'recordKey' => $recordKey]]);

    $client->catchExceptions(true);
    $component->call('callMountedAction');

    $html = (string) $client->getResponse()->getContent();

    expect($client->getResponse()->getStatusCode())->toBe(422)
        ->and(compositeReplicateFieldErrors($html))->toBe([$path => ['This value is already used.']])
        ->and($entityManager->isOpen())->toBeTrue()
        ->and($entityManager->getRepository($class)->count([]))->toBe(1);
})->with([
    'a scalar pair on a list row' => [ListImprints::class, ['panelId' => 'key'], 'plain~about', 'mountedActionsData.0.house', Imprint::class],
    'a derived identity on a relation-manager row' => [PlainStampsRelationManager::class, ['ownerRecord' => '1', 'pageClass' => EditCatalogue::class], '1~A=2FB=20C', 'mountedActionsData.0.code', Stamp::class],
]);

it('refuses by name a bare replicate of a single derived identity, which no modal can re-key', function (): void {
    [$client, $entityManager] = compositeReplicateClient();

    $component = $this->createLiveComponent(ListColophons::class, ['panelId' => 'key'], client: $client)
        ->call('mountAction', ['name' => 'replicate', 'arguments' => [], 'context' => ['table' => true, 'recordKey' => '1']]);

    expect(fn (): mixed => $component->call('callMountedAction'))
        ->toThrow(LogicException::class, 'The [replicate] action built a replica of [Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Colophon] whose identifier is incomplete');

    $entityManager->clear();

    expect($entityManager->getRepository(Colophon::class)->count([]))->toBe(1);
});
