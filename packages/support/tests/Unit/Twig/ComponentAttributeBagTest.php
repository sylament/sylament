<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\Twig;

use Sylament\Support\Twig\ComponentAttributeBag;

it('holds no attributes by default', function (): void {
    expect(new ComponentAttributeBag()->getAttributes())->toBe([]);
});

it('normalizes a class string into a whitespace-split token list', function (): void {
    $bag = new ComponentAttributeBag(['class' => "sy  sy-section\n custom"]);

    expect($bag->getAttributes())->toBe(['class' => ['sy', 'sy-section', 'custom']]);
});

it('resolves a conditional class map to its truthy keys', function (): void {
    $bag = new ComponentAttributeBag(['class' => ['always', 'on' => true, 'off' => false, ['nested other']]]);

    expect($bag->getAttributes())->toBe(['class' => ['always', 'on', 'nested', 'other']]);
});

it('normalizes a style string into a declaration list', function (): void {
    $bag = new ComponentAttributeBag(['style' => 'color: red; margin: 0 ;']);

    expect($bag->getAttributes())->toBe(['style' => ['color: red', 'margin: 0']]);
});

it('resolves a style map to property-value declarations', function (): void {
    $bag = new ComponentAttributeBag(['style' => ['color' => 'red', '--sy-gap: 1rem']]);

    expect($bag->getAttributes())->toBe(['style' => ['color: red', '--sy-gap: 1rem']]);
});

it('keeps other attribute values untouched', function (): void {
    $bag = new ComponentAttributeBag(['data-owner' => 'billing', 'aria-hidden' => true]);

    expect($bag->getAttributes())->toBe(['data-owner' => 'billing', 'aria-hidden' => true]);
});

it('merge appends class tokens with defaults first', function (): void {
    $bag = new ComponentAttributeBag(['class' => 'existing'])->merge(['class' => 'default']);

    expect($bag->getAttributes())->toBe(['class' => ['default', 'existing']]);
});

it('merge appends style declarations with defaults first', function (): void {
    $bag = new ComponentAttributeBag(['style' => 'color: red'])->merge(['style' => 'margin: 0']);

    expect($bag->getAttributes())->toBe(['style' => ['margin: 0', 'color: red']]);
});

it('merge keeps the existing value for a colliding non-appendable key', function (): void {
    $bag = new ComponentAttributeBag(['data-owner' => 'existing'])->merge(['data-owner' => 'default']);

    expect($bag->getAttributes())->toBe(['data-owner' => 'existing']);
});

it('merge adds keys absent from the existing attributes', function (): void {
    $bag = new ComponentAttributeBag(['data-a' => '1'])->merge(['data-b' => '2']);

    expect($bag->getAttributes())->toBe(['data-b' => '2', 'data-a' => '1']);
});

it('merge returns a new instance and leaves the original untouched', function (): void {
    $original = new ComponentAttributeBag(['class' => 'existing']);
    $merged = $original->merge(['class' => 'default']);

    expect($merged)->not->toBe($original)
        ->and($original->getAttributes())->toBe(['class' => ['existing']]);
});
