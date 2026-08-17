<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\State\Casts;

use stdClass;
use Sylament\Schemas\State\Casts\EnumArrayStateCast;
use Sylament\Schemas\Tests\Fixtures\State\Casts\CastStatus;
use Sylament\Schemas\Tests\Fixtures\State\Casts\CastWeight;

it('returns an empty list for blank state', function (): void {
    $cast = new EnumArrayStateCast(CastStatus::class);

    expect($cast->get(null))->toBe([])
        ->and($cast->get(''))->toBe([])
        ->and($cast->get([]))->toBe([])
        ->and($cast->set(null))->toBe([])
        ->and($cast->set([]))->toBe([]);
});

it('gets an enum instance for every resolvable element', function (): void {
    expect(new EnumArrayStateCast(CastStatus::class)->get(['draft', 'published']))
        ->toBe([CastStatus::Draft, CastStatus::Published]);
});

it('drops unresolvable and blank elements rather than pushing null', function (): void {
    expect(new EnumArrayStateCast(CastStatus::class)->get(['draft', 'bogus', null, '', 'published']))
        ->toBe([CastStatus::Draft, CastStatus::Published]);
});

it('drops blank elements on set rather than coercing them to empty strings', function (): void {
    expect(new EnumArrayStateCast(CastStatus::class)->set([CastStatus::Draft, null, '', '   ', CastStatus::Published]))
        ->toBe(['draft', 'published']);
});

it('drops a non-scalar element on both sides, failing closed', function (): void {
    $cast = new EnumArrayStateCast(CastStatus::class);

    expect($cast->get(['draft', ['tampered'], new stdClass(), 'published']))->toBe([CastStatus::Draft, CastStatus::Published])
        ->and($cast->set([CastStatus::Draft, ['tampered'], new stdClass(), CastStatus::Published]))->toBe(['draft', 'published']);
});

it('returns a list, reindexing after a drop', function (): void {
    expect(array_keys(new EnumArrayStateCast(CastStatus::class)->get([3 => 'draft', 7 => 'bogus', 9 => 'published'])))
        ->toBe([0, 1]);
});

it('wraps a bare scalar state into a single-element list', function (): void {
    expect(new EnumArrayStateCast(CastStatus::class)->get('draft'))->toBe([CastStatus::Draft])
        ->and(new EnumArrayStateCast(CastWeight::class)->get(1))->toBe([CastWeight::Light]);
});

it('passes an already-cast instance element through', function (): void {
    expect(new EnumArrayStateCast(CastStatus::class)->get([CastStatus::Draft, 'published']))
        ->toBe([CastStatus::Draft, CastStatus::Published]);
});

it('sets every element to its string backing value', function (): void {
    expect(new EnumArrayStateCast(CastStatus::class)->set([CastStatus::Draft, 'published']))->toBe(['draft', 'published'])
        ->and(new EnumArrayStateCast(CastWeight::class)->set([CastWeight::Light, CastWeight::Heavy]))->toBe(['1', '2']);
});

it('string-coerces a non-enum element on set', function (): void {
    expect(new EnumArrayStateCast(CastStatus::class)->set([CastStatus::Draft, 3]))->toBe(['draft', '3']);
});

it('wraps a bare scalar state on set', function (): void {
    expect(new EnumArrayStateCast(CastStatus::class)->set(CastStatus::Draft))->toBe(['draft']);
});
