<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\Components\Concerns;

use Sylament\Support\Components\Concerns\HasBadge;
use Sylament\Support\Concerns\EvaluatesClosures;

final class HasBadgeFixture
{
    use EvaluatesClosures;
    use HasBadge;
}

it('is unset by default', function (): void {
    $fixture = new HasBadgeFixture();

    expect($fixture->hasBadge())->toBeFalse()
        ->and($fixture->getBadge())->toBeNull()
        ->and($fixture->getBadgeColor())->toBeNull();
});

it('casts a numeric badge to string on set', function (): void {
    expect(new HasBadgeFixture()->badge(3)->getBadge())->toBe('3')
        ->and(new HasBadgeFixture()->badge(2.5)->getBadge())->toBe('2.5');
});

it('casts a numeric badge a closure returns to string', function (): void {
    expect(new HasBadgeFixture()->badge(fn (): int => 3)->getBadge())->toBe('3')
        ->and(new HasBadgeFixture()->badge(fn (): float => 2.5)->getBadge())->toBe('2.5');
});

it('evaluates closures and injects the badge into the colour closure', function (): void {
    $fixture = new HasBadgeFixture()
        ->badge(fn (): string => 'new')
        ->badgeColor(fn (?string $badge): string => $badge === 'new' ? 'success' : 'gray');

    expect($fixture->hasBadge())->toBeTrue()
        ->and($fixture->getBadge())->toBe('new')
        ->and($fixture->getBadgeColor('new'))->toBe('success')
        ->and($fixture->getBadgeColor('old'))->toBe('gray');
});

it('accepts an inline shade map as the colour', function (): void {
    expect(new HasBadgeFixture()->badgeColor(['500' => '#fff'])->getBadgeColor())->toBe(['500' => '#fff']);
});
