<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Architecture;

use UnexpectedValueException;

use function dirname;

/**
 * `.sy-sr-only` is the one utility in this stylesheet that other packages name by hand: forms'
 * field wrapper, its table repeater and its key-value chrome write it into their markup, and the
 * select controller and the tags input write it onto the live regions they mint, so support's rule
 * is a cross-package contract rather than a local class. Nothing renders it from this package, and
 * the emitters live where a support suite cannot see them, so its source is the only place the rule
 * can be held. It reads a stylesheet rather than exercising a class, so it sits in the Architecture
 * bucket rather than mirroring `src/`.
 *
 * @return list<string> the utilities the screen-reader rule applies
 */
function screenReaderUtilities(): array
{
    $path = dirname(__DIR__, 2) . '/assets/src/styles/utilities.css';

    $css = file_get_contents($path);

    if ($css === false) {
        throw new UnexpectedValueException("Unable to read {$path}.");
    }

    if (preg_match('/^\.sy-sr-only\s*\{(?<declarations>[^}]*)\}/m', $css, $rule) !== 1) {
        throw new UnexpectedValueException('No `.sy-sr-only` rule in utilities.css — the screen-reader utility has lost its stylesheet or been renamed. Forms\' field wrapper, its table repeater, its key-value chrome, the select controller and the tags input all emit the class by hand, so a rename here un-hides every screen-reader-only label with no test in any package failing.');
    }

    if (preg_match('/@apply\s+(?<utilities>[^;]+);/', $rule['declarations'], $apply) !== 1) {
        throw new UnexpectedValueException('The `.sy-sr-only` rule declares no `@apply` line.');
    }

    $utilities = preg_split('/\s+/', mb_trim($apply['utilities']));

    if ($utilities === false) {
        throw new UnexpectedValueException("Unable to split the utilities \"{$apply['utilities']}\".");
    }

    return $utilities;
}

it('hides the screen-reader-only utility visually while leaving it readable', function (): void {
    $utilities = screenReaderUtilities();

    expect(in_array('sr-only', $utilities, true))->toBeTrue(
        'The `.sy-sr-only` rule no longer applies `sr-only`. The class is emitted from five '
        . 'places in forms — the field wrapper\'s hidden labels, the table repeater\'s chrome '
        . "headers, the key-value table's chrome headers, the select controller's status region "
        . 'and the tags input\'s announcement region — and every one of them relies on this rule '
        . 'alone to take the element out of the visual flow while leaving it in the accessibility '
        . 'tree. Applied: ' . implode(' ', $utilities) . '.',
    )->and(in_array('hidden', $utilities, true))->toBeFalse(
        'The `.sy-sr-only` rule applies `hidden`, which takes the element out of the '
        . 'accessibility tree as well and silences exactly the labels the class exists to speak.',
    );
});
