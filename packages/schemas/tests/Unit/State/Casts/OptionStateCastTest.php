<?php

declare(strict_types=1);

use Sylament\Schemas\State\Casts\OptionStateCast;

enum OptionStateCastFixtureEnum: string
{
    case Draft = 'draft';
}

it('returns null for blank state when nullable', function (): void {
    $cast = new OptionStateCast();

    expect($cast->get(null))->toBeNull()
        ->and($cast->get(''))->toBeNull()
        ->and($cast->set(null))->toBeNull()
        ->and($cast->set(''))->toBeNull();
});

it('keeps blank state when not nullable', function (): void {
    $cast = new OptionStateCast(isNullable: false);

    expect($cast->get(''))->toBe('')
        ->and($cast->set(''))->toBe('');
});

it('narrows digit strings to int', function (): void {
    $cast = new OptionStateCast();

    expect($cast->get('42'))->toBe(42)
        ->and($cast->get('0'))->toBe(0)
        ->and($cast->get(7))->toBe(7);
});

it('keeps leading-zero strings as strings', function (): void {
    expect(new OptionStateCast()->get('007'))->toBe('007');
});

it('keeps digit strings wider than PHP_INT_MAX as strings', function (): void {
    $overflow = '9' . PHP_INT_MAX;

    expect(new OptionStateCast()->get($overflow))->toBe($overflow);
});

it('keeps non-numeric strings as strings', function (): void {
    expect(new OptionStateCast()->get('draft'))->toBe('draft');
});

it('unwraps a backed enum on both sides', function (): void {
    $cast = new OptionStateCast();

    expect($cast->get(OptionStateCastFixtureEnum::Draft))->toBe('draft')
        ->and($cast->set(OptionStateCastFixtureEnum::Draft))->toBe('draft');
});

it('fails closed on a non-scalar payload', function (): void {
    $cast = new OptionStateCast();

    expect($cast->get(['tampered']))->toBeNull()
        ->and($cast->set(['tampered']))->toBeNull()
        ->and($cast->get(new stdClass()))->toBeNull();
});

it('stringifies on set', function (): void {
    expect(new OptionStateCast()->set(42))->toBe('42');
});
