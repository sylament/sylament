<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\Components\Concerns;

use Sylament\Support\Tests\Fixtures\Components\Concerns\HasExtraAttributesFixture;
use Sylament\Support\Twig\ComponentAttributeBag;

it('has no extra attributes by default', function (): void {
    $fixture = new HasExtraAttributesFixture();

    expect($fixture->hasExtraAttributes())->toBeFalse()
        ->and($fixture->getExtraAttributes())->toBe([]);
});

it('replaces previously set attributes by default', function (): void {
    $fixture = new HasExtraAttributesFixture()
        ->extraAttributes(['data-a' => '1'])
        ->extraAttributes(['data-b' => '2']);

    expect($fixture->getExtraAttributes())->toBe(['data-b' => '2']);
});

it('appends entries with merge', function (): void {
    $fixture = new HasExtraAttributesFixture()
        ->extraAttributes(['data-a' => '1'])
        ->extraAttributes(['data-b' => '2'], merge: true);

    expect($fixture->getExtraAttributes())->toEqualCanonicalizing(['data-a' => '1', 'data-b' => '2']);
});

it('evaluates closure entries through the real Invoker', function (): void {
    $fixture = new HasExtraAttributesFixture()
        ->extraAttributes(fn (): array => ['data-computed' => 'yes']);

    expect($fixture->getExtraAttributes())->toBe(['data-computed' => 'yes']);
});

it('concatenates class tokens across merged entries', function (): void {
    $fixture = new HasExtraAttributesFixture()
        ->extraAttributes(['class' => 'one'])
        ->extraAttributes(['class' => 'two three'], merge: true);

    expect($fixture->getExtraAttributes())->toBe(['class' => ['two', 'three', 'one']]);
});

it('keeps the first entry value for a colliding scalar key', function (): void {
    // Laravel fold semantics, mirrored from Filament: later entries merge in as
    // defaults, so the earliest entry wins a scalar collision.
    $fixture = new HasExtraAttributesFixture()
        ->extraAttributes(['data-owner' => 'first'])
        ->extraAttributes(['data-owner' => 'second'], merge: true);

    expect($fixture->getExtraAttributes())->toBe(['data-owner' => 'first']);
});

it('exposes the merged attributes as a bag', function (): void {
    $bag = new HasExtraAttributesFixture()
        ->extraAttributes(['class' => 'custom'])
        ->getExtraAttributeBag();

    expect($bag)->toBeInstanceOf(ComponentAttributeBag::class)
        ->and($bag->getAttributes())->toBe(['class' => ['custom']]);
});

it('reports extra attributes after setting an empty array', function (): void {
    // Pins Filament behavior: an empty entry still counts as configured.
    expect(new HasExtraAttributesFixture()->extraAttributes([])->hasExtraAttributes())->toBeTrue();
});
