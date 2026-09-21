<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Unit\Validation;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use LogicException;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\CompositePair;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Handle;
use Sylament\Forms\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Forms\Validation\UniqueValue;
use Sylament\Forms\Validation\UniqueValueValidator;
use Sylament\Schemas\State\AssociationReader;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * `Validation::createValidator()` builds every validator with `new $class()`, which
 * for a validator with a dependency is an `ArgumentCountError` — so the Unit suite
 * seeds the instance the way the container would.
 */
function uniqueValidator(?AssociationReader $reader): ValidatorInterface
{
    return Validation::createValidatorBuilder()
        ->setConstraintValidatorFactory(new ConstraintValidatorFactory([
            UniqueValueValidator::class => new UniqueValueValidator($reader),
        ]))
        ->getValidator();
}

/**
 * @return ConstraintViolationListInterface<int, ConstraintViolationInterface>
 */
function uniqueViolations(EntityManager $entityManager, mixed $value, UniqueValue $constraint): ConstraintViolationListInterface
{
    return uniqueValidator(new AssociationReader($entityManager))->validate($value, $constraint);
}

/**
 * The DQL the probe carries once the value comparison and the exclusion are both
 * on it — `modifyQueryUsing` is the last thing the validator applies, so it is
 * the one seat from which the whole predicate is readable.
 *
 * @param class-string $entityClass
 */
function compositeUniqueProbeDql(EntityManager $entityManager, string $entityClass, string $property, mixed $value, string|int|null $ignoredIdentifier): string
{
    $dql = '';

    uniqueViolations($entityManager, $value, new UniqueValue(
        $entityClass,
        $property,
        ignoredIdentifier: $ignoredIdentifier,
        modifyQueryUsing: function (QueryBuilder $query) use (&$dql): void {
            $dql = $query->getDQL();
        },
    ));

    return $dql;
}

it('accepts a value no record holds', function (): void {
    $entityManager = EntityManagerFactory::create();
    EntityManagerFactory::seed($entityManager, new Handle('ada'));

    expect(uniqueViolations($entityManager, 'grace', new UniqueValue(Handle::class, 'name')))->toHaveCount(0);
});

it('flags a value a record already holds', function (): void {
    $entityManager = EntityManagerFactory::create();
    EntityManagerFactory::seed($entityManager, new Handle('ada'));

    $violations = uniqueViolations($entityManager, 'ada', new UniqueValue(Handle::class, 'name'));

    expect($violations)->toHaveCount(1)
        ->and($violations->get(0)->getMessage())->toBe('This value is already taken.');
});

it('ignores the record being edited, and only that one', function (): void {
    $entityManager = EntityManagerFactory::create();
    $ada = new Handle('ada');
    EntityManagerFactory::seed($entityManager, $ada, new Handle('grace'));

    expect(uniqueViolations($entityManager, 'ada', new UniqueValue(Handle::class, 'name', ignoredIdentifier: (string) $ada->getId())))->toHaveCount(0)
        ->and(uniqueViolations($entityManager, 'ada', new UniqueValue(Handle::class, 'name', ignoredIdentifier: '9999')))->toHaveCount(1);
});

it('scopes the probe through modifyQueryUsing', function (): void {
    $entityManager = EntityManagerFactory::create();
    EntityManagerFactory::seed($entityManager, new Handle('ada', 'alice'));

    $scopeToBob = new UniqueValue(Handle::class, 'name', modifyQueryUsing: function (QueryBuilder $query): void {
        $query->andWhere('record.owner = :sylament_owner')->setParameter('sylament_owner', 'bob');
    });

    expect(uniqueViolations($entityManager, 'ada', $scopeToBob))->toHaveCount(0)
        ->and(uniqueViolations($entityManager, 'ada', new UniqueValue(Handle::class, 'name')))->toHaveCount(1);
});

it('returns on an absent value without probing', function (): void {
    $entityManager = EntityManagerFactory::create();
    EntityManagerFactory::seed($entityManager, new Handle('ada'));

    expect(uniqueViolations($entityManager, null, new UniqueValue(Handle::class, 'name')))->toHaveCount(0)
        ->and(uniqueViolations($entityManager, '', new UniqueValue(Handle::class, 'name')))->toHaveCount(0);
});

it('refuses to run without an association reader, naming the bundle', function (): void {
    expect(fn (): ConstraintViolationListInterface => uniqueValidator(null)->validate('ada', new UniqueValue(Handle::class, 'name')))
        ->toThrow(LogicException::class, 'SylamentFormsBundle');
});

it('refuses to run against a reader with no entity manager, naming the arm', function (): void {
    expect(fn (): ConstraintViolationListInterface => uniqueValidator(new AssociationReader())->validate('ada', new UniqueValue(Handle::class, 'name')))
        ->toThrow(LogicException::class, 'unique()');
});

it('refuses a property the entity does not map', function (): void {
    $entityManager = EntityManagerFactory::create();

    expect(fn (): ConstraintViolationListInterface => uniqueViolations($entityManager, 'ada', new UniqueValue(Handle::class, 'nope')))
        ->toThrow(LogicException::class, 'not a mapped field');
});

it('excludes a composite record from its own probe, and only that one', function (): void {
    $entityManager = EntityManagerFactory::create();
    EntityManagerFactory::seed($entityManager, new CompositePair(1, 2, 'pair'), new CompositePair(3, 4, 'other'));

    expect(uniqueViolations($entityManager, 'pair', new UniqueValue(CompositePair::class, 'label', ignoredIdentifier: '1~2')))->toHaveCount(0)
        ->and(uniqueViolations($entityManager, 'pair', new UniqueValue(CompositePair::class, 'label', ignoredIdentifier: '3~4')))->toHaveCount(1);
});

it('excludes nothing when the ignored identifier does not decode', function (): void {
    $entityManager = EntityManagerFactory::create();
    EntityManagerFactory::seed($entityManager, new CompositePair(1, 2, 'pair'));

    expect(uniqueViolations($entityManager, 'pair', new UniqueValue(CompositePair::class, 'label', ignoredIdentifier: 'not-a-key')))->toHaveCount(1)
        ->and(uniqueViolations($entityManager, 'pair', new UniqueValue(CompositePair::class, 'label', ignoredIdentifier: '1')))->toHaveCount(1)
        ->and(compositeUniqueProbeDql($entityManager, CompositePair::class, 'label', 'pair', 'not-a-key'))
        ->toBe('SELECT record FROM ' . CompositePair::class . ' record WHERE record.label = :sylament_probe_0');
});

it('names the probed value and every excluded identifier column apart', function (): void {
    $entityManager = EntityManagerFactory::create();
    EntityManagerFactory::seed($entityManager, new CompositePair(1, 2, 'pair'));

    expect(compositeUniqueProbeDql($entityManager, CompositePair::class, 'label', 'pair', '1~2'))
        ->toBe('SELECT record FROM ' . CompositePair::class . ' record WHERE record.label = :sylament_probe_0 AND (NOT (record.a = :sylament_ignored_record_0 AND record.b = :sylament_ignored_record_1))');
});

it('keeps the single-identifier probe text, the exclusion aside', function (): void {
    $entityManager = EntityManagerFactory::create();
    EntityManagerFactory::seed($entityManager, new Handle('ada'));

    expect(compositeUniqueProbeDql($entityManager, Handle::class, 'name', 'ada', 1))
        ->toBe('SELECT record FROM ' . Handle::class . ' record WHERE record.name = :sylament_probe_0 AND record.id <> :sylament_ignored_record');
});
