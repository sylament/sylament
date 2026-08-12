<?php

declare(strict_types=1);

use Sylament\Panels\Navigation\NavigationItem;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

it('maps the built assets directory into AssetMapper under @sylament/panels', function (): void {
    // StimulusBundle's `ControllersMapGenerator` resolves a `symfony.controllers` entry by
    // calling `getAssetFromSourcePath()` on the package directory plus the declared `main`,
    // and throws when the path sits outside every registered one — so the prepend is what
    // stands between the built controller and a consuming app.
    $asset = $this::getContainer()->get(AssetMapperInterface::class)->getAssetFromSourcePath(
        dirname(__DIR__, 2) . '/assets/dist/controllers/sidebar_controller.js',
    );

    expect($asset)->not->toBeNull()
        ->and($asset->logicalPath)->toBe('@sylament/panels/controllers/sidebar_controller.js');
});

it('builds navigation items through the container, non-shared', function (): void {
    $this::getContainer();

    expect(NavigationItem::make('A'))->not->toBe(NavigationItem::make('A'));
});

it('injects container services into evaluated closures', function (): void {
    $container = $this::getContainer();
    $container->get(RequestStack::class)->push(Request::create('/admin/articles'));

    $item = NavigationItem::make('Articles')->isActiveWhen(
        fn (RequestStack $requestStack): bool => str_starts_with((string) $requestStack->getCurrentRequest()?->getPathInfo(), '/admin/articles'),
    );

    expect($item->isActive())->toBeTrue();
});
