<?php

declare(strict_types=1);

namespace Sylament\Tests\Architecture;

use Sylament\Tests\Architecture\Manifests\PackageManifest;

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

it('requires the three sylament packages its src resolves unconditionally', function (): void {
    expect(PackageManifest::of('actions')['require'])
        ->toHaveKey('sylament/forms', 'self.version')
        ->toHaveKey('sylament/schemas', 'self.version')
        ->toHaveKey('sylament/support', 'self.version');
});

it("requires the two sylament packages schemas' src resolves", function (): void {
    expect(PackageManifest::of('schemas')['require'])
        ->toHaveKey('sylament/actions', 'self.version')
        ->toHaveKey('sylament/support', 'self.version');
});

it('keeps the two guarded integrations as suggestions', function (): void {
    $manifest = PackageManifest::of('actions');

    expect($manifest['suggest'])
        ->toHaveKeys(['sylament/notifications', 'sylament/tables'])
        ->and($manifest['require'])
        ->not->toHaveKey('sylament/notifications')
        ->not->toHaveKey('sylament/tables');
});

it("requires the extensions and packages tables' src calls unconditionally", function (): void {
    expect(PackageManifest::of('tables')['require'])
        ->toHaveKey('ext-intl')
        ->toHaveKey('ext-mbstring')
        ->toHaveKey('sylament/forms', 'self.version')
        ->toHaveKey('symfony/html-sanitizer')
        ->toHaveKey('symfony/validator');
});

it("keeps tables' markdown converter a suggestion, behind the guard markdown() throws from", function (): void {
    $manifest = PackageManifest::of('tables');

    expect($manifest['suggest'])
        ->toHaveKey('league/commonmark')
        ->and($manifest['require'])
        ->not->toHaveKey('league/commonmark');
});

/**
 * The toolkit stylesheet reaches a page only through this entry: StimulusBundle loads an enabled, eager controller up
 * front with its autoimports, so a lazy or disabled `core`, or a moved stylesheet path, drops every package's styles
 * without an error.
 */
it("loads support's core controller eagerly, autoimporting the toolkit stylesheet", function (): void {
    expect(PackageManifest::assets('support')['symfony']['controllers']['core'] ?? null)->toMatchArray([
        'fetch' => 'eager',
        'enabled' => true,
        'autoimport' => ['@sylament/support/styles/index.css' => true],
    ]);
});

/**
 * The unsaved-changes guard runs only where StimulusBundle loads this controller up front: a lazy or disabled block
 * leaves every page rendering and saving with no prompt, and no error says so.
 */
it("loads panels' unsaved-changes controller eagerly", function (): void {
    expect(PackageManifest::assets('panels')['symfony']['controllers']['unsaved-changes'] ?? null)->toMatchArray([
        'fetch' => 'eager',
        'enabled' => true,
    ]);
});
