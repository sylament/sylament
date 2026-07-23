<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Components\StateCasts;

use Sylament\Schemas\Components\StateCasts\BooleanStateCast;

it('casts to a nullable bool on get, passing blanks through as null', function (): void {
    $cast = new BooleanStateCast();

    expect($cast->get(1))->toBeTrue()
        ->and($cast->get(0))->toBeFalse()
        ->and($cast->get('1'))->toBeTrue()
        ->and($cast->get(null))->toBeNull()
        ->and($cast->get(''))->toBeNull();
});

it('stores a bool on set, passing blanks through as null', function (): void {
    $cast = new BooleanStateCast();

    expect($cast->set(true))->toBeTrue()
        ->and($cast->set(false))->toBeFalse()
        ->and($cast->set(null))->toBeNull()
        ->and($cast->set(''))->toBeNull();
});

it('stores as int when configured', function (): void {
    $cast = new BooleanStateCast(isStoredAsInt: true);

    expect($cast->set(true))->toBe(1)
        ->and($cast->set(false))->toBe(0)
        ->and($cast->get(1))->toBeTrue()
        ->and($cast->get(0))->toBeFalse();
});

it('never returns null when not nullable', function (): void {
    $cast = new BooleanStateCast(isNullable: false);

    expect($cast->get(null))->toBeFalse()
        ->and($cast->get(''))->toBeFalse()
        ->and($cast->set(null))->toBeFalse();
});
