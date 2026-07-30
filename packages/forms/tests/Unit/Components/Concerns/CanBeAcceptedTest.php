<?php

declare(strict_types=1);

use Sylament\Forms\Tests\Fixtures\Components\FixtureBooleanField;
use Symfony\Component\Validator\Constraints\IsFalse;
use Symfony\Component\Validator\Constraints\IsTrue;

it('builds no acceptance constraint by default', function (): void {
    expect(FixtureBooleanField::make('agree')->getValidationConstraints())->toBe([]);
});

it('builds an IsTrue constraint from accepted()', function (): void {
    $constraints = FixtureBooleanField::make('agree')->accepted()->getValidationConstraints();

    expect($constraints)->toHaveCount(1)
        ->and($constraints[0])->toBeInstanceOf(IsTrue::class);
});

it('builds an IsFalse constraint from declined()', function (): void {
    $constraints = FixtureBooleanField::make('agree')->declined()->getValidationConstraints();

    expect($constraints)->toHaveCount(1)
        ->and($constraints[0])->toBeInstanceOf(IsFalse::class);
});

it('skips the constraint when the condition is false', function (): void {
    expect(FixtureBooleanField::make('agree')->accepted(false)->getValidationConstraints())->toBe([]);
});

it('bakes the validation message into the constraint', function (): void {
    $constraints = FixtureBooleanField::make('agree')
        ->validationMessages(['accepted' => 'You must agree.'])
        ->accepted()
        ->getValidationConstraints();

    expect($constraints[0]->message)->toBe('You must agree.');
});

it('reads the validation message set after the accepted() call', function (): void {
    $constraints = FixtureBooleanField::make('agree')
        ->accepted()
        ->validationMessages(['accepted' => 'You must agree.'])
        ->getValidationConstraints();

    expect($constraints[0]->message)->toBe('You must agree.');
});

it('reads the validation message set after the declined() call', function (): void {
    $constraints = FixtureBooleanField::make('agree')
        ->declined()
        ->validationMessages(['declined' => 'You must not agree.'])
        ->getValidationConstraints();

    expect($constraints[0]->message)->toBe('You must not agree.');
});
