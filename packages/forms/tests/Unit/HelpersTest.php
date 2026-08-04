<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Unit;

use function Sylament\Forms\array_move_after;
use function Sylament\Forms\array_move_before;

it('moves a key after its successor', function (): void {
    $items = ['a' => 1, 'b' => 2, 'c' => 3];

    expect(array_move_after($items, 'a'))->toBe(['b' => 2, 'a' => 1, 'c' => 3])
        ->and(array_move_after($items, 'b'))->toBe(['a' => 1, 'c' => 3, 'b' => 2]);
});

it('leaves the last key alone when moving it after', function (): void {
    $items = ['a' => 1, 'b' => 2];

    expect(array_move_after($items, 'b'))->toBe($items);
});

it('moves a key before its predecessor', function (): void {
    $items = ['a' => 1, 'b' => 2, 'c' => 3];

    expect(array_move_before($items, 'c'))->toBe(['a' => 1, 'c' => 3, 'b' => 2])
        ->and(array_move_before($items, 'b'))->toBe(['b' => 2, 'a' => 1, 'c' => 3]);
});

it('leaves the first key alone when moving it before', function (): void {
    $items = ['a' => 1, 'b' => 2];

    expect(array_move_before($items, 'a'))->toBe($items);
});

it('leaves an unknown key alone', function (): void {
    $items = ['a' => 1, 'b' => 2];

    expect(array_move_after($items, 'nope'))->toBe($items)
        ->and(array_move_before($items, 'nope'))->toBe($items);
});

it('moves positional keys, which repeaters carry in fake mode', function (): void {
    $items = [0 => ['name' => 'a'], 1 => ['name' => 'b'], 2 => ['name' => 'c']];

    expect(array_move_after($items, 0))->toBe([1 => ['name' => 'b'], 0 => ['name' => 'a'], 2 => ['name' => 'c']])
        ->and(array_move_before($items, 2))->toBe([0 => ['name' => 'a'], 2 => ['name' => 'c'], 1 => ['name' => 'b']]);
});

it('matches an integer key sent as a string', function (): void {
    $items = [0 => ['name' => 'a'], 1 => ['name' => 'b']];

    expect(array_move_after($items, '0'))->toBe([1 => ['name' => 'b'], 0 => ['name' => 'a']])
        ->and(array_move_before($items, '1'))->toBe([1 => ['name' => 'b'], 0 => ['name' => 'a']]);
});

it('leaves an empty array alone', function (): void {
    expect(array_move_after([], 'a'))->toBe([])
        ->and(array_move_before([], 'a'))->toBe([]);
});
