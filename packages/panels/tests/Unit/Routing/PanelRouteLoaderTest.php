<?php

declare(strict_types=1);

use Sylament\Panels\Controllers\PageController;
use Sylament\Panels\CurrentPanel;
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
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Line;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Verse;
use Sylament\Panels\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Panels\Tests\Fixtures\NestedPanelProvider;
use Sylament\Panels\Tests\Fixtures\Pages\SettingsPage;
use Sylament\Panels\Tests\Fixtures\Resources\ChapterResource;
use Sylament\Panels\Tests\Fixtures\Resources\LineResource;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditVerse;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\RecordProbePage;
use Sylament\Panels\Tests\Fixtures\Resources\ProbeResource;
use Sylament\Panels\Tests\Fixtures\Resources\VerseResource;
use Sylament\Schemas\State\AssociationReader;
use Sylament\Schemas\State\PropertyAccessor;
use Sylament\Schemas\State\RecordTypeReader;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

final class TwinSlugPage extends Page
{
    protected static ?string $slug = 'twin';
}

final class OtherTwinSlugPage extends Page
{
    protected static ?string $slug = 'twin';
}

/**
 * Mounts a placeholder its `mount()` never takes and no property of its own
 * answers, which is exactly what ux paints onto the live root as a stray HTML
 * attribute.
 */
final class UnmountablePlaceholderPage extends Page
{
    public static function getRoutePath(): string
    {
        return '/{thing}';
    }
}

/**
 * Takes the same placeholder through a public property rather than a mount
 * parameter — the second admit of the guard.
 */
final class PropertyPlaceholderPage extends Page
{
    public ?string $thing = null;

    public static function getRoutePath(): string
    {
        return '/{thing}';
    }
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
 * Derives the record parameter as its parent's, so the two collide on the very
 * pages it registers.
 */
final class RecordParameterResource extends Resource
{
    protected static ?string $recordClass = Verse::class;

    protected static ?string $parentResource = ChapterResource::class;

    public function getParentRouteParameterName(): string
    {
        return 'record';
    }

    public function getPages(): array
    {
        return ['edit' => new PageRegistration(RecordProbePage::class, '/{record}/edit')];
    }
}

/**
 * Derives the parameter its grandparent already travels under, two levels down.
 */
final class DuplicateParameterResource extends Resource
{
    protected static ?string $recordClass = Line::class;

    protected static ?string $parentResource = VerseResource::class;

    public function getParentRouteParameterName(): string
    {
        return 'chapter';
    }

