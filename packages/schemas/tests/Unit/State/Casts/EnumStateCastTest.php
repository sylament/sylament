<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\State\Casts;

use stdClass;
use Sylament\Schemas\State\Casts\EnumStateCast;
use Sylament\Schemas\Tests\Fixtures\State\Casts\CastStatus;
use Sylament\Schemas\Tests\Fixtures\State\Casts\CastWeight;

it('gets an enum instance from its backing value', function (): void {
    expect(new EnumStateCast(CastStatus::class)->get('draft'))->toBe(CastStatus::Draft);
});

it('passes an already-cast instance through', function (): void {
    expect(new EnumStateCast(CastStatus::class)->get(CastStatus::Draft))->toBe(CastStatus::Draft);
});

it('gets null for blank state', function (): void {
    expect(new EnumStateCast(CastStatus::class)->get(null))->toBeNull()
        ->and(new EnumStateCast(CastStatus::class)->get(''))->toBeNull();
});

it('fails closed to null for an unknown value and for a non-scalar payload', function (): void {
    expect(new EnumStateCast(CastStatus::class)->get('bogus'))->toBeNull()
        ->and(new EnumStateCast(CastStatus::class)->get(['tampered']))->toBeNull()
        ->and(new EnumStateCast(CastStatus::class)->get(new stdClass()))->toBeNull();
});

it('fails closed to null for a scalar of the wrong backing type', function (): void {
    expect(new EnumStateCast(CastStatus::class)->get(5))->toBeNull()
        ->and(new EnumStateCast(CastWeight::class)->get('heavy'))->toBeNull();
});

it('fails closed to null for an instance of a different enum', function (): void {
    expect(new EnumStateCast(CastStatus::class)->get(CastWeight::Light))->toBeNull();
});

it('gets an int-backed case from its digit-string spelling', function (): void {
    expect(new EnumStateCast(CastWeight::class)->get('1'))->toBe(CastWeight::Light)
        ->and(new EnumStateCast(CastWeight::class)->get(1))->toBe(CastWeight::Light);
});

it('sets an enum instance to its string backing value', function (): void {
    expect(new EnumStateCast(CastStatus::class)->set(CastStatus::Published))->toBe('published')
        ->and(new EnumStateCast(CastWeight::class)->set(CastWeight::Light))->toBe('1');
});

it('sets a non-enum value through untouched', function (): void {
    expect(new EnumStateCast(CastStatus::class)->set('published'))->toBe('published')
        ->and(new EnumStateCast(CastStatus::class)->set(null))->toBeNull()
        ->and(new EnumStateCast(CastStatus::class)->set(3))->toBe(3);
});
