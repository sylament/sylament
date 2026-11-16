<?php

declare(strict_types=1);

describe('array_move_before() and array_move_after()', function (): void {
    it('swaps an entry with its neighbour, keeping every key and value', function (string $function, array $array, int|string $key, array $moved): void {
        expect($function($array, $key))->toBe($moved);
    })->with([
        'before' => ['Sylament\Forms\array_move_before', ['a' => 1, 'b' => 2, 'c' => 3], 'b', ['b' => 2, 'a' => 1, 'c' => 3]],
        'after' => ['Sylament\Forms\array_move_after', ['a' => 1, 'b' => 2, 'c' => 3], 'b', ['a' => 1, 'c' => 3, 'b' => 2]],
        'a numeric string finding an integer key' => ['Sylament\Forms\array_move_after', [10, 20, 30], '1', [0 => 10, 2 => 30, 1 => 20]],
        'past an empty-string neighbour' => ['Sylament\Forms\array_move_before', ['' => 1, 'b' => 2], 'b', ['b' => 2, '' => 1]],
    ]);

    it('returns the array unchanged for an unknown key and at either edge', function (string $function, int|string $key): void {
        $array = ['a' => 1, 'b' => 2];

        expect($function($array, $key))->toBe($array);
    })->with([
        'an unknown key before' => ['Sylament\Forms\array_move_before', 'z'],
        'an unknown key after' => ['Sylament\Forms\array_move_after', 'z'],
        'the first key before' => ['Sylament\Forms\array_move_before', 'a'],
        'the last key after' => ['Sylament\Forms\array_move_after', 'b'],
    ]);
});
