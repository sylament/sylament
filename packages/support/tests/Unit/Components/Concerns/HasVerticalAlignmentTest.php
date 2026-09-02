<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\Components\Concerns;

use Sylament\Support\Enums\VerticalAlignment;
use Sylament\Support\Tests\Fixtures\Components\Concerns\HasVerticalAlignmentFixture;

it('has no vertical alignment by default', function (): void {
    expect(new HasVerticalAlignmentFixture()->getVerticalAlignment())->toBeNull();
});

it('stores a VerticalAlignment case', function (): void {
    expect(new HasVerticalAlignmentFixture()->verticalAlignment(VerticalAlignment::Center)->getVerticalAlignment())
        ->toBe(VerticalAlignment::Center);
});

it('normalizes a known vertical alignment string to its enum case', function (): void {
    expect(new HasVerticalAlignmentFixture()->verticalAlignment('center')->getVerticalAlignment())
        ->toBe(VerticalAlignment::Center);
});

it('passes an unknown vertical alignment string through as-is', function (): void {
    expect(new HasVerticalAlignmentFixture()->verticalAlignment('weird')->getVerticalAlignment())
        ->toBe('weird');
});

it('evaluates a vertical alignment closure', function (): void {
    expect(new HasVerticalAlignmentFixture()->verticalAlignment(fn (): VerticalAlignment => VerticalAlignment::End)->getVerticalAlignment())
        ->toBe(VerticalAlignment::End)
        ->and(new HasVerticalAlignmentFixture()->verticalAlignment(fn (): string => 'end')->getVerticalAlignment())
        ->toBe(VerticalAlignment::End);
});

it('stores the matching case via each vertical align helper', function (): void {
    expect(new HasVerticalAlignmentFixture()->verticallyAlignStart()->getVerticalAlignment())->toBe(VerticalAlignment::Start)
        ->and(new HasVerticalAlignmentFixture()->verticallyAlignCenter()->getVerticalAlignment())->toBe(VerticalAlignment::Center)
        ->and(new HasVerticalAlignmentFixture()->verticallyAlignEnd()->getVerticalAlignment())->toBe(VerticalAlignment::End);
});

it('yields no vertical alignment when the helper condition is false', function (): void {
    expect(new HasVerticalAlignmentFixture()->verticallyAlignEnd(false)->getVerticalAlignment())->toBeNull();
});

it('evaluates a vertical align helper condition closure', function (): void {
    expect(new HasVerticalAlignmentFixture()->verticallyAlignEnd(fn (): bool => true)->getVerticalAlignment())
        ->toBe(VerticalAlignment::End)
        ->and(new HasVerticalAlignmentFixture()->verticallyAlignEnd(fn (): bool => false)->getVerticalAlignment())
        ->toBeNull();
});

it('lets the last vertical alignment call win', function (): void {
    expect(new HasVerticalAlignmentFixture()->verticallyAlignStart()->verticallyAlignEnd()->getVerticalAlignment())
        ->toBe(VerticalAlignment::End);
});

it('reports dynamic vertical alignment only for stored closures', function (): void {
    expect(new HasVerticalAlignmentFixture()->hasDynamicVerticalAlignment())->toBeFalse()
        ->and(new HasVerticalAlignmentFixture()->verticalAlignment(VerticalAlignment::Start)->hasDynamicVerticalAlignment())->toBeFalse()
        ->and(new HasVerticalAlignmentFixture()->verticalAlignment(fn (): ?VerticalAlignment => null)->hasDynamicVerticalAlignment())->toBeTrue()
        ->and(new HasVerticalAlignmentFixture()->verticallyAlignStart()->hasDynamicVerticalAlignment())->toBeTrue();
});

it('builds the vertical alignment class from the enum value', function (): void {
    $fixture = new HasVerticalAlignmentFixture()->verticalAlignment(VerticalAlignment::Center);

    expect($fixture->getVerticalAlignmentClass())->toBe('sy-vertical-align-center');
});

it('passes a custom vertical alignment class string through', function (): void {
    $fixture = new HasVerticalAlignmentFixture()->verticalAlignment('my-vertical-align');

    expect($fixture->getVerticalAlignmentClass())->toBe('my-vertical-align');
});

it('has no vertical alignment class when unaligned', function (): void {
    expect(new HasVerticalAlignmentFixture()->getVerticalAlignmentClass())->toBeNull();
});
