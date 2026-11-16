<?php

declare(strict_types=1);

namespace Sylament\Tests\Architecture;

use Sylament\Support\Colors\Color;
use Sylament\Support\Colors\ColorManager;
use Sylament\Tests\Architecture\Styles\TextColumnIconShades;

/**
 * The text column's icons take their shade from a stylesheet, not from a policy: the item icon's policy resolves an
 * empty map, and a badged icon goes through the badge's own stylesheet, so no policy's contrast case reaches them.
 * Every shade involved is Filament's, so the pairings that miss the non-text ratio are pinned rather than fixed, and
 * the list goes red when a shade is raised and when a shipped palette lands that fails a pairing the others pass.
 * The surfaces are the table chrome's `bg-white dark:bg-gray-900` and the gray badge's light background; its dark one
 * is an alpha overlay this ratio cannot express.
 */
it('leaves the icon shades short of the non-text ratio in exactly the light-mode pairings Filament hands us', function (): void {
    $shades = TextColumnIconShades::read();
    $gray = ColorManager::DEFAULT_COLORS['gray'];
    $surfaces = ['light' => 'oklch(1 0 0)', 'dark' => $gray[900]];
    $pairings = [];

    foreach (ColorManager::DEFAULT_COLORS as $palette => $colors) {
        foreach ($surfaces as $mode => $surface) {
            $pairings["coloured item icon · {$palette} {$mode}"] = [$surface, $colors[$shades['itemColor']]];
        }
    }

    [$itemLight, $itemDark] = $shades['itemGray'];
    $pairings['uncoloured item icon light'] = [$surfaces['light'], $gray[$itemLight]];
    $pairings['uncoloured item icon dark'] = [$surfaces['dark'], $gray[$itemDark]];

    [$badgeLight] = $shades['badgeGray'];
    $pairings['gray badge icon light'] = [$gray[$shades['badgeSurface']], $gray[$badgeLight]];

    $failures = array_keys(array_filter(
        $pairings,
        static fn (array $pairing): bool => Color::calculateContrastRatio(...$pairing) < Color::WCAG_AA_NON_TEXT,
    ));

    expect($failures)->toBe([
        'coloured item icon · primary light',
        'coloured item icon · success light',
        'coloured item icon · warning light',
        'uncoloured item icon light',
        'gray badge icon light',
    ]);
});
