<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Integration\Concerns;

use Closure;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManager;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Exceptions\ValidationException;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\AssociationReader;
use Sylament\Schemas\Tests\Fixtures\Components\StateField;
use Sylament\Schemas\Tests\Fixtures\Concerns\StateHostFixture;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Callsign;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Redirect;
use Sylament\Schemas\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Schemas\Tests\Fixtures\Doctrine\IdentifierGuardRig;
use Sylament\Support\Tests\Doctrine\RecordingMiddleware;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/*
 * Schema::validateRecordKey(false): the record check's blank-key and taken-key
 * passes skipped, so the application owns a new record's key — while the
 * changed-key pass, its put-back and the record's own constraints run as
 * before. The taken key the switch lets through is held by a stored row the
 * identity map does not hold, so the flush meets the primary key: a
 * UniqueConstraintViolationException on SQLite, MySQL and PostgreSQL alike (a
 * managed holder would be Doctrine's EntityIdentityCollisionException at
 * persist() instead).
 */

/**
 * @return array{Schema, StateHostFixture}
 */
function recordKeySwitchSchema(object $record, EntityManager $entityManager, bool|Closure $validated, Component ...$components): array
{
    $host = new StateHostFixture();
    $host->setSchemaStateValidator(test()->getContainer()->get(ValidatorInterface::class));

    $schema = Schema::make($host)->statePath('data')->record($record)->components($components)->validateRecordKey($validated);
    $schema->setAssociationReader(new AssociationReader($entityManager));

    $host->cacheSchema('schema', $schema);
    $schema->fill($record);

    return [$schema, $host];
}

/**
 * @return array<string, list<string>>
 */
function recordKeySwitchErrors(Schema $schema): array
{
    try {
        $schema->getData();
    } catch (ValidationException $validationException) {
        return $validationException->getErrors();
    }

    return [];
}

it('lets a taken key through to the flush, which the database refuses, when the key rules are off', function (): void {
    $recorder = new RecordingMiddleware();
    $entityManager = IdentifierGuardRig::create($recorder);
    $callsign = new Callsign();

    [$schema, $host] = recordKeySwitchSchema($callsign, $entityManager, false, StateField::make('code'), StateField::make('label'));

    $host->data['code'] = IdentifierGuardRig::CALLSIGN;
    $host->data['label'] = 'Station two';
    $recorder->reset();

    expect($schema->getData())->toBe($callsign)
        ->and($recorder->statements())->toBe([]);

    $entityManager->persist($callsign);

    expect(function () use ($entityManager): void {
        $entityManager->flush();
    })->toThrow(UniqueConstraintViolationException::class);
});

it('refuses nothing under a blank key field the schema maps when the key rules are off', function (): void {
    $recorder = new RecordingMiddleware();
    $entityManager = EntityManagerFactory::create($recorder);
    $redirect = new Redirect();

    [$schema, $host] = recordKeySwitchSchema($redirect, $entityManager, false, StateField::make('host'), StateField::make('path'), StateField::make('label'));

    $host->data['host'] = 'example.com';
    $host->data['path'] = '   ';
    $host->data['label'] = 'Spaced';
    $recorder->reset();

    expect($schema->getData())->toBe($redirect)
        ->and($redirect->getPath())->toBe('   ')
        ->and($recorder->statements())->toBe([]);
});

it('still refuses a moved key and puts the stored one back when the key rules are off', function (): void {
    $recorder = new RecordingMiddleware();
    $entityManager = IdentifierGuardRig::create($recorder);
    $callsign = IdentifierGuardRig::callsign($entityManager);

    [$schema, $host] = recordKeySwitchSchema($callsign, $entityManager, false, StateField::make('code'), StateField::make('label'));

    $host->data['code'] = 'OK2';
    $recorder->reset();

    expect(recordKeySwitchErrors($schema))->toBe(['data.code' => ['This value cannot be changed.']])
        ->and($host->getErrorsForSchemaStatePath('data.code'))->toBe(['This value cannot be changed.'])
        ->and($callsign->getCode())->toBe(IdentifierGuardRig::CALLSIGN)
        ->and($recorder->statements())->toBe([]);

    $entityManager->flush();

    expect($recorder->statements())->toBe([]);
});

it('still answers the constraints the record declares when the key rules are off', function (): void {
    $entityManager = IdentifierGuardRig::create();

    [$schema, $host] = recordKeySwitchSchema(new Callsign(), $entityManager, false, StateField::make('code'), StateField::make('label'));

    $host->data['code'] = 'OK9';
    $host->data['label'] = 'x';

    expect(recordKeySwitchErrors($schema))->toBe(['data.label' => ['Name the station.']]);
});

it('switches the key rules off through a closure the schema is injected into', function (): void {
    $recorder = new RecordingMiddleware();
    $entityManager = IdentifierGuardRig::create($recorder);
    $callsign = new Callsign();

    [$schema, $host] = recordKeySwitchSchema(
        $callsign,
        $entityManager,
        static fn (Schema $schema): bool => $schema->getStatePath() !== 'data',
        StateField::make('code'),
        StateField::make('label'),
    );

    $host->data['code'] = IdentifierGuardRig::CALLSIGN;
    $host->data['label'] = 'Station two';
    $recorder->reset();

    expect($schema->getData())->toBe($callsign)
        ->and($recorder->statements())->toBe([]);
});
