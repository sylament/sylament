<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration;

use Sylament\Forms\Uploads\TemporaryUploads;
use Symfony\Component\AssetMapper\AssetMapperInterface;

it('wires the temporary upload store from the sylament_forms config tree', function (): void {
    // The bundle's first `configure()` tree. The disk is deliberately not asserted: it resolves
    // through `sylament.default_filesystem_disk`, which this kernel does not set today and a
    // later lane does — the directory and the sweep flag are what this wiring owns.
    $store = $this::getContainer()->get(TemporaryUploads::class);

    expect($store)->toBeInstanceOf(TemporaryUploads::class)
        ->and($store->getDirectory())->toBe('sylament-tmp');
});

it('maps the built assets directory into AssetMapper under @sylament/forms', function (): void {
    // StimulusBundle's `ControllersMapGenerator` resolves a `symfony.controllers` entry by
    // calling `getAssetFromSourcePath()` on the package directory plus the declared `main`,
    // and throws when the path sits outside every registered one — so the prepend is what
    // stands between the built controller and a consuming app.
    $asset = $this::getContainer()->get(AssetMapperInterface::class)->getAssetFromSourcePath(
        dirname(__DIR__, 2) . '/assets/dist/controllers/checkbox_list_controller.js',
    );

    expect($asset)->not->toBeNull()
        ->and($asset->logicalPath)->toBe('@sylament/forms/controllers/checkbox_list_controller.js');
});

it('resolves the built select controller through AssetMapper', function (): void {
    // The bundle is a `BUNDLED_ENTRIES` build (Floating UI and SortableJS inlined), so the shipped
    // file is the committed dist artifact — `findLogicalPath()` returns null for a file that is
    // not there.
    $asset = $this::getContainer()->get(AssetMapperInterface::class)->getAssetFromSourcePath(
        dirname(__DIR__, 2) . '/assets/dist/controllers/select_controller.js',
    );

    expect($asset)->not->toBeNull()
        ->and($asset->logicalPath)->toBe('@sylament/forms/controllers/select_controller.js');
});
