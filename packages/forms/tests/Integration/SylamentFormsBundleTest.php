<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration;

use Symfony\Component\AssetMapper\AssetMapperInterface;

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
