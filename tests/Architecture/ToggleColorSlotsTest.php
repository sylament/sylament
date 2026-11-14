<?php

declare(strict_types=1);

namespace Sylament\Tests\Architecture;

use Sylament\Support\Colors\ColorManager;
use Sylament\Tests\Architecture\Styles\ToggleSlots;

/**
 * The toggle is the one component whose colour policy ships from support and whose stylesheet
 * ships from here, so neither package's suite sees both halves and only this test ties them
 * together. It scans stylesheets rather than mirroring a class under `src/`, which is what puts it
 * in the Architecture bucket; support's `PolicyShadeGridTest` makes the same two assertions for
 * the components whose stylesheets it owns.
 *
 * Both halves fail in silence. A class `utilities.css` does not define sets no variable, and a
 * variable no class sets makes the declaration reading it invalid at computed-value time — the
 * track paints its unset gray in both cases, with nothing in the console.
 */
dataset('adversarial palettes', function (): iterable {
    $shades = array_keys(ColorManager::DEFAULT_COLORS['gray']);

    yield 'uniformly dark' => [array_fill_keys($shades, 'oklch(0.15 0.05 260)')];

    yield 'pale but for the darkest shade' => [
        array_replace(array_fill_keys($shades, 'oklch(0.99 0.01 90)'), [950 => 'oklch(0.2 0.05 90)']),
    ];

    yield 'dark but for the lightest shade' => [
        array_replace(array_fill_keys($shades, 'oklch(0.15 0.05 260)'), [50 => 'oklch(0.99 0 0)']),
    ];

    foreach (ColorManager::DEFAULT_COLORS as $name => $palette) {
        yield $name => [$palette];
    }
});

it('emits only classes that utilities.css defines', function (array $palette): void {
    $css = ToggleSlots::supportStylesheet('utilities.css');
    $shadeClasses = ToggleSlots::shadeClasses($palette);

    expect($shadeClasses)->not->toBeEmpty();

    foreach ($shadeClasses as $shadeClass) {
        expect($css)->toContain('.' . str_replace(':', '\:', $shadeClass) . ' {');
    }
})->with('adversarial palettes');

/**
 * The class-defined check above passes for any grid shade under any slot key, so it says nothing
 * about whether the slots are the ones `toggle.css` reads. Swap the policy's `bg` slot for
 * `hover:bg` and every emitted class still has a rule — it just sets `--sy-hover-bg`, leaving the
 * `bg-(--sy-bg)` the checked track paints with unset. Hardcoding that declaration back to
 * `bg-color-600` is the same failure from the other side: a slot resolved for nothing.
 */
it('reads exactly the slot variables the policy resolves, in either state', function (array $palette): void {
    $set = ToggleSlots::variablesSetBy(ToggleSlots::shadeClasses($palette));
    sort($set);

    foreach (ToggleSlots::variablesReadByState() as $state => $read) {
        sort($read);

        expect($read)->toBe(
            $set,
            "ToggleComponent's slots and the {$state} rules in toggle.css disagree on which `--sy-` "
            . 'variables carry the resolved shades. A variable the rules read and no class sets is a '
            . 'declaration the browser drops; a slot the state never reads is a shade resolved for '
            . 'nothing, and the track paints its unset gray.',
        );
    }
})->with('adversarial palettes');
