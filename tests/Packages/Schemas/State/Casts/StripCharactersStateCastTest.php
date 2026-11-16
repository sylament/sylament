<?php

declare(strict_types=1);

use Sylament\Schemas\State\Casts\StripCharactersStateCast;

it('strips the characters from a string, and from each string of a list, both ways', function (mixed $state, mixed $expected): void {
    $cast = new StripCharactersStateCast([' ', '-']);

    expect($cast->get($state))->toBe($expected)
        ->and($cast->set($state))->toBe($expected)
        ->and(new StripCharactersStateCast()->get($state))->toBe($state);
})->with([
    'a string' => ['12 34-5', '12345'],
    'a list' => [['1-2', 3, '4 5'], ['12', 3, '45']],
    'neither' => [42, 42],
]);
