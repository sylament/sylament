<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\State\Casts;

use stdClass;
use Sylament\Schemas\State\Casts\OptionsArrayStateCast;
use Sylament\Schemas\Tests\Fixtures\State\Casts\OptionStateCastFixtureEnum;

it('returns an empty list for blank state by default', function (): void {
    $cast = new OptionsArrayStateCast();

    expect($cast->get(null))->toBe([])
        ->and($cast->get(''))->toBe([])
        ->and($cast->get([]))->toBe([])
        ->and($cast->set(null))->toBe([])
        ->and($cast->set([]))->toBe([]);
});

it('returns null for blank state when nullable', function (): void {
    $cast = new OptionsArrayStateCast(isNullable: true);

    expect($cast->get(null))->toBeNull()
        ->and($cast->get([]))->toBeNull()
        ->and($cast->set(null))->toBeNull()
        ->and($cast->set([]))->toBeNull();
});

it('normalizes every element of an array', function (): void {
    $cast = new OptionsArrayStateCast();

    expect($cast->get(['42', 'draft', 7]))->toBe([42, 'draft', 7])
        ->and($cast->set(['42', 'draft', 7]))->toBe(['42', 'draft', '7']);
});

it('wraps a scalar state into a single-element list', function (): void {
    $cast = new OptionsArrayStateCast();

    expect($cast->get('draft'))->toBe(['draft'])
        ->and($cast->get('42'))->toBe([42])
        ->and($cast->set(42))->toBe(['42']);
});

it('keeps leading-zero and overflow digit strings as strings', function (): void {
    $overflow = '9' . PHP_INT_MAX;

    expect(new OptionsArrayStateCast()->get(['007', $overflow]))->toBe(['007', $overflow]);
});

it('unwraps backed enum elements on both sides', function (): void {
    $cast = new OptionsArrayStateCast();

    expect($cast->get([OptionStateCastFixtureEnum::Draft]))->toBe(['draft'])
        ->and($cast->set([OptionStateCastFixtureEnum::Draft]))->toBe(['draft']);
});

it('drops a nested array element, failing closed', function (): void {
    $cast = new OptionsArrayStateCast();

    expect($cast->get(['draft', ['tampered'], 'published']))->toBe(['draft', 'published'])
        ->and($cast->set(['draft', ['tampered'], 'published']))->toBe(['draft', 'published']);
});

it('drops a non-scalar object element, failing closed', function (): void {
    $cast = new OptionsArrayStateCast();

    expect($cast->get(['draft', new stdClass()]))->toBe(['draft'])
        ->and($cast->set(['draft', new stdClass()]))->toBe(['draft']);
});

it('drops blank elements', function (): void {
    $cast = new OptionsArrayStateCast();

    expect($cast->get(['draft', null, '', 'published']))->toBe(['draft', 'published'])
        ->and($cast->set(['draft', null, '', 'published']))->toBe(['draft', 'published']);
});

it('returns a list, reindexing after a drop', function (): void {
    expect(array_keys(new OptionsArrayStateCast()->get([3 => 'draft', 7 => ['tampered'], 9 => 'published'])))
        ->toBe([0, 1]);
});

it('returns an int element unchanged', function (): void {
    $cast = new OptionsArrayStateCast();

    expect($cast->get([PHP_INT_MAX, PHP_INT_MIN]))->toBe([PHP_INT_MAX, PHP_INT_MIN]);
});
