<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\State\Casts;

use JsonException;
use LogicException;
use stdClass;
use Sylament\Schemas\State\Casts\KeyValueStateCast;

it('returns an empty array for blank state both ways', function (): void {
    $cast = new KeyValueStateCast();

    expect($cast->get(null))->toBe([])
        ->and($cast->get(''))->toBe([])
        ->and($cast->get([]))->toBe([])
        ->and($cast->set(null))->toBe([])
        ->and($cast->set(''))->toBe([])
        ->and($cast->set([]))->toBe([]);
});

it('plucks a row list into a map, dropping the blank key and keeping the last duplicate', function (): void {
    $cast = new KeyValueStateCast();

    expect($cast->get([
        ['key' => 'a', 'value' => 'one'],
        ['key' => '', 'value' => 'dropped'],
        ['key' => 'a', 'value' => 'two'],
        ['key' => 'b', 'value' => null],
    ]))->toBe(['a' => 'two', 'b' => null]);
});

it('drops a row whose key is missing', function (): void {
    expect(new KeyValueStateCast()->get([
        ['value' => 'orphan'],
        ['key' => 'a', 'value' => 'one'],
    ]))->toBe(['a' => 'one']);
});

it('expands a map into a row list, dropping the blank key', function (): void {
    $cast = new KeyValueStateCast();

    expect($cast->set(['a' => 'one', '' => 'dropped', 'b' => null]))->toBe([
        ['key' => 'a', 'value' => 'one'],
        ['key' => 'b', 'value' => null],
    ]);
});

it('is idempotent in both directions', function (): void {
    $cast = new KeyValueStateCast();

    $rows = [['key' => 'a', 'value' => 'one']];
    $map = ['a' => 'one'];

    expect($cast->get($map))->toBe($map)
        ->and($cast->set($rows))->toBe($rows);
});

it('decodes a JSON string in both directions', function (): void {
    $cast = new KeyValueStateCast();

    expect($cast->get('[{"key":"a","value":"one"}]'))->toBe(['a' => 'one'])
        ->and($cast->get('{"a":"one"}'))->toBe(['a' => 'one'])
        ->and($cast->set('{"a":"one"}'))->toBe([['key' => 'a', 'value' => 'one']])
        ->and($cast->set('[{"key":"a","value":"one"}]'))->toBe([['key' => 'a', 'value' => 'one']]);
});

it('throws on malformed JSON rather than fatalling', function (): void {
    new KeyValueStateCast()->get('{"a":');
})->throws(JsonException::class);

it('refuses a state that is neither an array nor the JSON string of one', function (): void {
    new KeyValueStateCast()->get(new stdClass());
})->throws(LogicException::class, 'stdClass');

it('refuses a JSON string that decodes to a scalar', function (): void {
    new KeyValueStateCast()->set('42');
})->throws(LogicException::class);
