<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Architecture;

use JsonException;

use function dirname;

/**
 * Guards the package's declared dependencies, which nothing else in this suite can
 * see: the monorepo resolves every package out of one root vendor, so the `Select`
 * the Unit tests build autoloads whether or not this package's manifest names
 * `sylament/forms` — the line could go away without a single failure here, and break
 * only the split repo.
 *
 * `sylament/forms` is a hard require because `AttachAction`/`AssociateAction` build a
 * `Select` unconditionally — the guarded-soft-dependency shape the table context uses
 * does not transfer to a class a return type resolves. `sylament/tables` and
 * `sylament/notifications` stay suggestions, each behind a real guard.
 *
 * It lives in the Architecture bucket rather than mirroring `src/` because it reads a
 * file rather than exercising a class.
 */

/**
 * @return array<string, mixed>
 *
 * @throws JsonException
 */
function actionsManifest(): array
{
    $contents = file_get_contents(dirname(__DIR__, 2) . '/composer.json');

    expect($contents)->toBeString();

    /** @var array<string, mixed> $manifest */
    $manifest = json_decode((string) $contents, associative: true, flags: JSON_THROW_ON_ERROR);

    return $manifest;
}

it('requires the three sylament packages its src resolves unconditionally', function (): void {
    expect(actionsManifest()['require'])
        ->toHaveKey('sylament/forms', 'self.version')
        ->toHaveKey('sylament/schemas', 'self.version')
        ->toHaveKey('sylament/support', 'self.version');
});

it('keeps the two guarded integrations as suggestions', function (): void {
    $manifest = actionsManifest();

    expect($manifest['suggest'])
        ->toHaveKeys(['sylament/notifications', 'sylament/tables'])
        ->and($manifest['require'])
        ->not->toHaveKey('sylament/notifications')
        ->not->toHaveKey('sylament/tables');
});
