<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Integration\Concerns;

use Doctrine\ORM\EntityManager;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Group;
use Sylament\Schemas\Exceptions\ValidationException;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\AssociationReader;
use Sylament\Schemas\Tests\Fixtures\Components\StateField;
use Sylament\Schemas\Tests\Fixtures\Concerns\StateHostFixture;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Headband;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\LintelHolder;
use Sylament\Schemas\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Support\Tests\Doctrine\RecordingMiddleware;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/*
 * Where the record check files a violation: under the component that maps its
 * path when that component shows its own errors, and at the schema's own path
 * — which the page and the action modal render above the form — when the
 * deepest component mapping it is a layout component entangling a related
 * record, which shows none.
 */

/**
 * A `data`-bag host validating through the container's validator, its schema
 * bound to `$record` and every relationship read through `$entityManager`.
 *
 * @return array{Schema, StateHostFixture}
 */
function recordErrorPlacementSchema(object $record, EntityManager $entityManager, Component ...$components): array
{
    $reader = new AssociationReader($entityManager);
    $host = new StateHostFixture();
    $host->setSchemaStateValidator(test()->getContainer()->get(ValidatorInterface::class));

    foreach ($components as $component) {
        if ($component instanceof Group) {
            $component->setAssociationReader($reader);
        }
    }

    $schema = Schema::make($host)->statePath('data')->record($record)->components($components);
    $schema->setAssociationReader($reader);

    $host->cacheSchema('schema', $schema);
    $schema->fill($record);

    return [$schema, $host];
}

/**
 * @return array<string, list<string>>
 */
function recordErrorPlacementErrors(Schema $schema): array
{
    try {
        $schema->getData();
    } catch (ValidationException $validationException) {
        return $validationException->getErrors();
    }

    return [];
}

it("files the blank-key refusal at the schema's own path when only a layout component entangling the related record maps its key", function (): void {
    $recorder = new RecordingMiddleware();
    $entityManager = EntityManagerFactory::create($recorder);

    [$schema, $host] = recordErrorPlacementSchema(new Headband(), $entityManager, Group::make([StateField::make('label')])->relationship('spine'));
    $host->data = ['spine' => ['label' => 'A spine with no code']];
    $recorder->reset();

    expect(recordErrorPlacementErrors($schema))->toBe(['data' => ['This value should not be blank.']])
        ->and($host->getErrorsForSchemaStatePath('data'))->toBe(['This value should not be blank.'])
        ->and($host->getErrorsForSchemaStatePath('data.spine'))->toBe([])
        ->and($recorder->statements())->toBe([]);
});

it("files the blank-key refusal under the related record's key field when the entangled group maps it", function (): void {
    $entityManager = EntityManagerFactory::create();

    [$schema, $host] = recordErrorPlacementSchema(new Headband(), $entityManager, Group::make([StateField::make('code'), StateField::make('label')])->relationship('spine'));
    $host->data = ['spine' => ['code' => '   ', 'label' => 'Spaced']];

    expect(recordErrorPlacementErrors($schema))->toBe(['data.spine.code' => ['This value should not be blank.']]);
});

it("files a related record's own constraint on a field the group does not map at the schema's own path", function (): void {
    $entityManager = EntityManagerFactory::create();

    [$schema, $host] = recordErrorPlacementSchema(new LintelHolder(), $entityManager, StateField::make('name'), Group::make([StateField::make('code')])->relationship('plate'));
    $host->data = ['name' => 'Holder', 'plate' => ['code' => 'P1']];

    expect(recordErrorPlacementErrors($schema))->toBe(['data' => ['This value should not be blank.']])
        ->and($host->getErrorsForSchemaStatePath('data'))->toBe(['This value should not be blank.']);
});

it("files a related record's own constraint under the field the group maps it through", function (): void {
    $entityManager = EntityManagerFactory::create();

    [$schema, $host] = recordErrorPlacementSchema(new LintelHolder(), $entityManager, StateField::make('name'), Group::make([StateField::make('code'), StateField::make('label')])->relationship('plate'));
    $host->data = ['name' => 'Holder', 'plate' => ['code' => 'P1', 'label' => '']];

    expect(recordErrorPlacementErrors($schema))->toBe(['data.plate.label' => ['This value should not be blank.']])
        ->and($host->getErrorsForSchemaStatePath('data'))->toBe([]);
});
