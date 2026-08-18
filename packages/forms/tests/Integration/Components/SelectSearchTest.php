<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Forms\Components\Select;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Author;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;
use Symfony\UX\LiveComponent\Test\TestLiveComponent;

uses(InteractsWithLiveComponents::class);

/*
 * The searchable select's browser-facing seam, on the same live host the option
 * actions run on: `callSchemaComponentMethod` reaches the field by its absolute
 * key, calls one `#[ExposedLiveComponentMethod]` method on it and hands the
 * answer back through the `sylament:schema-component-method-result` browser
 * event — the whole round trip the JS control makes. Kernel reboot is disabled
 * throughout, because the fixture database is an in-memory SQLite a container
 * service builds and a rebooted kernel would hand each request an empty one.
 */

/**
 * @return array{KernelBrowser, EntityManagerInterface}
 */
function searchableSelectClient(): array
{
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    return [$client, test()->getContainer()->get(EntityManagerInterface::class)];
}

/**
 * @return array{Author, Author}
 */
function seedSearchableAuthors(EntityManagerInterface $entityManager): array
{
    $authors = [];

    foreach (['Ada', 'Bob'] as $name) {
        $author = new Author();
        $author->setName($name);

        $entityManager->persist($author);

        $authors[] = $author;
    }

    $entityManager->flush();

    return [$authors[0], $authors[1]];
}

/**
 * The one call the JS control makes, and the event it reads its answer off.
 *
 * @param array<string, mixed> $arguments
 *
 * @return ?array{event: non-empty-string, payload: array<string, mixed>}
 */
function callSelectMethod(TestLiveComponent $component, string $method, array $arguments = []): ?array
{
    $component->call('callSchemaComponentMethod', [
        'componentKey' => 'schema.author',
        'method' => $method,
        'arguments' => $arguments,
    ]);

    return $component->getDispatchedBrowserEvent($component->render(), 'sylament:schema-component-method-result');
}

it('answers a search with the matching rows in the shape the JS control speaks', function (): void {
    [$client, $entityManager] = searchableSelectClient();

    [$ada] = seedSearchableAuthors($entityManager);

    $component = $this->createLiveComponent('SelectOptionForm', ['isSearchable' => true], client: $client);

    $event = callSelectMethod($component, 'getSearchResultsForJs', ['search' => 'Ad']);

    expect($event)->not->toBeNull()
        ->and($event['payload'])->toBe([
            'componentKey' => 'schema.author',
            'method' => 'getSearchResultsForJs',
            'result' => [
                ['label' => 'Ada', 'value' => (string) $ada->getId(), 'isDisabled' => false],
            ],
        ]);
});

it('answers a search that matches nothing with an empty result', function (): void {
    [$client, $entityManager] = searchableSelectClient();

    seedSearchableAuthors($entityManager);

    $component = $this->createLiveComponent('SelectOptionForm', ['isSearchable' => true], client: $client);

    $event = callSelectMethod($component, 'getSearchResultsForJs', ['search' => 'Zoë']);

    expect($event['payload']['result'])->toBe([]);
});

it("answers the selected option's label", function (): void {
    [$client, $entityManager] = searchableSelectClient();

    [, $bob] = seedSearchableAuthors($entityManager);

    $component = $this->createLiveComponent('SelectOptionForm', ['isSearchable' => true], client: $client);
    $component->set('data', ['author' => (string) $bob->getId()]);

    $event = callSelectMethod($component, 'getOptionLabel');

    expect($event['payload']['result'])->toBe('Bob');
});

it('answers null for a method the select does not expose', function (): void {
    [$client, $entityManager] = searchableSelectClient();

    seedSearchableAuthors($entityManager);

    $component = $this->createLiveComponent('SelectOptionForm', ['isSearchable' => true], client: $client);

    $event = callSelectMethod($component, 'getOptions');

    expect($event)->not->toBeNull()
        ->and($event['payload']['result'])->toBeNull();
});

it('enumerates nothing but the selected option while the select is searchable and not preloaded', function (): void {
    [$client, $entityManager] = searchableSelectClient();

    [$ada] = seedSearchableAuthors($entityManager);

    $component = $this->createLiveComponent('SelectOptionForm', ['isSearchable' => true], client: $client);
    $html = (string) $component->set('data', ['author' => (string) $ada->getId()])->render();

    $select = $component->component()->getSchemaComponent('schema.author');

    expect($select)->toBeInstanceOf(Select::class)
        ->and($select->getExtraTemplateData())->toBe([
            'options' => [],
            'optionsForJs' => [],
            'selectedOptions' => [['value' => (string) $ada->getId(), 'label' => 'Ada']],
            'isNative' => false,
        ])
        // The row the search did not ask for never reaches the client.
        ->and($html)->not->toContain('Bob');
});
