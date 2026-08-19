<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\Components\Concerns;

use Sylament\Support\Tests\Fixtures\Components\Concerns\HasFromBreakpointFixture;

it('has no from breakpoint by default', function (): void {
    expect(new HasFromBreakpointFixture()->getFromBreakpoint())->toBeNull();
});

it('stores a from breakpoint string', function (): void {
    expect(new HasFromBreakpointFixture()->from('md')->getFromBreakpoint())->toBe('md');
});

it('evaluates a from breakpoint closure', function (): void {
    expect(new HasFromBreakpointFixture()->from(fn (): string => 'lg')->getFromBreakpoint())->toBe('lg');
});

it('clears the from breakpoint with null', function (): void {
    expect(new HasFromBreakpointFixture()->from('md')->from(null)->getFromBreakpoint())->toBeNull();
});
