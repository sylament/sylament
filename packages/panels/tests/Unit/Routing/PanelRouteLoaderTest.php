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
use Sylament\Panels\Tests\Fixtures\Resources\Pages\SeparatorProbePage;
use Sylament\Panels\Tests\Fixtures\Resources\ProbeResource;
use Sylament\Panels\Tests\Fixtures\Resources\SeparatorProbeResource;
use Sylament\Panels\Tests\Fixtures\Resources\VerseResource;
use Sylament\Schemas\State\AssociationReader;
use Sylament\Schemas\State\PropertyAccessor;
use Sylament\Schemas\State\RecordTypeReader;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Matcher\UrlMatcher;
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
 * Registers the bare record path `SeparatorProbeResource` leaves out, because a
 * bare one registered beside its siblings would match `/5-preview` whole.
 */
final class CompositeRouteBareRecordResource extends Resource
{
    protected static ?string $recordClass = Article::class;

    protected static ?string $slug = 'bares';

    public function getPages(): array
    {
        return ['view' => new PageRegistration(SeparatorProbePage::class, '/{record}')];
    }
}

/**
 * Takes the placeholders of the three path shapes whose record placeholder
 * shares its own path segment with a second one.
 */
final class CompositeRoutePlaceholderSegmentPage extends Page
{
    public ?string $record = null;

    public ?string $x = null;

    public ?string $locale = null;

    public ?string $format = null;
}

final class CompositeRoutePlaceholderSegmentResource extends Resource
{
    protected static ?string $recordClass = Article::class;

    protected static ?string $slug = 'segments';

    public function getPages(): array
    {
        return [
            'adjacent' => new PageRegistration(CompositeRoutePlaceholderSegmentPage::class, '/{record}{x}-y'),
            'locale' => new PageRegistration(CompositeRoutePlaceholderSegmentPage::class, '/{record}-{locale}'),
            'format' => new PageRegistration(CompositeRoutePlaceholderSegmentPage::class, '/{record}.{format}'),
        ];
    }
}

/**
 * Two record paths whose static text overlaps, the shorter registered first,
 * so a URL spelling the longer one meets the shorter one's route on the way.
 */
final class CompositeRouteOverlapResource extends Resource
{
    protected static ?string $recordClass = Article::class;

    protected static ?string $slug = 'overlaps';

    public function getPages(): array
    {
        return [
            'short' => new PageRegistration(SeparatorProbePage::class, '/{record}-x'),
            'long' => new PageRegistration(SeparatorProbePage::class, '/{record}-y-x'),
        ];
    }
}

/**
 * A nested resource carrying a separator path of its own, where the parent key
 * travels beside the record on one path.
 */
final class CompositeRouteNestedSeparatorResource extends Resource
{
    protected static ?string $recordClass = Verse::class;

    protected static ?string $parentResource = ChapterResource::class;

    protected static ?string $slug = 'dashed-verses';

