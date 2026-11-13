<?php

declare(strict_types=1);

use Symfony\Component\AssetMapper\AssetMapperInterface;

it('serves its built Stimulus controllers through AssetMapper as @sylament/notifications', function (): void {
    $asset = $this::getContainer()->get(AssetMapperInterface::class)->getAssetFromSourcePath(
        dirname(__DIR__, 2) . '/packages/notifications/assets/dist/controllers/notifications_controller.js',
    );

    expect($asset?->logicalPath)->toBe('@sylament/notifications/controllers/notifications_controller.js');
});