    public function getPages(): array
    {
        return ['edit' => new PageRegistration(RecordProbePage::class, '/{record}/edit')];
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

/**
 * A loader whose resources carry the services a nested one reads its parent
 * chain through — `loaderFor()` leaves them bare, which is all a flat resource
 * needs.
 *
 * @param array<class-string<Resource>, Resource> $resources
 */
function wiredLoaderFor(PanelProvider $provider, array $resources): PanelRouteLoader
{
    $registry = new ResourceRegistry(new ServiceLocator(array_map(
        static fn (Resource $resource): Closure => static fn (): Resource => $resource,
        $resources,
    )));

    $panels = new PanelRegistry([$provider]);
    $entityManager = EntityManagerFactory::create();
    $propertyAccessor = new PropertyAccessor(PropertyAccess::createPropertyAccessor(), new RecordTypeReader());

    foreach ($resources as $resource) {
        $resource->setResourceServices(
            $entityManager,
            new UrlGenerator(new RouteCollection(), new RequestContext()),
            new CurrentPanel(new RequestStack(), $panels),
            $panels,
            $propertyAccessor,
            new AssociationReader($entityManager, $propertyAccessor),
            $registry,
        );
    }

    return new PanelRouteLoader($panels, $registry);
}

function nestedResourceLoader(): PanelRouteLoader
{
    return wiredLoaderFor(new NestedPanelProvider(), [
        ChapterResource::class => new ChapterResource(),
        VerseResource::class => new VerseResource(),
        LineResource::class => new LineResource(),
    ]);
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

it('mounts a nested resource under its parent record', function (): void {
    $route = nestedResourceLoader()()->get('sylament.nest.resources.chapters.verses.edit');

    expect($route?->getPath())->toBe('/nest/chapters/{chapter}/verses/{record}/edit')
        ->and($route->compile()->getVariables())->toBe(['chapter', 'record'])
        ->and($route->getDefault('_sylament_parent_params'))->toBe(['chapter'])
        ->and($route->getDefault('_sylament_page'))->toBe(EditVerse::class);
});

it('walks a two-deep chain root-first', function (): void {
    $route = nestedResourceLoader()()->get('sylament.nest.resources.chapters.verses.lines.edit');

    expect($route?->getPath())->toBe('/nest/chapters/{chapter}/verses/{verse}/lines/{record}/edit')
        ->and($route->compile()->getVariables())->toBe(['chapter', 'verse', 'record'])
        ->and($route->getDefault('_sylament_parent_params'))->toBe(['chapter', 'verse']);
});

it('collapses a nested index registration onto the nested resource root', function (): void {
    expect(nestedResourceLoader()()->get('sylament.nest.resources.chapters.verses.lines.index')?->getPath())
        ->toBe('/nest/chapters/{chapter}/verses/{verse}/lines');
});

it('leaves every unnested route without parent parameters', function (): void {
    $routes = nestedResourceLoader()();

    expect($routes->get('sylament.nest.resources.chapters.edit')?->getDefault('_sylament_parent_params'))->toBeNull()
        ->and($routes->get('sylament.nest.resources.chapters.index')?->getDefault('_sylament_parent_params'))->toBeNull()
        ->and($routes->get('sylament.nest.pages.dashboard')?->getDefault('_sylament_parent_params'))->toBeNull();
});

it('refuses a nested resource whose parent the panel leaves out', function (): void {
    $provider = new class extends PanelProvider {
        public function panel(Panel $panel): Panel
        {
            return $panel->id('nest')->path('nest')->resources([VerseResource::class]);
        }
    };

    wiredLoaderFor($provider, [
        ChapterResource::class => new ChapterResource(),
        VerseResource::class => new VerseResource(),
    ])();
})->throws(LogicException::class, 'which panel "nest" does not register');

it('refuses a parent parameter that collides with the record parameter', function (): void {
    $provider = new class extends PanelProvider {
        public function panel(Panel $panel): Panel
        {
            return $panel->id('nest')->path('nest')->resources([ChapterResource::class, RecordParameterResource::class]);
        }
    };

    wiredLoaderFor($provider, [
        ChapterResource::class => new ChapterResource(),
        RecordParameterResource::class => new RecordParameterResource(),
    ])();
})->throws(LogicException::class, 'collides with the record parameter');

it('refuses a parent parameter an ancestor already travels under', function (): void {
    $provider = new class extends PanelProvider {
        public function panel(Panel $panel): Panel
        {
            return $panel->id('nest')->path('nest')->resources([ChapterResource::class, VerseResource::class, DuplicateParameterResource::class]);
        }
    };

    wiredLoaderFor($provider, [
        ChapterResource::class => new ChapterResource(),
        VerseResource::class => new VerseResource(),
        DuplicateParameterResource::class => new DuplicateParameterResource(),
    ])();
})->throws(LogicException::class, 'collides with an ancestor parameter');

it('rejects a resources() entry that is not a Resource subclass', function (): void {
    $provider = new class extends PanelProvider {
        public function panel(Panel $panel): Panel
        {
            return $panel->id('probe')->path('probe')->resources([stdClass::class]);
        }
    };

    loaderFor($provider)();
})->throws(LogicException::class, 'stdClass');

it('mounts a resource page whose mount takes the record placeholder', function (): void {
    expect(probeResourceLoader()()->get('sylament.probe.resources.articles.probe')?->getPath())
        ->toBe('/probe/articles/{record}/probe');
});

it('refuses a page mounted at a placeholder it cannot take', function (): void {
    $provider = new class extends PanelProvider {
        public function panel(Panel $panel): Panel
        {
            return $panel->id('admin')->path('admin')->pages([UnmountablePlaceholderPage::class]);
        }
    };

    loaderFor($provider)();
})->throws(LogicException::class, 'the route parameter "{thing}"');

it('admits a placeholder a public property of the page answers', function (): void {
    $provider = new class extends PanelProvider {
        public function panel(Panel $panel): Panel
        {
            return $panel->id('admin')->path('admin')->pages([PropertyPlaceholderPage::class]);
        }
    };

    expect(loaderFor($provider)()->get('sylament.admin.pages.property-placeholder-page')?->getPath())
        ->toBe('/admin/{thing}');
});

it('never asks a nested page to mount the parent keys the loader folds itself', function (): void {
    // `EditVerse::mount()` takes `$record` and `$parentRecords` — never a
    // `$chapter` — so the parent parameter has to be admitted by the loader's
    // own list rather than by the page.
    $route = nestedResourceLoader()()->get('sylament.nest.resources.chapters.verses.lines.edit');

    expect($route?->getPath())->toBe('/nest/chapters/{chapter}/verses/{verse}/lines/{record}/edit')
        ->and($route->getDefault('_sylament_parent_params'))->toBe(['chapter', 'verse']);
});
