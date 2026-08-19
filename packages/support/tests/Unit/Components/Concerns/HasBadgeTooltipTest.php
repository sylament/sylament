<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\Components\Concerns;

use Sylament\Support\Components\Concerns\HasBadgeTooltip;
use Sylament\Support\Concerns\EvaluatesClosures;
use Twig\Markup;

final class HasBadgeTooltipFixture
{
    use EvaluatesClosures;
    use HasBadgeTooltip;
}

it('has no badge tooltip by default', function (): void {
    expect(new HasBadgeTooltipFixture()->getBadgeTooltip())->toBeNull();
});

it('stores a string tooltip', function (): void {
    expect(new HasBadgeTooltipFixture()->badgeTooltip('Three unread')->getBadgeTooltip())
        ->toBe('Three unread');
});

it('passes Markup through untouched', function (): void {
    $markup = new Markup('<b>Three</b>', 'UTF-8');

    expect(new HasBadgeTooltipFixture()->badgeTooltip($markup)->getBadgeTooltip())->toBe($markup);
});

it('evaluates a closure with the badge injected', function (): void {
    $fixture = new HasBadgeTooltipFixture()
        ->badgeTooltip(fn (?string $badge): string => "{$badge} unread");

    expect($fixture->getBadgeTooltip('7'))->toBe('7 unread')
        ->and($fixture->getBadgeTooltip())->toBe(' unread');
});
