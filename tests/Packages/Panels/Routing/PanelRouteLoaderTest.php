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
use Sylament\Schemas\Identity\RecordKey;
use Sylament\Tests\Fixtures\Entity\Article;
use Sylament\Tests\Fixtures\Entity\OrderLine;
use Sylament\Tests\Fixtures\Panels\AdminPanelProvider;
use Sylament\Tests\Fixtures\Panels\Pages\SluggedSettingsPage;
use Sylament\Tests\Fixtures\Panels\Resources\ArticleResource;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditArticle;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ListArticles;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Routing\RouteCollection;

/*
 * A panel the loader refuses, or one shaped only for its join, is built inline over a registry of
 * its own: a panel the loader refuses can never join the kernel's, whose every route build it
 * would break.
 */

it("mounts every page of a panel as a GET route at the panel's path, which PageController serves for that page", function (): void {
    $routes = $this::getContainer()->get('router')->getRouteCollection();
    $settings = $routes->get('sylament.panel-routing.pages.settings');

    expect($settings?->getPath())->toBe('/panel-routing/settings')
        ->and($settings?->getMethods())->toBe(['GET'])
        ->and($settings?->getDefaults())->toBe(['_controller' => PageController::class, '_sylament_panel' => 'panel-routing', '_sylament_page' => SluggedSettingsPage::class])
        ->and($routes->get('sylament.panel-routing.pages.dashboard')?->getPath())->toBe('/panel-routing');
});

it("mounts every page of a resource under the resource's slug, by the key it is registered under", function (): void {
    $router = $this::getContainer()->get('router');
    $edit = $router->getRouteCollection()->get('sylament.admin.resources.articles.edit');

    expect($router->generate('sylament.admin.resources.articles.index'))->toBe('/admin/articles')
        ->and($router->generate('sylament.admin.resources.articles.create'))->toBe('/admin/articles/create')
        ->and($router->generate('sylament.admin.resources.articles.edit', ['record' => 12]))->toBe('/admin/articles/12/edit')
        ->and($edit?->getMethods())->toBe(['GET'])
        ->and($edit?->getDefaults())->toBe(['_controller' => PageController::class, '_sylament_panel' => 'admin', '_sylament_page' => EditArticle::class]);
});

it('narrows a record placeholder a separator follows, so a key spelling that separator generates a URL that matches back', function (string $route, string $key): void {
    $router = $this::getContainer()->get('router');

    $url = $router->generate("sylament.panel-routing.resources.previews.{$route}", ['record' => $key]);

    expect($router->getRouteCollection()->get("sylament.panel-routing.resources.previews.{$route}")?->getRequirements())->toBe(['record' => '[^/]+'])
        ->and($router->match($url))->toMatchArray(['_route' => "sylament.panel-routing.resources.previews.{$route}", 'record' => $key]);
})->with([
    'a dash, in a slug' => ['dashed', 'spring-sale'],
    'a tilde, in a composite key' => fn (): array => ['tilded', RecordKey::of($this->entityManager(), new OrderLine(order: 7, line: 2))],
    'a dot, in a file name' => ['dotted', 'report.v2'],
]);

it('leaves a record placeholder a slash or a second placeholder follows to the default pattern, which splits at the first separator', function (): void {
    $router = $this::getContainer()->get('router');
    $routes = $router->getRouteCollection();

    expect($routes->get('sylament.panel-routing.resources.previews.edit')?->getRequirements())->toBe([])
        ->and($routes->get('sylament.panel-routing.resources.previews.localised')?->getRequirements())->toBe([])
        ->and($router->match('/panel-routing/previews/5-en-GB'))->toMatchArray(['_route' => 'sylament.panel-routing.resources.previews.localised', 'record' => '5', 'locale' => 'en-GB']);
});

