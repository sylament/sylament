<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\Component\Concerns;

use Sylament\Support\Service\Invoker;
use Sylament\Support\Tests\Fixtures\Component\Concerns\CanOrderColumnsFixture;
use Symfony\Component\DependencyInjection\Container;

beforeEach(function (): void {
    Invoker::setInstance(new Invoker(new Container()));
});

afterEach(function (): void {
    Invoker::unsetInstance();
});

it('has no column order on any breakpoint by default', function (): void {
    expect(new CanOrderColumnsFixture()->getColumnOrder())->toBe([
        'default' => null,
        'sm' => null,
        'md' => null,
        'lg' => null,
        'xl' => null,
        '2xl' => null,
    ]);
});

it('maps an integer order onto the lg breakpoint without a default', function (): void {
    expect(new CanOrderColumnsFixture()->columnOrder(2)->getColumnOrder())
        ->toBe(['lg' => 2]);
});

it('merges successive columnOrder() calls', function (): void {
    expect(new CanOrderColumnsFixture()->columnOrder(['md' => 2])->columnOrder(['xl' => 4])->getColumnOrder())
        ->toBe(['md' => 2, 'xl' => 4]);
});

it('assigns an appended scalar closure to the default breakpoint', function (): void {
    // Deliberately different from HasColumns, whose numeric-key fallback is lg —
    // Filament differs between the trait families; do not align them.
    expect(new CanOrderColumnsFixture()->columnOrder(fn (): int => 3)->getColumnOrder())
        ->toBe(['default' => 3]);
});

it('returns a single breakpoint value on request', function (): void {
    $fixture = new CanOrderColumnsFixture()->columnOrder(2);

    expect($fixture->getColumnOrder('lg'))->toBe(2)
        ->and($fixture->getColumnOrder('md'))->toBeNull();
});
