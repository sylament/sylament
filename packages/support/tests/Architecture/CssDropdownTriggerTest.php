<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Architecture;

use UnexpectedValueException;

use function dirname;

/**
 * What `.sy-dropdown-trigger` declares is invisible to a render — the markup the Dropdown render
 * tests assert was never what went wrong in this rule — and no suite here drives a browser, so its
 * source is the only place it can be held to the Filament original. It reads a stylesheet rather
 * than exercising a class, so it sits in the Architecture bucket rather than mirroring `src/`.
 *
 * @return list<string> the utilities the trigger rule applies
 */
function dropdownTriggerUtilities(): array
{
    $path = dirname(__DIR__, 2) . '/assets/src/styles/components/dropdown.css';

    $css = file_get_contents($path);

    if ($css === false) {
        throw new UnexpectedValueException("Unable to read {$path}.");
    }

    if (preg_match('/^\.sy-dropdown-trigger\s*\{(?<declarations>[^}]*)\}/m', $css, $rule) !== 1) {
        throw new UnexpectedValueException('No `.sy-dropdown-trigger` rule in dropdown.css — the trigger has lost its stylesheet or been renamed.');
    }

    if (preg_match('/@apply\s+(?<utilities>[^;]+);/', $rule['declarations'], $apply) !== 1) {
        throw new UnexpectedValueException('The `.sy-dropdown-trigger` rule declares no `@apply` line.');
    }

    $utilities = preg_split('/\s+/', mb_trim($apply['utilities']));

    if ($utilities === false) {
        throw new UnexpectedValueException("Unable to split the utilities \"{$apply['utilities']}\".");
    }

    return $utilities;
}

it('lays the dropdown trigger out as a block-level flex box', function (): void {
    $utilities = dropdownTriggerUtilities();

    expect(in_array('flex', $utilities, true))->toBeTrue(
        'The `.sy-dropdown-trigger` rule no longer applies `flex`, which is what keeps the trigger '
        . 'box the same height as the trigger it wraps. An inline-level trigger sits on its '
        . "wrapper's baseline instead, so the wrapper reserves descender space nothing fills and "
        . 'every ActionGroup trigger rides above the sibling actions it is centred against.',
    )->and(in_array('inline-flex', $utilities, true))->toBeFalse(
        'The `.sy-dropdown-trigger` rule applies `inline-flex`, reintroducing exactly the baseline '
        . 'slack `flex` is there to remove.',
    );
});

it('gives the dropdown trigger a pointer cursor', function (): void {
    $utilities = dropdownTriggerUtilities();

    expect(in_array('cursor-pointer', $utilities, true))->toBeTrue(
        'The `.sy-dropdown-trigger` rule no longer applies `cursor-pointer`. The trigger is a plain '
        . '`div` carrying the toggle action, so any trigger content that is not itself a button or '
        . 'a link — markup, plain text — falls back to `cursor: auto` and does not read as '
        . 'clickable. Applied: ' . implode(' ', $utilities) . '.',
    );
});
