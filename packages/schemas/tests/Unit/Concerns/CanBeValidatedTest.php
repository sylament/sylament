<?php

declare(strict_types=1);

use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Components\ConstrainedField;
use Sylament\Schemas\Tests\Fixtures\Concerns\StateHostFixture;
use Symfony\Component\Validator\Constraints\NotBlank;

it('collects constraints keyed by absolute state path', function (): void {
    $schema = Schema::make(new StateHostFixture())
        ->statePath('data')
        ->components([
            ConstrainedField::make('title')->required(),
            ConstrainedField::make('slug'),
        ]);

    $constraints = $schema->getValidationConstraints();

    expect(array_keys($constraints))->toBe(['data.title'])
        ->and($constraints['data.title'][0])->toBeInstanceOf(NotBlank::class);
});

it('skips a component that is neither dehydrated nor validated', function (): void {
    $schema = Schema::make(new StateHostFixture())
        ->statePath('data')
        ->components([
            ConstrainedField::make('title')->required()->dehydrated(false)->validatedWhenNotDehydrated(false),
        ]);

    expect($schema->getValidationConstraints())->toBe([]);
});

it('still collects from a non-dehydrated component that stays validated', function (): void {
    $schema = Schema::make(new StateHostFixture())
        ->statePath('data')
        ->components([
            ConstrainedField::make('password_confirmation')->required()->dehydrated(false),
        ]);

    expect(array_keys($schema->getValidationConstraints()))->toBe(['data.password_confirmation']);
});

it('skips a hidden component that does not dehydrate when hidden', function (): void {
    $schema = Schema::make(new StateHostFixture())
        ->statePath('data')
        ->components([
            ConstrainedField::make('title')->required()->hidden(),
        ]);

    expect($schema->getValidationConstraints())->toBe([]);
});

it('recurses into child schemas', function (): void {
    $schema = Schema::make(new StateHostFixture())
        ->statePath('data')
        ->components([
            ConstrainedField::make('wrapper')->childComponents([
                ConstrainedField::make('nested')->required(),
            ]),
        ]);

    expect(array_keys($schema->getValidationConstraints()))->toContain('data.wrapper.nested');
});
