<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\Colors\Components;

use Sylament\Support\Colors\Color;
use Sylament\Support\Colors\ColorManager;
use Sylament\Support\Colors\Components\BadgeComponent;
use Sylament\Support\Colors\Components\ButtonComponent;
use Sylament\Support\Colors\Components\DropdownComponent\ItemComponent;
use Sylament\Support\Colors\Components\IconButtonComponent;
use Sylament\Support\Colors\Components\LinkComponent;
use Sylament\Support\Colors\Components\OutlinedButtonComponent;
use Sylament\Support\Colors\Components\ToggleComponent;

use function Sylament\Support\Tests\policy;

/**
 * Every slot every policy resolves, paired with the surface it is painted on and the ratio the
 * policy declares for it. `badge.css` hardcoded `text-color-600`, which measures 3.06:1 for
 * primary and warning, 3.03:1 for success and 4.35:1 for danger — these are the cases that fail
 * if that shade comes back.
 *
 * `ButtonComponent` is absent because its map picks a background before it picks text, so its
 * slots are not independent; it has its own two tests below.
 */
dataset('policy slots', function (): iterable {
    $gray = ColorManager::DEFAULT_COLORS['gray'];
    $white = 'oklch(1 0 0)';

    $policies = [
        BadgeComponent::class => [
            'text' => [fn (array $palette): string => $palette[50], Color::WCAG_AA_TEXT],
            'dark:text' => [fn (array $palette): string => $gray[600], Color::WCAG_AA_TEXT],
        ],
        LinkComponent::class => [
            'text' => [fn (array $palette): string => $gray[50], Color::WCAG_AA_TEXT],
            'dark:text' => [fn (array $palette): string => $gray[700], Color::WCAG_AA_TEXT],
        ],
        OutlinedButtonComponent::class => [
            'text' => [fn (array $palette): string => $gray[50], Color::WCAG_AA_TEXT],
            'dark:text' => [fn (array $palette): string => $gray[700], Color::WCAG_AA_TEXT],
        ],
        ToggleComponent::class => [
            'bg' => [fn (array $palette): string => $gray[50], Color::WCAG_AA_NON_TEXT],
            'text' => [fn (array $palette): string => $white, Color::WCAG_AA_NON_TEXT],
            'dark:bg' => [fn (array $palette): string => $gray[700], Color::WCAG_AA_NON_TEXT],
        ],
        IconButtonComponent::class => [
            'text' => [fn (array $palette): string => $gray[50], Color::WCAG_AA_NON_TEXT],
            'hover:text' => [fn (array $palette): string => $gray[50], Color::WCAG_AA_NON_TEXT],
            'dark:text' => [fn (array $palette): string => $gray[700], Color::WCAG_AA_NON_TEXT],
            'dark:hover:text' => [fn (array $palette): string => $gray[700], Color::WCAG_AA_NON_TEXT],
        ],
        ItemComponent::class => [
            'text' => [fn (array $palette): string => $white, Color::WCAG_AA_TEXT],
            'hover:text' => [fn (array $palette): string => $palette[50], Color::WCAG_AA_TEXT],
            'dark:text' => [fn (array $palette): string => $gray[900], Color::WCAG_AA_TEXT],
            'dark:hover:text' => [fn (array $palette): string => $palette[950], Color::WCAG_AA_TEXT],
        ],
    ];

    foreach ($policies as $class => $slots) {
        $shortName = mb_substr(mb_strrchr($class, '\\') ?: $class, 1);

        foreach ($slots as $slot => [$surface, $minRatio]) {
            foreach (array_keys(ColorManager::DEFAULT_COLORS) as $palette) {
                yield "{$shortName} {$slot} · {$palette}" => [$class, $slot, $surface, $minRatio, $palette];
            }
        }
    }
});

it(
    'resolves a shade clearing the declared ratio against the surface it is painted on',
    function (string $class, string $slot, callable $surface, float $minRatio, string $palette): void {
        $shades = ColorManager::DEFAULT_COLORS[$palette];

        $map = policy($class)->getColorMap($shades);

        expect(Color::calculateContrastRatio($surface($shades), $shades[$map[$slot]]))
            ->toBeGreaterThanOrEqual($minRatio);
    },
)->with('policy slots');

/**
 * The filled button in all four of its states, not just the resting one. One shade Filament's
 * candidate list offers admits no readable text at all — the test below proves it — so the two
 * failing pairings are pinned rather than asserted. Closing them means changing which shades a
 * filled button paints, a visual change to every danger button rather than a port fix; the list
 * is exact, so it goes red in either direction.
 */
it('clears WCAG AA in every filled-button state bar the shades that admit no readable text', function (): void {
    $states = [
        'default' => ['bg', 'text'],
        'hover' => ['hover:bg', 'hover:text'],
        'dark' => ['dark:bg', 'dark:text'],
        'dark hover' => ['dark:hover:bg', 'dark:hover:text'],
    ];

    $failures = [];

    foreach (ColorManager::DEFAULT_COLORS as $palette => $shades) {
        $map = policy(ButtonComponent::class)->getColorMap($shades);

        foreach ($states as $state => [$bgKey, $textKey]) {
            $text = $map[$textKey] === 0 ? 'oklch(1 0 0)' : $shades[$map[$textKey]];

            if (Color::calculateContrastRatio($shades[$map[$bgKey]], $text) >= Color::WCAG_AA_TEXT) {
                continue;
            }

            $failures[] = "{$palette} {$state}";
        }
    }

    expect($failures)->toBe([
        'danger hover',
        'danger dark hover',
    ]);
});

it('has no readable text at all on the background shades the filled button cannot make accessible', function (): void {
    $unreachable = ['danger' => 500];

    foreach ($unreachable as $palette => $shade) {
        $shades = ColorManager::DEFAULT_COLORS[$palette];

        $readable = array_filter(
            ['oklch(1 0 0)', ...array_values($shades)],
            fn (string $text): bool => Color::calculateContrastRatio($shades[$shade], $text) >= Color::WCAG_AA_TEXT,
        );

        expect($readable)->toBe([]);
    }
});