    public function getPages(): array
    {
        return ['dash' => new PageRegistration(SeparatorProbePage::class, '/{record}-preview')];
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

function compositeRouteSeparatorRoutes(): RouteCollection
{
    $provider = new class extends PanelProvider {
        public function panel(Panel $panel): Panel
        {
            return $panel->id('sep')->path('sep')->resources([
                SeparatorProbeResource::class,
                CompositeRouteBareRecordResource::class,
                CompositeRoutePlaceholderSegmentResource::class,
                CompositeRouteOverlapResource::class,
            ]);
        }
    };

    return loaderFor($provider, [
        SeparatorProbeResource::class => new SeparatorProbeResource(),
        CompositeRouteBareRecordResource::class => new CompositeRouteBareRecordResource(),
        CompositeRoutePlaceholderSegmentResource::class => new CompositeRoutePlaceholderSegmentResource(),
        CompositeRouteOverlapResource::class => new CompositeRouteOverlapResource(),
    ])();
}

function compositeRouteNestedRoutes(): RouteCollection
{
    $provider = new class extends PanelProvider {
        public function panel(Panel $panel): Panel
        {
            return $panel->id('nest')->path('nest')->resources([ChapterResource::class, CompositeRouteNestedSeparatorResource::class]);
        }
    };

    return wiredLoaderFor($provider, [
        ChapterResource::class => new ChapterResource(),
        CompositeRouteNestedSeparatorResource::class => new CompositeRouteNestedSeparatorResource(),
    ])();
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

it('narrows a record placeholder a separator follows, and no other one', function (): void {
    $routes = compositeRouteSeparatorRoutes();

    expect($routes->get('sylament.sep.resources.separator-probes.dash')?->getRequirements())->toBe(['record' => '[^/]+'])
        ->and($routes->get('sylament.sep.resources.separator-probes.tilde')?->getRequirements())->toBe(['record' => '[^/]+'])
        ->and($routes->get('sylament.sep.resources.separator-probes.underscore')?->getRequirements())->toBe(['record' => '[^/]+'])
        ->and($routes->get('sylament.sep.resources.separator-probes.equals')?->getRequirements())->toBe(['record' => '[^/]+'])
        ->and($routes->get('sylament.sep.resources.separator-probes.dot')?->getRequirements())->toBe(['record' => '[^/]+'])
        ->and($routes->get('sylament.sep.resources.separator-probes.probe')?->getRequirements())->toBe([])
        ->and($routes->get('sylament.sep.resources.separator-probes.index')?->getRequirements())->toBe([])
        ->and($routes->get('sylament.sep.resources.bares.view')?->getRequirements())->toBe([]);
});

it('leaves a path no separator follows compiling to the regex it compiled to before', function (): void {
    $provider = new class extends PanelProvider {
        public function panel(Panel $panel): Panel
        {
            return $panel->id('probe')->path('probe')->resources([SlashlessPathResource::class]);
        }
    };

    $routes = compositeRouteSeparatorRoutes();
    $slashless = loaderFor($provider, [SlashlessPathResource::class => new SlashlessPathResource()])();

    expect($slashless->get('sylament.probe.resources.notes.edit')?->compile()->getRegex())
        ->toBe('{^/probe/notes/(?P<record>[^/]++)/edit$}sD')
        ->and($routes->get('sylament.sep.resources.bares.view')?->compile()->getRegex())
        ->toBe('{^/sep/bares/(?P<record>[^/]++)$}sD')
        ->and($routes->get('sylament.sep.resources.separator-probes.probe')?->compile()->getRegex())
        ->toBe('{^/sep/separator\-probes/(?P<record>[^/]++)/probe$}sD');
});

it('generates and matches back a key carrying the separator its path ends on', function (): void {
    $routes = compositeRouteSeparatorRoutes();
    $context = new RequestContext();
    $generator = new UrlGenerator($routes, $context);
    $matcher = new UrlMatcher($routes, $context);

    // A possessive `[^/]++` generates every one of these and then matches none of
    // them, so the requirement string is pinned beside the round trip it buys.
    expect($routes->get('sylament.sep.resources.separator-probes.dash')?->getRequirement('record'))->toBe('[^/]+')
        ->and($routes->get('sylament.sep.resources.separator-probes.dash')?->compile()->getRegex())
        ->toBe('{^/sep/separator\-probes/(?P<record>[^/]+)\-preview$}sD');

    foreach (['main-hall~autumn=2F2026', '1~en-GB', '0f1e2d3c-4b5a-6978-8796-a5b4c3d2e1f0'] as $key) {
        $url = $generator->generate('sylament.sep.resources.separator-probes.dash', ['record' => $key]);

        expect($url)->toBe('/sep/separator-probes/' . $key . '-preview')
            ->and($matcher->match($url)['record'])->toBe($key);
    }
});

it('leaves a record placeholder sharing its segment with a second one to the compiler', function (): void {
    $routes = compositeRouteSeparatorRoutes();

    // The three regexes below are the compiler's own, and a requirement of any
    // shape would move where such a segment splits between its two placeholders.
    expect($routes->get('sylament.sep.resources.segments.adjacent')?->getRequirements())->toBe([])
        ->and($routes->get('sylament.sep.resources.segments.adjacent')?->compile()->getRegex())
        ->toBe('{^/sep/segments/(?P<record>[^/\-]+)(?P<x>[^/\-]++)\-y$}sD')
        ->and($routes->get('sylament.sep.resources.segments.locale')?->getRequirements())->toBe([])
        ->and($routes->get('sylament.sep.resources.segments.locale')?->compile()->getRegex())
        ->toBe('{^/sep/segments/(?P<record>[^/\-]++)\-(?P<locale>[^/]++)$}sD')
        ->and($routes->get('sylament.sep.resources.segments.format')?->getRequirements())->toBe([])
        ->and($routes->get('sylament.sep.resources.segments.format')?->compile()->getRegex())
        ->toBe('{^/sep/segments/(?P<record>[^/\.]++)\.(?P<format>[^/]++)$}sD');
});

// A greedy record pattern reads up to the *last* spelling of the static text
// behind it, so a URL that spells out the longer of two overlapping paths is
// claimed by whichever of them is registered first.
it('characterizes: an earlier overlapping path claims a url the later one spells out', function (): void {
    $matcher = new UrlMatcher(compositeRouteSeparatorRoutes(), new RequestContext());
    $match = $matcher->match('/sep/overlaps/5-y-x');

    expect($match['_route'])->toBe('sylament.sep.resources.overlaps.short')
        ->and($match['record'])->toBe('5-y');
});

it('narrows the record placeholder of a nested path and leaves the parent key alone', function (): void {
    $route = compositeRouteNestedRoutes()->get('sylament.nest.resources.chapters.verses.dash');

    expect($route?->getPath())->toBe('/nest/chapters/{chapter}/dashed-verses/{record}-preview')
        ->and($route->getRequirements())->toBe(['record' => '[^/]+'])
        ->and($route->compile()->getRegex())->toBe('{^/nest/chapters/(?P<chapter>[^/]++)/dashed\-verses/(?P<record>[^/]+)\-preview$}sD');
});

it('never asks a nested page to mount the parent keys the loader folds itself', function (): void {
    // `EditVerse::mount()` takes `$record` and `$parentRecords` — never a
    // `$chapter` — so the parent parameter has to be admitted by the loader's
    // own list rather than by the page.
    $route = nestedResourceLoader()()->get('sylament.nest.resources.chapters.verses.lines.edit');

    expect($route?->getPath())->toBe('/nest/chapters/{chapter}/verses/{verse}/lines/{record}/edit')
        ->and($route->getDefault('_sylament_parent_params'))->toBe(['chapter', 'verse']);
});
