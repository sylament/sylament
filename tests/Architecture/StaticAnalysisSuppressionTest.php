<?php

declare(strict_types=1);

namespace Sylament\Tests\Architecture;

use Sylament\Tests\Architecture\Source\ShippedSource;

use function dirname;

/**
 * Architectural convention test banning inline PHPStan suppressions.
 *
 * Pest's `arch()` API inspects classes and namespaces, not file contents, so it cannot
 * express a source-text rule — this guards the convention with an expectation instead,
 * but lives in the Architecture suite because it is a project-wide convention, not a
 * test of any single class (hence it does not mirror src/).
 *
 * The rule: shipped source carries no `@phpstan-ignore` of any form. PHPStan runs at
 * level max with no baseline, and an inline ignore hides a real defect at the one place
 * a reader is least likely to question it — the three that existed were all masking the
 * same unguarded nullable, fixed by making it throw. When an error genuinely cannot be
 * fixed, it belongs in a baseline file, where it is counted and reviewable, never inline.
 */

it('suppresses no PHPStan error inline', function (string $file): void {
    $content = (string) file_get_contents(dirname(__DIR__, 2) . '/packages/' . $file);

    expect(str_contains($content, '@phpstan-ignore'))->toBeFalse(
        "Remove the @phpstan-ignore from {$file}. PHPStan runs at level max with no baseline, so an "
        . 'inline ignore hides a real defect where a reader is least likely to question it — fix the '
        . 'cause instead (an unguarded nullable usually wants a throw). If an error genuinely cannot '
        . 'be fixed, add a baseline file so it is counted and reviewable rather than buried in source.',
    );
})->with(ShippedSource::files());
