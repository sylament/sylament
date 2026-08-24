<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\State\Casts;

use Sylament\Schemas\State\Casts\StripCharactersStateCast;

it('strips on both sides of the round trip', function (): void {
    $cast = new StripCharactersStateCast([',']);

    expect($cast->get('1,234'))->toBe('1234')
        ->and($cast->set('1,234'))->toBe('1234');
});

it('strips each string of an array element-wise, leaving the rest alone', function (): void {
    $cast = new StripCharactersStateCast([',']);

    expect($cast->get(['1,2', 3, null]))->toBe(['12', 3, null])
        ->and($cast->set(['1,2', 3, null]))->toBe(['12', 3, null]);
});

it('leaves a non-string alone', function (): void {
    $cast = new StripCharactersStateCast([',']);

    expect($cast->get(null))->toBeNull()
        ->and($cast->set(7))->toBe(7);
});

it('is a pass-through with no characters', function (): void {
    $cast = new StripCharactersStateCast();

    expect($cast->get('1,234'))->toBe('1,234')
        ->and($cast->set(['1,2']))->toBe(['1,2']);
});
