<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\Components\Concerns;

use Sylament\Support\Tests\Fixtures\Components\Concerns\CanSpanColumnsFixture;

it('spans a single column on every breakpoint by default', function (): void {
    $fixture = new CanSpanColumnsFixture();

    expect($fixture->getColumnSpan())->toBe([
        'default' => 1,
        'sm' => null,
        'md' => null,
        'lg' => null,
        'xl' => null,
        '2xl' => null,
    ])->and($fixture->getColumnStart())->toBe([
        'default' => null,
        'sm' => null,
        'md' => null,
        'lg' => null,
        'xl' => null,
        '2xl' => null,
    ]);
});

it('maps an integer span onto the lg breakpoint alongside an implicit single-column default', function (): void {
    expect(new CanSpanColumnsFixture()->columnSpan(2)->getColumnSpan())
        ->toBe(['default' => 1, 'lg' => 2]);
});

it('maps an integer start onto the lg breakpoint without a default', function (): void {
    // Filament's asymmetry: columnSpan(scalar) seeds default => 1, columnStart(scalar) does not.
    expect(new CanSpanColumnsFixture()->columnStart(2)->getColumnStart())
        ->toBe(['lg' => 2]);
});

it('spans the full width on the default breakpoint with columnSpanFull()', function (): void {
    expect(new CanSpanColumnsFixture()->columnSpanFull()->getColumnSpan())
        ->toBe(['default' => 'full']);
});

it('merges successive columnSpan() calls', function (): void {
    expect(new CanSpanColumnsFixture()->columnSpan(['md' => 2])->columnSpan(['xl' => 4])->getColumnSpan())
        ->toBe(['md' => 2, 'xl' => 4]);
});

it('assigns an appended scalar closure to the default breakpoint', function (): void {
    // Deliberately different from HasColumns, whose numeric-key fallback is lg —
    // Filament differs between the trait families; do not align them.
    expect(new CanSpanColumnsFixture()->columnSpan(fn (): int => 3)->getColumnSpan())
        ->toBe(['default' => 3]);
});

it('spreads an appended closure that returns a breakpoint map', function (): void {
    expect(new CanSpanColumnsFixture()->columnSpan(fn (): array => ['md' => 2])->getColumnSpan())
        ->toBe(['md' => 2]);
});

it('evaluates a closure stored under a breakpoint key', function (): void {
    expect(new CanSpanColumnsFixture()->columnSpan(['md' => fn (): int => 2])->getColumnSpan())
        ->toBe(['md' => 2]);
});

it('drops entries with an empty breakpoint key', function (): void {
    expect(new CanSpanColumnsFixture()->columnSpan(['' => 2, 'md' => 3])->getColumnSpan())
        ->toBe(['md' => 3]);
});

it('returns a single breakpoint value on request', function (): void {
    $fixture = new CanSpanColumnsFixture()->columnSpan(2);

    expect($fixture->getColumnSpan('lg'))->toBe(2)
        ->and($fixture->getColumnSpan('md'))->toBeNull();
});
