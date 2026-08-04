<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Unit\Components\Concerns;

use Sylament\Forms\Tests\Fixtures\Components\FixtureItemsField;

use function Sylament\Forms\Tests\wireField;

it('is not cloneable by default', function (): void {
    $field = FixtureItemsField::make('tags');
    wireField($field);

    expect($field->isCloneable())->toBeFalse();
});

it('turns cloning on', function (): void {
    $field = FixtureItemsField::make('tags')->cloneable();
    wireField($field);

    $off = FixtureItemsField::make('tags')->cloneable(false);
    wireField($off);

    expect($field->isCloneable())->toBeTrue()
        ->and($off->isCloneable())->toBeFalse();
});

it('evaluates a closure condition', function (): void {
    $field = FixtureItemsField::make('tags')->cloneable(fn (): bool => true);
    wireField($field);

    expect($field->isCloneable())->toBeTrue();
});

it('is never cloneable while disabled', function (): void {
    $field = FixtureItemsField::make('tags')->cloneable()->disabled();
    wireField($field);

    expect($field->isCloneable())->toBeFalse();
});
