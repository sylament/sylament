<?php

declare(strict_types=1);

namespace Sylament\Tests\Architecture;

use Sylament\Support\Colors\Components\LinkComponent;
use Sylament\Tests\Architecture\Styles\ColorPolicies;
use Sylament\Tests\Architecture\Styles\PaletteCopies;

/**
 * A shade a policy resolves reaches the page as a class whose `utilities.css` rule sets a `--sy-` slot variable, which
 * the component stylesheet's coloured rules read. Every link fails in silence: a class with no rule sets no variable, a
 * variable nobody sets makes the declaration reading it invalid at computed-value time, and the element paints the
 * slot's unset default with nothing in the console. The policies come from the container, so a new one is scanned
 * without anyone editing this file.
 */
dataset('adversarial palettes', function (): iterable {
    foreach (ColorPolicies::palettes() as $name => $palette) {
        yield $name => [$palette];
    }
});

it('emits only shade classes that utilities.css defines', function (string $policy, array $palette): void {
    expect(ColorPolicies::undefinedClasses(ColorPolicies::shadeClasses($policy, $palette)))->toBe([]);
})->with(ColorPolicies::policies())->with('adversarial palettes');

/**
 * The class-defined check above passes for any grid shade under any slot key, so it says nothing about whether the
 * slots are the ones the stylesheet reads: swap a policy's `text` slot for `hover:text` and every emitted class still
 * has a rule, setting `--sy-hover-text` and leaving the `text-(--sy-text)` the component paints with unset.
 */
it('sets exactly the slot variables its stylesheet reads', function (string $policy, array $palette): void {
    $stylesheet = ColorPolicies::stylesheetOf($policy);

    expect($stylesheet)->not->toBeNull("{$policy} resolves shades, but no stylesheet carries its name: name it in ColorPolicies::STYLESHEETS with the reason.");

    [$package, $name] = $stylesheet;

    expect(ColorPolicies::mismatches(
        $policy,
        PaletteCopies::stylesheet($package, $name),
        ColorPolicies::slotsSetBy(ColorPolicies::shadeClasses($policy, $palette)),
    ))->toBe([], "{$policy} and the coloured rules of {$name} in {$package} disagree on which `--sy-` slot variables carry the resolved shades.");
})->with(ColorPolicies::slotResolvingPolicies())->with('adversarial palettes');

it('leaves no stylesheet reading a slot that no policy resolves', function (): void {
    expect(ColorPolicies::unclaimedStylesheets())->toBe([]);
});

it('reports a slot the stylesheet reads and the policy does not set', function (): void {
    $css = '.sy-link { &.sy-color { @apply text-(--sy-text) bg-(--sy-bg); } &:not(.sy-color) { @apply text-(--sy-dark-text); } }';

    expect(ColorPolicies::mismatches(LinkComponent::class, $css, ['--sy-text', '--sy-hover-text']))->toBe([
        'coloured rules' => ['unset' => ['--sy-bg'], 'unread' => ['--sy-hover-text']],
    ]);
});