it('joins the panel, resource and page paths with exactly one slash, at the site root too', function (): void {
    $settings = new class extends Page {
        protected static ?string $slug = 'settings';
    };
    $slashless = new class extends Resource {
        protected static ?string $recordClass = Article::class;

        protected static ?string $slug = 'notes';

        public function getPages(): array
        {
            return ['edit' => EditArticle::route('{record}/edit')];
        }
    };
    $root = new class ($settings::class) extends PanelProvider {
        public function __construct(private readonly string $page)
        {
        }

        public function panel(Panel $panel): Panel
        {
            return $panel->id('panel-root')->pages([Dashboard::class, $this->page]);
        }
    };
    $trailing = new class ($settings::class, $slashless::class) extends PanelProvider {
        public function __construct(private readonly string $page, private readonly string $resource)
        {
        }

        public function panel(Panel $panel): Panel
        {
            return $panel->id('panel-trailing')->path('panel-trailing/')->pages([Dashboard::class, $this->page])->resources([$this->resource]);
        }
    };

    $routes = new PanelRouteLoader(new PanelRegistry([$root, $trailing]), new ResourceRegistry(new ServiceLocator([$slashless::class => fn (): Resource => $slashless])))();

    expect($routes->get('sylament.panel-root.pages.dashboard')?->getPath())->toBe('/')
        ->and($routes->get('sylament.panel-root.pages.settings')?->getPath())->toBe('/settings')
        ->and($routes->get('sylament.panel-trailing.pages.dashboard')?->getPath())->toBe('/panel-trailing')
        ->and($routes->get('sylament.panel-trailing.pages.settings')?->getPath())->toBe('/panel-trailing/settings')
        ->and($routes->get('sylament.panel-trailing.resources.notes.edit')?->getPath())->toBe('/panel-trailing/notes/{record}/edit');
});

it('tracks the file of every page, resource and provider the routes derive from', function (): void {
    $routes = new PanelRouteLoader(new PanelRegistry([new AdminPanelProvider()]), $this::getContainer()->get(ResourceRegistry::class))();

    $files = array_map(
        static fn (FileResource $file): string => $file->getResource(),
        array_filter($routes->getResources(), static fn (object $resource): bool => $resource instanceof FileResource),
    );

    expect($files)->toContain(
        new ReflectionClass(AdminPanelProvider::class)->getFileName(),
        new ReflectionClass(ArticleResource::class)->getFileName(),
        new ReflectionClass(ListArticles::class)->getFileName(),
        new ReflectionClass(EditArticle::class)->getFileName(),
    );
});

it('refuses an entry that is not the class it is registered as', function (string $message, Closure $mount): void {
    expect($mount)->toThrow(LogicException::class, $message);
})->with([
    'a pages() entry' => fn (): array => [
        sprintf('The pages() entry "stdClass" on panel "panel-stray-page" is not a %s subclass.', Page::class),
        fn (): RouteCollection => new PanelRouteLoader(new PanelRegistry([new class extends PanelProvider {
            public function panel(Panel $panel): Panel
            {
                return $panel->id('panel-stray-page')->pages([stdClass::class]);
            }
        }]), new ResourceRegistry(new ServiceLocator([])))(),
    ],
    'a resources() entry' => fn (): array => [
        sprintf('The resources() entry "stdClass" on panel "panel-stray-resource" is not a %s subclass.', Resource::class),
        fn (): RouteCollection => new PanelRouteLoader(new PanelRegistry([new class extends PanelProvider {
            public function panel(Panel $panel): Panel
            {
                return $panel->id('panel-stray-resource')->resources([stdClass::class]);
            }
        }]), new ResourceRegistry(new ServiceLocator([])))(),
    ],
    "a resource's getPages() entry" => function (): array {
        $resource = new class extends Resource {
            protected static ?string $recordClass = Article::class;

            protected static ?string $slug = 'strays';

            public function getPages(): array
            {
                return ['index' => new PageRegistration(stdClass::class, '/')];
            }
        };
        $provider = new class ($resource::class) extends PanelProvider {
            public function __construct(private readonly string $resource)
            {
            }

            public function panel(Panel $panel): Panel
            {
                return $panel->id('panel-stray-registration')->resources([$this->resource]);
            }
        };

        return [
            'The getPages() entry "stdClass" on resource "',
            fn (): RouteCollection => new PanelRouteLoader(new PanelRegistry([$provider]), new ResourceRegistry(new ServiceLocator([$resource::class => fn (): Resource => $resource])))(),
        ];
    },
]);

