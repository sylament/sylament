<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Unit\Validation;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use LogicException;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Handle;
use Sylament\Forms\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Forms\Validation\ExistingValue;
use Sylament\Forms\Validation\ExistingValueValidator;
use Sylament\Schemas\State\AssociationReader;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

function existingValidator(?AssociationReader $reader): ValidatorInterface
{
    return Validation::createValidatorBuilder()
        ->setConstraintValidatorFactory(new ConstraintValidatorFactory([
            ExistingValueValidator::class => new ExistingValueValidator($reader),
        ]))
        ->getValidator();
}

/**
 * @return ConstraintViolationListInterface<int, ConstraintViolationInterface>
 */
function existingViolations(EntityManager $entityManager, mixed $value, ExistingValue $constraint): ConstraintViolationListInterface
{
    return existingValidator(new AssociationReader($entityManager))->validate($value, $constraint);
}

it('accepts a value some record holds', function (): void {
    $entityManager = EntityManagerFactory::create();
    EntityManagerFactory::seed($entityManager, new Handle('ada'));

    expect(existingViolations($entityManager, 'ada', new ExistingValue(Handle::class, 'name')))->toHaveCount(0);
});

it('flags a value no record holds', function (): void {
    $entityManager = EntityManagerFactory::create();
    EntityManagerFactory::seed($entityManager, new Handle('ada'));

    $violations = existingViolations($entityManager, 'grace', new ExistingValue(Handle::class, 'name'));

    expect($violations)->toHaveCount(1)
        ->and($violations->get(0)->getMessage())->toBe('The selected value does not exist.');
});

it('checks every element of an array value in one probe', function (): void {
    $entityManager = EntityManagerFactory::create();
    EntityManagerFactory::seed($entityManager, new Handle('ada'), new Handle('bob'));

    expect(existingViolations($entityManager, ['ada', 'bob'], new ExistingValue(Handle::class, 'name')))->toHaveCount(0)
        ->and(existingViolations($entityManager, ['ada', 'grace'], new ExistingValue(Handle::class, 'name')))->toHaveCount(1);
});

it('counts a repeated element once', function (): void {
    $entityManager = EntityManagerFactory::create();
    EntityManagerFactory::seed($entityManager, new Handle('ada'));

    expect(existingViolations($entityManager, ['ada', 'ada'], new ExistingValue(Handle::class, 'name')))->toHaveCount(0);
});

it('scopes the probe through modifyQueryUsing', function (): void {
    $entityManager = EntityManagerFactory::create();
    EntityManagerFactory::seed($entityManager, new Handle('ada', 'alice'));

    $scopeToBob = new ExistingValue(Handle::class, 'name', modifyQueryUsing: function (QueryBuilder $query): void {
        $query->andWhere('record.owner = :sylament_owner')->setParameter('sylament_owner', 'bob');
    });

    expect(existingViolations($entityManager, 'ada', $scopeToBob))->toHaveCount(1)
        ->and(existingViolations($entityManager, 'ada', new ExistingValue(Handle::class, 'name')))->toHaveCount(0);
});

it('returns on an absent value without probing', function (): void {
    $entityManager = EntityManagerFactory::create();

    expect(existingViolations($entityManager, null, new ExistingValue(Handle::class, 'name')))->toHaveCount(0)
        ->and(existingViolations($entityManager, '', new ExistingValue(Handle::class, 'name')))->toHaveCount(0)
        ->and(existingViolations($entityManager, [], new ExistingValue(Handle::class, 'name')))->toHaveCount(0);
});

it('refuses to run without an association reader, naming the bundle', function (): void {
    expect(fn (): ConstraintViolationListInterface => existingValidator(null)->validate('ada', new ExistingValue(Handle::class, 'name')))
        ->toThrow(LogicException::class, 'SylamentFormsBundle');
});

it('refuses to run against a reader with no entity manager, naming the arm', function (): void {
    expect(fn (): ConstraintViolationListInterface => existingValidator(new AssociationReader())->validate('ada', new ExistingValue(Handle::class, 'name')))
        ->toThrow(LogicException::class, 'exists()');
});

it('refuses a property the entity does not map', function (): void {
    $entityManager = EntityManagerFactory::create();

    expect(fn (): ConstraintViolationListInterface => existingViolations($entityManager, 'ada', new ExistingValue(Handle::class, 'nope')))
        ->toThrow(LogicException::class, 'not a mapped field');
});
