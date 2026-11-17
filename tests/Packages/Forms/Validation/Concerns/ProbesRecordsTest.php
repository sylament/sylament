<?php

declare(strict_types=1);

use Doctrine\ORM\QueryBuilder;
use Sylament\Forms\Validation\UniqueValue;
use Sylament\Forms\Validation\UniqueValueValidator;
use Sylament\Schemas\State\AssociationReader;
use Sylament\Tests\Fixtures\Entity\Product;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

it('refuses to probe through a reader with no entity manager, naming the arm and the property', function (): void {
    $validator = Validation::createValidatorBuilder()
        ->setConstraintValidatorFactory(new ConstraintValidatorFactory([UniqueValueValidator::class => new UniqueValueValidator(new AssociationReader())]))
        ->getValidator();

    expect(fn (): ConstraintViolationListInterface => $validator->validate('Lamp', new UniqueValue(Product::class, 'name')))
        ->toThrow(LogicException::class, 'unique() needs a Doctrine entity manager to probe [' . Product::class . '::$name], and none is registered.');
});

it('refuses a property the entity does not map, naming the fields it does, before any statement', function (): void {
    expect(fn () => $this::getContainer()->get(ValidatorInterface::class)->validate('Lamp', new UniqueValue(Product::class, 'title')))
        ->toThrow(LogicException::class, 'unique() probes [Sylament\Tests\Fixtures\Entity\Product] by [title], which is not a mapped field of that entity. Name a field it maps: [id], [name].')
        ->and($this->statementsRun())->toBe([]);
});

it('binds the value under the next free probe name, after any parameter already on the query, and lets the modifier rebind it', function (): void {
    $this->seed(new Product('Lamp'));
    $validator = $this::getContainer()->get(ValidatorInterface::class);

    expect($validator->validate('Desk', new UniqueValue(Product::class, 'name', modifyQueryUsing: static fn (QueryBuilder $query): QueryBuilder => $query->setParameter('sylament_probe_0', 'Lamp'))))->toHaveCount(1)
        ->and($validator->validate('Lamp', new UniqueValue(Product::class, 'name', modifyQueryUsing: static fn (QueryBuilder $query): QueryBuilder => $query->setParameter('sylament_probe_0', 'Desk'))))->toHaveCount(0);
});
