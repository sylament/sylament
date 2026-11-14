<?php

declare(strict_types=1);

namespace Sylament\Tests\Architecture;

use Sylament\Support\Colors\ColorManager;
use Sylament\Tests\Architecture\Styles\PaletteCopies;

/**
 * The six shipped palettes are named in four places — `ColorManager::DEFAULT_COLORS` in PHP, and in
 * CSS the `:root` floor that gives a consumer who registers nothing coloured components without
 * calling `sylamentStyles()`, the `@theme inline` bridge that makes `@apply bg-<palette>-600`
 * compile, and the `.sy-color-<palette>` swap classes that point the dynamic `color` palette at
 * one of them. This test is what that duplication was traded for: it is the only thing tying the
 * four copies together, and without it any one of them can be edited alone and every other test
 * stays green.
 *
 * It reads stylesheets rather than exercising a class, so it sits in the Architecture bucket
 * rather than mirroring `src/`.
 */

it('defines a :root palette for exactly the colours DEFAULT_COLORS ships', function (): void {
    expect(array_keys(PaletteCopies::cssFloor()))->toBe(
        array_keys(PaletteCopies::phpFloor()),
        'index.css and ColorManager::DEFAULT_COLORS disagree on which palettes exist. A palette in '
        . 'one and not the other renders as either dead CSS or an uncoloured component.',
    );
});

it('matches DEFAULT_COLORS shade for shade', function (string $palette): void {
    expect(PaletteCopies::cssFloor()[$palette] ?? [])->toBe(
        PaletteCopies::phpFloor()[$palette],
        "The `{$palette}` palette in index.css no longer matches ColorManager::DEFAULT_COLORS. The "
        . 'PHP copy is what the contrast policies measure against and the CSS copy is what the '
        . 'browser paints, so any drift means shades are chosen for colours that are not on screen.',
    );
})->with(array_keys(ColorManager::DEFAULT_COLORS));

it('bridges a Tailwind token for exactly the floor palettes and the dynamic one', function (): void {
    $expected = [...array_keys(ColorManager::DEFAULT_COLORS), 'color'];
    sort($expected);

    expect(array_keys(PaletteCopies::themeBridge()))->toBe(
        $expected,
        'The `@theme inline` block in index.css and ColorManager::DEFAULT_COLORS disagree on which '
        . 'palettes exist. Without a bridged token, `@apply bg-<palette>-600` does not compile, so '
        . 'a palette the floor defines paints nothing.',
    );
});

it('bridges every palette shade by shade', function (string $palette): void {
    expect(PaletteCopies::themeBridge()[$palette] ?? [])->toBe(
        PaletteCopies::shadeVariables($palette),
        "The `{$palette}` bridge in index.css no longer maps every shade of the grid onto its own "
        . '`--sy-` property. A missing or misdirected shade compiles to a declaration reading an '
        . 'unset variable, which the browser drops at computed-value time and reports nowhere.',
    );
})->with([...array_keys(ColorManager::DEFAULT_COLORS), 'color']);

it('ships a swap class for exactly the palettes DEFAULT_COLORS defines', function (): void {
    expect(array_keys(PaletteCopies::swapClasses()))->toBe(
        array_keys(PaletteCopies::phpFloor()),
        'The `.sy-color-<palette>` rules in utilities.css and ColorManager::DEFAULT_COLORS disagree '
        . 'on which palettes exist. `getComponentClasses()` emits one of those class names for '
        . 'every coloured component, and without a rule to match it the dynamic `color` palette '
        . 'stays unset and the component renders uncoloured.',
    );
});

it('swaps every palette shade by shade', function (string $palette): void {
    expect(PaletteCopies::swapClasses()[$palette] ?? [])->toBe(
        PaletteCopies::shadeVariables($palette),
        "The `.sy-color-{$palette}` rule in utilities.css no longer points every shade of the grid "
        . 'at its own `--sy-` property. A shade left out is a shade the resolved colour map can '
        . 'still choose, and the carrier class reading it then sets its slot to nothing.',
    );
})->with(array_keys(ColorManager::DEFAULT_COLORS));
