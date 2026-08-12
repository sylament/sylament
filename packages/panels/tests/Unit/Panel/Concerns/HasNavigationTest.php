<?php

declare(strict_types=1);

use Sylament\Panels\Navigation\NavigationGroup;
use Sylament\Panels\Navigation\NavigationItem;
use Sylament\Panels\Panel;
use Sylament\Panels\Resources\Pages\PageRegistration;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Resources\ResourceRegistry;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Article;
use Sylament\Panels\Tests\Fixtures\Pages\SettingsPage;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * The three resources below are local to this file on purpose. The shared
 * `ArticleResource` fixture declares no pages yet, so today it would be gated out
 * of navigation entirely, and the `index` row a later task gives it would then
 * flip the pageless arm the other way.
 */
final class NavProbeResource extends Resource
{
    protected static ?string $recordClass = Article::class;

    protected static ?string $navigationGroup = 'Content';

    public function getPages(): array
    {
        return ['index' => new PageRegistration(SettingsPage::class, '/')];
    }
}

final class OptedOutNavigationResource extends Resource
{
    protected static ?string $recordClass = Article::class;

    protected static bool $shouldRegisterNavigation = false;

    public function getPages(): array
    {
        return ['index' => new PageRegistration(SettingsPage::class, '/')];
    }
}

final class PagelessNavigationResource extends Resource
{
    protected static ?string $recordClass = Article::class;
}

function navigationRegistryWith(Resource ...$resources): ResourceRegistry
{
    $factories = [];

    foreach ($resources as $resource) {
        $factories[$resource::class] = static fn (): Resource => $resource;
    }

    return new ResourceRegistry(new ServiceLocator($factories));
}

it('derives grouped, sorted navigation from pages and extra items', function (): void {
    $panel = Panel::make()
        ->id('admin')
        ->navigationGroups(['Content'])
        ->navigationItems([
            NavigationItem::make('External')->url('#')->sort(5),
        ])
        ->pages([SettingsPage::class]);

    $navigation = $panel->getNavigation();

    expect($navigation)->toHaveCount(2)
        ->and($navigation[0]->getLabel())->toBeNull()
        ->and($navigation[0]->getItems()[0]->getLabel())->toBe('External')
        ->and($navigation[1]->getLabel())->toBe('Content')
        ->and($navigation[1]->getItems()[0]->getLabel())->toBe('Settings');
});

it('keeps registered group objects intact by cloning per call', function (): void {
    $registered = NavigationGroup::make('Content')->collapsed();
    $panel = Panel::make()->id('admin')->navigationGroups([$registered])->pages([SettingsPage::class]);

    $first = $panel->getNavigation();
    $second = $panel->getNavigation();

    expect($registered->getItems())->toBe([])
        ->and($first[0])->not->toBe($second[0])
        ->and($first[0]->getItems())->toHaveCount(1);
});

it('stamps the panel collapsible default onto every group', function (): void {
    $panel = Panel::make()
        ->id('admin')
        ->collapsibleNavigationGroups(false)
        ->pages([SettingsPage::class]);

    expect($panel->getNavigation()[0]->isCollapsible())->toBeFalse();
});

it('drops hidden items and groups that end up empty', function (): void {
    $panel = Panel::make()->id('admin')->navigationItems([
        NavigationItem::make('Ghost')->url('#')->hidden(),
    ]);

    expect($panel->getNavigation())->toBe([]);
});

it('merges resource navigation items between page items and registered items', function (): void {
    $panel = Panel::make()
        ->id('admin')
        ->navigationItems([
            NavigationItem::make('External')->url('#')->group('Content'),
        ])
        ->pages([SettingsPage::class])
        ->resources([NavProbeResource::class]);

    $panel->setResourceRegistry(navigationRegistryWith(new NavProbeResource()));

    $navigation = $panel->getNavigation();
    $items = $navigation[0]->getItems();

    expect($navigation)->toHaveCount(1)
        ->and(array_map(fn (NavigationItem $item): string => $item->getLabel(), $items))
        ->toBe(['Settings', 'Articles', 'External'])
        ->and($items[1]->getGroup())->toBe('Content')
        ->and($items[1]->getSort())->toBe(-1);
});

it('skips a resource that opts out of navigation', function (): void {
    $panel = Panel::make()->id('admin')->resources([OptedOutNavigationResource::class]);
    $panel->setResourceRegistry(navigationRegistryWith(new OptedOutNavigationResource()));

    expect($panel->getNavigation())->toBe([]);
});

it('contributes nothing for a resource without an index page', function (): void {
    $panel = Panel::make()->id('admin')->resources([PagelessNavigationResource::class]);
    $panel->setResourceRegistry(navigationRegistryWith(new PagelessNavigationResource()));

    expect($panel->getNavigation())->toBe([]);
});

it('throws when resources are registered but the panel was built outside the container', function (): void {
    $panel = new Panel();
    $panel->resources([NavProbeResource::class]);

    $panel->getNavigation();
})->throws(LogicException::class, 'container-built panel');
