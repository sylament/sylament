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

it('refuses an entity with a composite identifier once an identifier is ignored', function (): void {
    $entityManager = EntityManagerFactory::create();

    expect(fn (): ConstraintViolationListInterface => uniqueViolations($entityManager, 'pair', new UniqueValue(CompositePair::class, 'label', ignoredIdentifier: '1')))
        ->toThrow(LogicException::class, 'composite identifier');
});
