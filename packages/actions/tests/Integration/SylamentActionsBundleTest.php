<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration;

use Sylament\Actions\BulkAction;
use Sylament\Actions\BulkActionGroup;
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

it('registers the bulk preset and its group as non-shared services, so their #[Required] setters run', function (): void {
    // An action class the bundle does not `->set()` falls back to `new $class()` in
    // `ComponentResolver::instantiate()`, and every `#[Required]` injection on it — the
    // manager registry, the event dispatcher — silently never runs.
    $container = $this::getContainer();

    expect($container->has(BulkAction::class))->toBeTrue()
        ->and($container->has(BulkActionGroup::class))->toBeTrue()
        ->and($container->get(BulkAction::class))->toBeInstanceOf(BulkAction::class)
        ->and($container->get(BulkAction::class))->not->toBe($container->get(BulkAction::class));
});
