<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\Components\Concerns;

use Sylament\Support\Enums\Alignment;
use Sylament\Support\Tests\Fixtures\Components\Concerns\HasAlignmentFixture;

it('has no alignment by default', function (): void {
    expect(new HasAlignmentFixture()->getAlignment())->toBeNull();
});

it('stores an Alignment case', function (): void {
    expect(new HasAlignmentFixture()->alignment(Alignment::Center)->getAlignment())
        ->toBe(Alignment::Center);
});

it('normalizes a known alignment string to its enum case', function (): void {
    expect(new HasAlignmentFixture()->alignment('center')->getAlignment())
        ->toBe(Alignment::Center);
});

it('passes an unknown alignment string through as-is', function (): void {
    expect(new HasAlignmentFixture()->alignment('custom-alignment-class')->getAlignment())
        ->toBe('custom-alignment-class');
});

it('evaluates an alignment closure', function (): void {
    expect(new HasAlignmentFixture()->alignment(fn (): Alignment => Alignment::End)->getAlignment())
        ->toBe(Alignment::End)
        ->and(new HasAlignmentFixture()->alignment(fn (): string => 'end')->getAlignment())
        ->toBe(Alignment::End);
});

it('stores the matching case via each align helper', function (): void {
    expect(new HasAlignmentFixture()->alignStart()->getAlignment())->toBe(Alignment::Start)
        ->and(new HasAlignmentFixture()->alignCenter()->getAlignment())->toBe(Alignment::Center)
        ->and(new HasAlignmentFixture()->alignEnd()->getAlignment())->toBe(Alignment::End)
        ->and(new HasAlignmentFixture()->alignJustify()->getAlignment())->toBe(Alignment::Justify)
        ->and(new HasAlignmentFixture()->alignBetween()->getAlignment())->toBe(Alignment::Between)
        ->and(new HasAlignmentFixture()->alignLeft()->getAlignment())->toBe(Alignment::Left)
        ->and(new HasAlignmentFixture()->alignRight()->getAlignment())->toBe(Alignment::Right);
});

it('yields no alignment when the helper condition is false', function (): void {
    expect(new HasAlignmentFixture()->alignEnd(false)->getAlignment())->toBeNull();
});

it('evaluates a helper condition closure', function (): void {
    expect(new HasAlignmentFixture()->alignEnd(fn (): bool => true)->getAlignment())
        ->toBe(Alignment::End)
        ->and(new HasAlignmentFixture()->alignEnd(fn (): bool => false)->getAlignment())
        ->toBeNull();
});

it('lets the last alignment call win', function (): void {
    expect(new HasAlignmentFixture()->alignStart()->alignEnd()->getAlignment())
        ->toBe(Alignment::End);
});

it('reports dynamic alignment only for stored closures', function (): void {
    expect(new HasAlignmentFixture()->hasDynamicAlignment())->toBeFalse()
        ->and(new HasAlignmentFixture()->alignment(Alignment::Start)->hasDynamicAlignment())->toBeFalse()
        ->and(new HasAlignmentFixture()->alignment(fn (): ?Alignment => null)->hasDynamicAlignment())->toBeTrue()
        // The align helpers always store a condition-wrapping closure, even for literal conditions.
        ->and(new HasAlignmentFixture()->alignStart()->hasDynamicAlignment())->toBeTrue();
});

it('builds the alignment class from the enum value', function (): void {
    $fixture = new HasAlignmentFixture()->alignment(Alignment::Center);

    expect($fixture->getAlignmentClass())->toBe('sy-align-center');
});

it('passes a custom alignment class string through', function (): void {
    $fixture = new HasAlignmentFixture()->alignment('my-align');

    expect($fixture->getAlignmentClass())->toBe('my-align');
});

it('has no alignment class when unaligned', function (): void {
    expect(new HasAlignmentFixture()->getAlignmentClass())->toBeNull();
});
