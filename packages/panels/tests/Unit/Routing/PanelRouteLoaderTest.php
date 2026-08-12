<?php

declare(strict_types=1);

use Sylament\Panels\Controllers\PageController;
use Sylament\Panels\Pages\Dashboard;
use Sylament\Panels\Pages\Page;
use Sylament\Panels\Panel;
use Sylament\Panels\PanelProvider;
use Sylament\Panels\PanelRegistry;
use Sylament\Panels\Resources\Pages\PageRegistration;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Resources\ResourceRegistry;
use Sylament\Panels\Routing\PanelRouteLoader;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Article;
use Sylament\Panels\Tests\Fixtures\Pages\SettingsPage;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\RecordProbePage;
use Sylament\Panels\Tests\Fixtures\Resources\ProbeResource;
use Symfony\Component\DependencyInjection\ServiceLocator;

final class TwinSlugPage extends Page
{
    protected static ?string $slug = 'twin';
}

final class OtherTwinSlugPage extends Page
{
    protected static ?string $slug = 'twin';
}

/**
 * Carries `ProbeResource`'s slug, so the two mount the same route names.
 */
final class TwinSlugResource extends Resource
{
    protected static ?string $recordClass = Article::class;

    protected static ?string $slug = 'articles';

    public function getPages(): array
    {
        return ['index' => new PageRegistration(RecordProbePage::class, '/')];
    }
}

/**
 * Registers its pages without the leading slash `PageRegistration::$path` treats as optional.
 */
final class SlashlessPathResource extends Resource
{
    protected static ?string $recordClass = Article::class;

    protected static ?string $slug = 'notes';

    public function getPages(): array
    {
        return [
            'index' => new PageRegistration(RecordProbePage::class, ''),
            'edit' => new PageRegistration(RecordProbePage::class, '{record}/edit'),
        ];
    }
}

/**
 * @param array<class-string<Resource>, Resource> $resources
 */
function loaderFor(PanelProvider $provider, array $resources = []): PanelRouteLoader
{
    $factories = [];

    foreach ($resources as $class => $resource) {
        $factories[$class] = static fn (): Resource => $resource;
    }

    return new PanelRouteLoader(new PanelRegistry([$provider]), new ResourceRegistry(new ServiceLocator($factories)));
}

function probeResourceLoader(): PanelRouteLoader
{
    $provider = new class extends PanelProvider {
        public function panel(Panel $panel): Panel
        {
            return $panel->id('probe')->path('probe')->resources([ProbeResource::class]);
        }
    };

    return loaderFor($provider, [ProbeResource::class => new ProbeResource()]);
}

it('mounts one GET route per page under the panel path', function (): void {
    $provider = new class extends PanelProvider {
        public function panel(Panel $panel): Panel
        {
            return $panel->id('admin')->path('admin')->pages([Dashboard::class, SettingsPage::class]);
        }
    };

    $routes = loaderFor($provider)();

    $dashboard = $routes->get('sylament.admin.pages.dashboard');
    $settings = $routes->get('sylament.admin.pages.settings-page');

    expect($dashboard?->getPath())->toBe('/admin')
        ->and($dashboard->getMethods())->toBe(['GET'])
        ->and($dashboard->getDefault('_controller'))->toBe(PageController::class)
        ->and($dashboard->getDefault('_sylament_panel'))->toBe('admin')
        ->and($dashboard->getDefault('_sylament_page'))->toBe(Dashboard::class)
        ->and($settings?->getPath())->toBe('/admin/settings-page');
});

it('mounts a root-mounted panel without a double slash', function (): void {
    $provider = new class extends PanelProvider {
        public function panel(Panel $panel): Panel
        {
            return $panel->id('app')->pages([Dashboard::class, SettingsPage::class]);
        }
    };

    $routes = loaderFor($provider)();

    expect($routes->get('sylament.app.pages.dashboard')?->getPath())->toBe('/')
        ->and($routes->get('sylament.app.pages.settings-page')?->getPath())->toBe('/settings-page');
});

it('tracks provider and page class files as resources', function (): void {
    $provider = new class extends PanelProvider {
        public function panel(Panel $panel): Panel
        {
            return $panel->id('admin')->path('admin')->pages([Dashboard::class]);
        }
    };

    $resources = array_map(strval(...), loaderFor($provider)()->getResources());

    expect(implode(' ', $resources))->toContain('Dashboard.php');
});

it('rejects a pages() entry that is not a Page subclass', function (): void {
    $provider = new class extends PanelProvider {
        public function panel(Panel $panel): Panel
        {
            return $panel->id('admin')->path('admin')->pages([stdClass::class]);
        }
    };

    loaderFor($provider)();
})->throws(LogicException::class, 'stdClass');

it('mounts one route per resource page under the resource slug', function (): void {
    $routes = probeResourceLoader()();

    $route = $routes->get('sylament.probe.resources.articles.probe');

    expect($route)->not->toBeNull()
        ->and($route->getPath())->toBe('/probe/articles/{record}/probe')
        ->and($route->getDefault('_controller'))->toBe(PageController::class)
        ->and($route->getDefault('_sylament_panel'))->toBe('probe')
        ->and($route->getDefault('_sylament_page'))->toBe(RecordProbePage::class)
        ->and($route->getMethods())->toBe(['GET']);
});

it('collapses the index registration onto the resource root', function (): void {
    expect(probeResourceLoader()()->get('sylament.probe.resources.articles.index')?->getPath())->toBe('/probe/articles');
});

it('mounts a registration path that omits its leading slash', function (): void {
    $provider = new class extends PanelProvider {
        public function panel(Panel $panel): Panel
        {
            return $panel->id('probe')->path('probe')->resources([SlashlessPathResource::class]);
        }
    };

    $routes = loaderFor($provider, [SlashlessPathResource::class => new SlashlessPathResource()])();

    expect($routes->get('sylament.probe.resources.notes.edit')?->getPath())->toBe('/probe/notes/{record}/edit')
        ->and($routes->get('sylament.probe.resources.notes.index')?->getPath())->toBe('/probe/notes');
});

it('tracks resource and resource page class files as resources', function (): void {
    $files = implode(' ', array_map(strval(...), probeResourceLoader()()->getResources()));

    expect($files)->toContain('ProbeResource.php')
        ->and($files)->toContain('RecordProbePage.php');
});

it('throws when two mounts derive the same route name', function (): void {
    $provider = new class extends PanelProvider {
        public function panel(Panel $panel): Panel
        {
            return $panel->id('admin')->path('admin')->pages([TwinSlugPage::class, OtherTwinSlugPage::class]);
        }
    };

    loaderFor($provider)();
})->throws(LogicException::class, 'already mounted');

it('throws when two resources derive the same route names', function (): void {
    $provider = new class extends PanelProvider {
        public function panel(Panel $panel): Panel
        {
            return $panel->id('probe')->path('probe')->resources([ProbeResource::class, TwinSlugResource::class]);
        }
    };

    loaderFor($provider, [
        ProbeResource::class => new ProbeResource(),
        TwinSlugResource::class => new TwinSlugResource(),
    ])();
})->throws(LogicException::class, 'already mounted');

it('rejects a resources() entry that is not a Resource subclass', function (): void {
    $provider = new class extends PanelProvider {
        public function panel(Panel $panel): Panel
        {
            return $panel->id('probe')->path('probe')->resources([stdClass::class]);
        }
    };

    loaderFor($provider)();
})->throws(LogicException::class, 'stdClass');
