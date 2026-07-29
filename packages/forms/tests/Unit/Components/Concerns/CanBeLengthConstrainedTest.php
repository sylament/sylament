<?php

declare(strict_types=1);

use Sylament\Forms\Tests\Fixtures\Components\FixtureLengthField;
use Symfony\Component\Validator\Constraints\Length;

it('seeds max and min from length()', function (): void {
    $field = FixtureLengthField::make('code')->length(6);

    expect($field->getLength())->toBe(6)
        ->and($field->getMaxLength())->toBe(6)
        ->and($field->getMinLength())->toBe(6);
});

it('builds an exact Length constraint from length()', function (): void {
    $constraints = FixtureLengthField::make('code')->length(6)->getLengthValidationConstraints();

    expect($constraints)->toHaveCount(1)
        ->and($constraints[0])->toBeInstanceOf(Length::class)
        ->and($constraints[0]->min)->toBe(6)
        ->and($constraints[0]->max)->toBe(6);
});

it('builds separate max and min Length constraints', function (): void {
    $constraints = FixtureLengthField::make('title')->maxLength(255)->minLength(3)->getLengthValidationConstraints();

    expect($constraints)->toHaveCount(2)
        ->and($constraints[0]->max)->toBe(255)
        ->and($constraints[0]->min)->toBeNull()
        ->and($constraints[1]->min)->toBe(3)
        ->and($constraints[1]->max)->toBeNull();
});

it('builds nothing without bounds', function (): void {
    expect(FixtureLengthField::make('title')->getLengthValidationConstraints())->toBe([]);
});

it('evaluates a closure length', function (): void {
    expect(FixtureLengthField::make('code')->length(fn (): int => 4)->getLength())->toBe(4);
});
