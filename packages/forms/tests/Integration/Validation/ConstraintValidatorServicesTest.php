<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Validation;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Handle;
use Sylament\Forms\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Forms\Validation\ActiveUrlValidator;
use Sylament\Forms\Validation\ExistingValue;
use Sylament\Forms\Validation\ExistingValueValidator;
use Sylament\Forms\Validation\UniqueValue;
use Sylament\Forms\Validation\UniqueValueValidator;
use Symfony\Component\Validator\Validator\ValidatorInterface;

it('registers every constraint validator as a service', function (): void {
    $container = $this::getContainer();

    expect($container->has(UniqueValueValidator::class))->toBeTrue()
        ->and($container->has(ExistingValueValidator::class))->toBeTrue()
        ->and($container->has(ActiveUrlValidator::class))->toBeTrue();
});

it("probes the database through the container-built validator, which carries the kernel's reader", function (): void {
    $container = $this::getContainer();
    $entityManager = $container->get(EntityManagerInterface::class);
    EntityManagerFactory::seed($entityManager, new Handle('taken'));

    $validator = $container->get(ValidatorInterface::class);

    expect($validator->validate('taken', new UniqueValue(Handle::class, 'name')))->toHaveCount(1)
        ->and($validator->validate('free', new UniqueValue(Handle::class, 'name')))->toHaveCount(0)
        ->and($validator->validate('taken', new ExistingValue(Handle::class, 'name')))->toHaveCount(0)
        ->and($validator->validate('free', new ExistingValue(Handle::class, 'name')))->toHaveCount(1);
});
