<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Unit\Components\Concerns;

use Sylament\Forms\Tests\Fixtures\Components\FixtureField;
use Sylament\Forms\Tests\Fixtures\Components\FixtureLengthField;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

use function Sylament\Forms\Tests\wireField;

it('is not required by default and builds no constraints', function (): void {
    $field = FixtureField::make('title');

    expect($field->isRequired())->toBeFalse()
        ->and($field->getValidationConstraints())->toBe([]);
});

it('builds NotBlank when required', function (): void {
    $constraints = FixtureField::make('title')->required()->getValidationConstraints();

    expect($constraints)->toHaveCount(1)
        ->and($constraints[0])->toBeInstanceOf(NotBlank::class)
        ->and($constraints[0]->message)->toBe('This value should not be blank.');
});

it('inverts required through nullable()', function (): void {
    expect(FixtureField::make('title')->required()->nullable()->isRequired())->toBeFalse()
        ->and(FixtureField::make('title')->nullable(false)->isRequired())->toBeTrue();
});

it('evaluates a closure required condition', function (): void {
    expect(FixtureField::make('title')->required(fn (): bool => true)->isRequired())->toBeTrue()
        ->and(FixtureField::make('title')->required(fn (): bool => false)->isRequired())->toBeFalse();
});

it('appends escape-hatch constraints behind their condition', function (): void {
    $field = FixtureField::make('slug')
        ->constraint(new Regex('/^[a-z-]+$/'))
        ->constraint(new NotBlank(), condition: false);

    $constraints = $field->getValidationConstraints();

    expect($constraints)->toHaveCount(1)
        ->and($constraints[0])->toBeInstanceOf(Regex::class);
});

it('evaluates closure constraints and flattens arrays', function (): void {
    $field = FixtureField::make('slug')->constraints(fn (): array => [new NotBlank(), new Regex('/^[a-z-]+$/')]);

    expect($field->getValidationConstraints())->toHaveCount(2);
});

it('skips a closure constraint that returns null', function (): void {
    $field = FixtureField::make('slug')
        ->constraint(fn (): ?Regex => null)
        ->constraint(new NotBlank());

    $constraints = $field->getValidationConstraints();

    expect($constraints)->toHaveCount(1)
        ->and($constraints[0])->toBeInstanceOf(NotBlank::class);
});

it('evaluates a closure constraint returning a single constraint', function (): void {
    $field = FixtureField::make('slug')->constraint(fn (): NotBlank => new NotBlank());

    expect($field->getValidationConstraints())->toHaveCount(1)
        ->and($field->getValidationConstraints()[0])->toBeInstanceOf(NotBlank::class);
});

it('accepts a constraint list in constraints()', function (): void {
    $field = FixtureField::make('slug')->constraints([new NotBlank(), new Regex('/^[a-z-]+$/')], condition: false);

    expect($field->getValidationConstraints())->toBe([]);
});

it('bakes the required message from validationMessages()', function (): void {
    $constraints = FixtureField::make('title')
        ->required()
        ->validationMessages(['required' => 'Give it a title.'])
        ->getValidationConstraints();

    expect($constraints[0]->message)->toBe('Give it a title.');
});

it('appends the length constraints of a length-constrained field', function (): void {
    $constraints = FixtureLengthField::make('title')->required()->maxLength(255)->getValidationConstraints();

    expect($constraints)->toHaveCount(2)
        ->and($constraints[0])->toBeInstanceOf(NotBlank::class)
        ->and($constraints[1])->toBeInstanceOf(Length::class)
        ->and($constraints[1]->max)->toBe(255);
});

it('keys dehydrated constraints by state path', function (): void {
    $field = wireField(FixtureField::make('title')->required());

    $constraints = [];
    $field->dehydrateValidationConstraints($constraints);

    expect(array_keys($constraints))->toBe([$field->getStatePath()])
        ->and($constraints['title'])->toHaveCount(1)
        ->and($constraints['title'][0])->toBeInstanceOf(NotBlank::class);
});

it('dehydrates nothing without constraints', function (): void {
    $field = wireField(FixtureField::make('title'));

    $constraints = [];
    $field->dehydrateValidationConstraints($constraints);

    expect($constraints)->toBe([]);
});

it('dehydrates nothing without a state path', function (): void {
    $field = wireField(FixtureField::make()->required());

    $constraints = [];
    $field->dehydrateValidationConstraints($constraints);

    expect($constraints)->toBe([]);
});
