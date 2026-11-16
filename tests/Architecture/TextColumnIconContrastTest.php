<?php

declare(strict_types=1);

namespace Sylament\Tests\Architecture;

use Sylament\Tests\Architecture\Styles\PaletteCopies;
use Sylament\Tests\Architecture\Styles\TextColumnIconShades;

/**
 * The text column's icons take their shade from a stylesheet, not from a policy: the item icon's policy resolves an
 * empty map, and a badged icon goes through the badge's own stylesheet, so no policy's contrast case reaches them.
 * Every shade involved is Filament's, so the pairings that miss the non-text ratio are pinned rather than fixed, and
 * the list goes red when a shade is raised and when a shipped palette lands that fails a pairing the others pass.
 */
it('leaves the icon shades short of the non-text ratio in exactly the light-mode pairings Filament hands us', function (): void {
    expect(TextColumnIconShades::failingPairings(
        PaletteCopies::stylesheet('tables', 'components/text-column.css'),
        PaletteCopies::stylesheet('support', 'components/badge.css'),
    ))->toBe([
        'coloured item icon · primary light',
        'coloured item icon · success light',
        'coloured item icon · warning light',
        'uncoloured item icon light',
        'gray badge icon light',
    ]);
});

it('reports a dark-mode icon shade that sinks into the dark surface', function (): void {
    $textColumn = '.sy-ta-text-item { & > .sy-icon { @apply inline-block text-gray-400 dark:text-gray-800; &.sy-color { @apply text-color-500; } } }';
    $badge = '.sy-badge { &:not(.sy-color) { @apply bg-gray-50; & .sy-icon { @apply text-gray-400 dark:text-gray-500; } } }';

    expect(TextColumnIconShades::failingPairings($textColumn, $badge))->toContain('uncoloured item icon dark');
});