it('refuses a second mount deriving a route name another already holds, naming both', function (string $message, Closure $mount): void {
    expect($mount)->toThrow(LogicException::class, $message);
})->with([
    'two pages sharing a slug' => function (): array {
        $first = new class extends Page {
            protected static ?string $slug = 'settings';
        };
        $second = new class extends Page {
            protected static ?string $slug = 'settings';
        };
        $provider = new class ([$first::class, $second::class]) extends PanelProvider {
            /**
             * @param list<class-string<Page>> $pages
             */
            public function __construct(private readonly array $pages)
            {
            }

            public function panel(Panel $panel): Panel
            {
                return $panel->id('panel-twin-pages')->pages($this->pages);
            }
        };

        return [
            sprintf('The route name "sylament.panel-twin-pages.pages.settings" is already mounted by page %s — page %s derives the same name.', $first::class, $second::class),
            fn (): RouteCollection => new PanelRouteLoader(new PanelRegistry([$provider]), new ResourceRegistry(new ServiceLocator([])))(),
        ];
    },
    'two resources sharing a slug' => function (): array {
        $articles = $this::getContainer()->get(ResourceRegistry::class)->get(ArticleResource::class);
        $twin = new class extends Resource {
            protected static ?string $recordClass = Article::class;

            protected static ?string $slug = 'articles';

            public function getPages(): array
            {
                return ['index' => ListArticles::route('/')];
            }
        };
        $provider = new class ($twin::class) extends PanelProvider {
            public function __construct(private readonly string $twin)
            {
            }

            public function panel(Panel $panel): Panel
            {
                return $panel->id('panel-twin-resources')->resources([ArticleResource::class, $this->twin]);
            }
        };

        return [
            sprintf('The route name "sylament.panel-twin-resources.resources.articles.index" is already mounted by resource page %s (%s) — resource page %s (', ListArticles::class, ArticleResource::class, ListArticles::class),
            fn (): RouteCollection => new PanelRouteLoader(new PanelRegistry([$provider]), new ResourceRegistry(new ServiceLocator([
                ArticleResource::class => fn (): Resource => $articles,
                $twin::class => fn (): Resource => $twin,
            ])))(),
        ];
    },
]);

it('refuses a placeholder the page can mount through neither a mount() parameter nor a public property, taking an inline requirement raw', function (): void {
    $mounted = new class extends Page {
        protected static ?string $slug = 'mounted';

        public static function getRoutePath(): string
        {
            return '/{code}';
        }

        public function mount(string $code): void
        {
        }
    };
    $hydrated = new class extends Page {
        public ?string $code = null;

        protected static ?string $slug = 'hydrated';

        public static function getRoutePath(): string
        {
            return '/hydrated/{code}';
        }
    };
    $ghost = new class extends Page {
        protected static ?string $slug = 'ghost';

        public static function getRoutePath(): string
        {
            return '/{ghost}';
        }
    };
    $inline = new class extends Page {
        protected static ?string $slug = 'inline';

        public static function getRoutePath(): string
        {
            return '/{record<\d+>}';
        }

        public function mount(int|string $record): void
        {
        }
    };
    $mount = static fn (string ...$pages): RouteCollection => new PanelRouteLoader(new PanelRegistry([new class ($pages) extends PanelProvider {
        /**
         * @param list<class-string<Page>> $pages
         */
        public function __construct(private readonly array $pages)
        {
        }

        public function panel(Panel $panel): Panel
        {
            return $panel->id('panel-placeholders')->path('panel-placeholders')->pages($this->pages);
        }
    }]), new ResourceRegistry(new ServiceLocator([])))();

    expect($mount($mounted::class, $hydrated::class)->get('sylament.panel-placeholders.pages.hydrated')?->getPath())->toBe('/panel-placeholders/hydrated/{code}')
        ->and(fn (): RouteCollection => $mount($ghost::class))->toThrow(
            LogicException::class,
            sprintf('Page "%s" is mounted at "/panel-placeholders/{ghost}" with the route parameter "{ghost}" its mount() does not declare and no public property of that name accepts.', $ghost::class),
        )
        ->and(fn (): RouteCollection => $mount($inline::class))->toThrow(LogicException::class, 'with the route parameter "{record<\d+>}" its mount() does not declare');
});
