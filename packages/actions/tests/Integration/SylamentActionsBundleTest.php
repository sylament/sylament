<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration;

use Symfony\Component\AssetMapper\AssetMapperInterface;

it('maps the built assets directory into AssetMapper under @sylament/actions', function (): void {
    // StimulusBundle's `ControllersMapGenerator` resolves a `symfony.controllers` entry by
    // calling `getAssetFromSourcePath()` on the package directory plus the declared `main`,
    // and throws when the path sits outside every registered one — so the prepend is what
    // stands between the built controller and a consuming app.
    $asset = $this::getContainer()->get(AssetMapperInterface::class)->getAssetFromSourcePath(
        dirname(__DIR__, 2) . '/assets/dist/controllers/dispatch_controller.js',
    );

    expect($asset)->not->toBeNull()
        ->and($asset->logicalPath)->toBe('@sylament/actions/controllers/dispatch_controller.js');
});
