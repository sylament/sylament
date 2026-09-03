<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Sylament\Panels\Navigation\NavigationGroup;
use Sylament\Panels\Navigation\NavigationItem;
use Sylament\Panels\Resources\RelationManagers\RelationGroup;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Article;
use Sylament\Panels\Tests\Fixtures\Doctrine\FixtureManagerRegistry;
use Sylament\Support\Evaluation\Invoker;
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

it('maps the unsaved-changes controller and the live-host helper it imports', function (): void {
    // The per-file build ships `utils/live_host.js` standalone beside the controller that
    // imports it, so its logical path must resolve too or the browser 404s on the import.
    $assetMapper = $this::getContainer()->get(AssetMapperInterface::class);
    $dist = dirname(__DIR__, 2) . '/assets/dist';

    expect($assetMapper->getAssetFromSourcePath($dist . '/controllers/unsaved_changes_controller.js')?->logicalPath)
        ->toBe('@sylament/panels/controllers/unsaved_changes_controller.js')
        ->and($assetMapper->getAssetFromSourcePath($dist . '/utils/live_host.js')?->logicalPath)
        ->toBe('@sylament/panels/utils/live_host.js');
});

it('builds navigation items through the container, non-shared', function (): void {
    $this::getContainer();

    expect(NavigationItem::make('A'))->not->toBe(NavigationItem::make('A'));
});

it('builds relation groups through the container so their closures reach it', function (): void {
    $invoker = $this::getContainer()->get(Invoker::class);

    expect(RelationGroup::make('Content')->getInvoker())->toBe($invoker)
        ->and(RelationGroup::make('Content'))->not->toBe(RelationGroup::make('Content'));
});

it('injects container services into evaluated closures', function (): void {
    // The group is the subject on purpose: it overrides no resolver hook, so the public
    // container arm is the only thing that can answer the `RequestStack` hint.
    $container = $this::getContainer();
    $stack = $container->get(RequestStack::class);
    $stack->push(Request::create('/admin/articles'));

    try {
        $group = NavigationGroup::make()->label(
            fn (RequestStack $requestStack): string => (string) $requestStack->getCurrentRequest()?->getPathInfo(),
        );

        expect($group->getLabel())->toBe('/admin/articles');
    } finally {
        $stack->pop();
    }
});

it('resolves the fixture manager registry for a page host with no table', function (): void {
    $container = $this::getContainer();

    expect($container->get(ManagerRegistry::class))->toBeInstanceOf(FixtureManagerRegistry::class)
        ->and($container->get(ManagerRegistry::class)->getManagerForClass(Article::class))
        ->toBe($container->get(EntityManagerInterface::class));
});
