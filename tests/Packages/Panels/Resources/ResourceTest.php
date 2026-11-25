<?php

declare(strict_types=1);

use Doctrine\DBAL\Exception\DriverException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;
use Sylament\Panels\PanelRegistry;
use Sylament\Panels\Resources\Resource;
use Sylament\Schemas\Schema;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\Article;
use Sylament\Tests\Fixtures\Entity\HookedArticle;
use Sylament\Tests\Fixtures\Entity\Invoice;
use Sylament\Tests\Fixtures\Entity\InvoiceLine;
use Sylament\Tests\Fixtures\Entity\OrderLine;
use Sylament\Tests\Fixtures\Entity\SkuParcel;
use Sylament\Tests\Fixtures\Panels\Record\Placard;
use Sylament\Tests\Fixtures\Panels\Resources\ArticleResource;
use Sylament\Tests\Fixtures\Panels\Resources\BareResource;
use Sylament\Tests\Fixtures\Panels\Resources\IdKeyedArticleResource;
use Sylament\Tests\Fixtures\Panels\Resources\InvoiceKeyedLineResource;
use Sylament\Tests\Fixtures\Panels\Resources\InvoiceLineResource;
use Sylament\Tests\Fixtures\Panels\Resources\NoIndexResource;
use Sylament\Tests\Fixtures\Panels\Resources\OrderKeyedLineResource;
use Sylament\Tests\Fixtures\Panels\Resources\OrderLineResource;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ListArticles;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ListScopedArticles;
use Sylament\Tests\Fixtures\Panels\Resources\PlacardResource;
use Sylament\Tests\Fixtures\Panels\Resources\ResourceStatus;
use Sylament\Tests\Fixtures\Panels\Resources\ScopedArticleResource;
use Sylament\Tests\Fixtures\Panels\Resources\ShelfLabels\ShelfLabelResource;
use Sylament\Tests\Fixtures\Panels\Resources\SluggedArticleResource;
use Sylament\Tests\Fixtures\Panels\Resources\TitleKeyedArticleResource;
use Sylament\Tests\Fixtures\Panels\Resources\UnmappedKeyArticleResource;
use Sylament\Tests\Fixtures\Panels\Resources\UnmappedRecordResource;
use Sylament\Tests\Fixtures\Panels\Selectors;
use Sylament\Tests\Fixtures\SecondaryEntity\Observatory;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Sylament\Tests\Fixtures\Tables\Selectors as TablesSelectors;
use Sylament\Tests\Infrastructure\Doctrine\TestPlatform;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PropertyAccess\Exception\UninitializedPropertyException;
use Symfony\Component\Routing\RouterInterface;
use Twig\Error\RuntimeError;

it('hands back the schema and the table it is given when the resource configures neither', function (): void {
    $resource = $this::getContainer()->get(BareResource::class);
    $schema = Schema::make();
    $table = Table::make($this->createLiveComponent(ListArticles::class)->component());

    expect($resource->form($schema))->toBe($schema)
        ->and($resource->infolist($schema))->toBe($schema)
        ->and($resource->table($table))->toBe($table);
});

it('refuses to guess a record class it was not given, naming the resource and the property', function (): void {
    expect(fn (): string => $this::getContainer()->get(BareResource::class)->getRecordClass())
        ->toThrow(LogicException::class, 'The resource [' . BareResource::class . '] must declare its record class — set the $recordClass static property.');
});

it('builds a fresh record query on every call, selecting the record class as `record`', function (): void {
    $resource = $this::getContainer()->get(ArticleResource::class);
    $query = $resource->getRecordQuery();

    expect($query->getDQL())->toBe('SELECT record FROM ' . Article::class . ' record')
        ->and($resource->getRecordQuery())->not->toBe($query);
});

it('throws from the first crumb of any page of a parentless resource with no index page', function (): void {
    expect(fn (): mixed => $this::getContainer()->get(NoIndexResource::class)->getIndexUrl())
        ->toThrow(LogicException::class, 'The resource [' . NoIndexResource::class . '] does not have an [index] page. Define [getIndexUrl()] for alternative routing.')
        ->and(fn (): mixed => $this->visit('/resource-routes/no-index/create'))
        ->toThrow(RuntimeError::class, 'The resource [' . NoIndexResource::class . '] does not have an [index] page.');
});

describe('labels', function (): void {
    it("labels its records after the record class's name, and title-cases every word for its navigation", function (): void {
        $resource = $this::getContainer()->get(ArticleResource::class);

        expect([$resource->getRecordLabel(), $resource->getPluralRecordLabel(), $resource->getTitleCaseRecordLabel(), $resource->getTitleCasePluralRecordLabel(), $resource->getNavigationLabel()])
            ->toBe(['article', 'articles', 'Article', 'Articles', 'Articles']);
    });

    it('answers the labels it declares, title-casing every word of them', function (): void {
        $resource = new class extends Resource {
            protected static ?string $recordClass = Article::class;

            protected static ?string $recordLabel = 'bill of lading';

            protected static ?string $pluralRecordLabel = 'bills of lading';
        };

        expect([$resource->getRecordLabel(), $resource->getPluralRecordLabel(), $resource->getTitleCaseRecordLabel(), $resource->getTitleCasePluralRecordLabel(), $resource->getNavigationLabel()])
            ->toBe(['bill of lading', 'bills of lading', 'Bill Of Lading', 'Bills Of Lading', 'Bills Of Lading']);
    });

    it("pluralises a declared singular label through the inflector's irregular forms", function (): void {
        $resource = new class extends Resource {
            protected static ?string $recordClass = Article::class;

            protected static ?string $recordLabel = 'person';
        };

        expect($resource->getPluralRecordLabel())->toBe('people');
    });
});

describe('slug', function (): void {
    it('mounts the resource under the slug it derives from its class name', function (): void {
        $page = $this->visit('/admin/articles');

        expect($page->filter(Selectors::PAGE_HEADING)->text())->toBe('Articles');
    });

    it('derives the slug from the class basename alone, stripping a trailing `Resource` and pluralising', function (string $class, string $slug): void {
        expect(new $class()->getSlug())->toBe($slug);
    })->with([
        'two words, under a namespace segment named after their plural' => [ShelfLabelResource::class, 'shelf-labels'],
        '`Resource` leading the name, an irregular plural' => [ResourceStatus::class, 'resource-statuses'],
    ]);

    it('turns a slash in a declared slug into a dot in the route base name', function (): void {
        $resource = new class extends Resource {
            protected static ?string $slug = 'archive/articles';
        };
        $admin = $this::getContainer()->get(PanelRegistry::class)->get('admin');

        expect($resource->getRouteBaseName($admin))->toBe('sylament.admin.resources.archive.articles');
    });
});

describe('record title', function (): void {
    it('titles a record by the attribute it names', function (): void {
        $this->seed($article = new Article('Draft'));

        expect($this::getContainer()->get(ArticleResource::class)->getRecordTitle($this->reload($article)))->toBe('Draft');
    });

    it('falls back to the record label where the attribute reads no printable title', function (?Placard $record): void {
        expect($this::getContainer()->get(PlacardResource::class)->getRecordTitle($record))->toBe('placard');
    })->with([
        'no record' => [null],
        'a caption not set yet' => [new Placard()],
        'an empty string' => [Placard::captioned('')],
        'whitespace' => [Placard::captioned('   ')],
        'false' => [Placard::captioned(false)],
        'null' => [Placard::captioned(null)],
        'an array' => [Placard::captioned(['Sale'])],
    ]);

    it('titles a record by a stringable, a number or a zero', function (mixed $caption, string $title): void {
        expect($this::getContainer()->get(PlacardResource::class)->getRecordTitle(Placard::captioned($caption)))->toBe($title);
    })->with([
        'a stringable' => [new class implements Stringable {
            public function __toString(): string
            {
                return 'Closing down';
            }
        }, 'Closing down'],
        'an integer' => [42, '42'],
        'a zero' => [0, '0'],
    ]);

    it("names a record whose title is blank by the resource's record label in its edit page's trail", function (): void {
        $this->seed($article = new Article(''));

        $page = $this->visit("/admin/articles/{$article->id}/edit");

        expect($page->filter(SupportSelectors::BREADCRUMB)->each(static fn ($crumb): string => $crumb->text()))->toBe(['Articles', 'article', 'Edit']);
    });
});

describe('record resolution', function (): void {
    it('resolves the record a minted key names, whatever its key is made of', function (string $resource, object $record, object $sibling): void {
        $this->seed($record, $sibling);
        $resource = $this::getContainer()->get($resource);

        expect($resource->resolveRecordRouteBinding($resource->getRecordRouteKey($record)))->toEqual($this->reload($record));
    })->with([
        'a single identifier' => fn (): array => [ArticleResource::class, new Article('Wanted'), new Article('Other')],
        'scalar fields' => fn (): array => [OrderLineResource::class, new OrderLine(order: 7, line: 2, note: 'Wanted'), new OrderLine(order: 7, line: 3)],
        'an association plus a scalar' => fn (): array => [InvoiceLineResource::class, new InvoiceLine($seven = new Invoice(7), line: 2, note: 'Wanted'), new InvoiceLine($seven, line: 3)],
    ]);

    it('answers no record for a well-formed key naming no row, in one statement', function (): void {
        $this->seed(new Article('Kept'));

        expect($this::getContainer()->get(ArticleResource::class)->resolveRecordRouteBinding('999'))->toBeNull()
            ->and($this->statementsRun())->toHaveCount(1);
    });

    it('answers no record for a key the record class could never mint, without running a statement', function (string $resource, string $key): void {
        $this->seed(new Article('Kept'), new OrderLine(order: 7, line: 2));

        expect($this::getContainer()->get($resource)->resolveRecordRouteBinding($key))->toBeNull()
            ->and($this->statementsRun())->toBe([]);
    })->with([
        'a single integer with a leading zero' => [ArticleResource::class, '01'],
        'one part of two' => [OrderLineResource::class, '7'],
    ]);

    it("serves a 404 for a row outside the resource's own query, which its list leaves out", function (): void {
        $this->seed($alpha = new Article('Alpha'), $beta = new Article('Beta'));

        $page = $this->visit("/resource-routes/scoped-articles/{$alpha->id}/edit");

        expect($page->filter(Selectors::field('title'))->attr('value'))->toBe('Alpha')
            ->and(fn (): mixed => $this->visit("/resource-routes/scoped-articles/{$beta->id}/edit"))->toThrow(NotFoundHttpException::class);

        $this->createLiveComponent(ListScopedArticles::class, ['panelId' => 'resource-routes'])
            ->assertCanSeeTableRecords([$alpha])
            ->assertCanNotSeeTableRecords([$beta]);
    });

    it('resolves through a memoised record query under an alias of its own, and leaves that query as it was', function (): void {
        $this->seed($apple = new Article('Apple'), $apricot = new Article('Apricot'));
        $resource = $this::getContainer()->get(ScopedArticleResource::class);
        $dql = $resource->getRecordQuery()->getDQL();

        expect($resource->resolveRecordRouteBinding((string) $apple->id)?->title)->toBe('Apple')
            ->and($resource->resolveRecordRouteBinding((string) $apricot->id)?->title)->toBe('Apricot')
            ->and($resource->getRecordQuery()->getDQL())->toBe($dql);
    });

    it('resolves the key against the query a modifier answers with', function (): void {
        $this->seed($alpha = new Article('Alpha'), $beta = new Article('Beta'));
        $resource = $this::getContainer()->get(ArticleResource::class);
        $betaOnly = fn (): QueryBuilder => $this->entityManager()->createQueryBuilder()->select('kept')->from(Article::class, 'kept')->where("kept.title = 'Beta'");

        expect($resource->resolveRecordRouteBinding((string) $alpha->id, $betaOnly))->toBeNull()
            ->and($resource->resolveRecordRouteBinding((string) $beta->id, $betaOnly)?->title)->toBe('Beta');
    });

    it('keeps the query it handed a modifier that answers null, with whatever the modifier added to it', function (): void {
        $this->seed($alpha = new Article('Alpha'), $beta = new Article('Beta'));
        $resource = $this::getContainer()->get(ArticleResource::class);
        $narrowToBeta = static function (QueryBuilder $query): null {
            $query->andWhere("record.title = 'Beta'");

            return null;
        };

        expect($resource->resolveRecordRouteBinding((string) $alpha->id, $narrowToBeta))->toBeNull()
            ->and($resource->resolveRecordRouteBinding((string) $beta->id, $narrowToBeta)?->title)->toBe('Beta');
    });

    it('refuses a modifier answering anything but a query builder or null, naming the resource and the answer', function (): void {
        expect(fn (): ?object => $this::getContainer()->get(ArticleResource::class)->resolveRecordRouteBinding('1', static fn (): string => 'record'))
            ->toThrow(LogicException::class, 'The query modifier passed to [' . ArticleResource::class . '::resolveRecordRouteBinding()] must return a query builder or null; it returned string.');
    });

    it('decodes the key against the declared record class even when a modifier roots the query elsewhere', function (): void {
        $this->seed($hooked = new HookedArticle('Hooked'), new SkuParcel('abc'));
        $resource = $this::getContainer()->get(ArticleResource::class);
        $em = $this->entityManager();

        expect($resource->resolveRecordRouteBinding((string) $hooked->id, static fn (): QueryBuilder => $em->createQueryBuilder()->select('h')->from(HookedArticle::class, 'h')))
            ->toBeInstanceOf(HookedArticle::class);

        $this->seed();

        expect($resource->resolveRecordRouteBinding('abc', static fn (): QueryBuilder => $em->createQueryBuilder()->select('p')->from(SkuParcel::class, 'p')))->toBeNull()
            ->and($this->statementsRun())->toBe([]);
    });

    it('refuses a query naming no root alias to resolve against', function (): void {
        $em = $this->entityManager();

        expect(fn (): ?object => $this::getContainer()->get(ArticleResource::class)->resolveRecordRouteBinding('1', static fn (): QueryBuilder => $em->createQueryBuilder()))
            ->toThrow(LogicException::class, 'The record query of resource [' . ArticleResource::class . '] names no root alias to resolve a record against.');
    });
});

describe('route key', function (): void {
    it("refuses to key a record of another class, and a resource declaring no record class refuses for want of one", function (): void {
        $this->seed($line = new OrderLine(order: 7, line: 2));

        expect(fn (): string => $this::getContainer()->get(ArticleResource::class)->getRecordRouteKey($line))
            ->toThrow(LogicException::class, 'Resource [' . ArticleResource::class . '] speaks for [' . Article::class . ']; it cannot key a [' . OrderLine::class . '].')
            ->and(fn (): string => $this::getContainer()->get(BareResource::class)->getRecordRouteKey($line))
            ->toThrow(LogicException::class, 'must declare its record class');
    });
});

describe('custom route key', function (): void {
    it("keys the record's URL by the property it names, and opens its page by that value", function (): void {
        $this->seed($article = new Article('Alpha'));
        $resource = $this::getContainer()->get(TitleKeyedArticleResource::class);

        $page = $this->visit('/resource-routes/title-keyed-articles/Alpha/edit');

        expect($resource->getRecordRouteKey($article))->toBe('Alpha')
            ->and($resource->getUrl('edit', ['record' => $article], isAbsolute: false, panel: 'resource-routes'))->toBe('/resource-routes/title-keyed-articles/Alpha/edit')
            ->and($page->filter(Selectors::field('title'))->attr('value'))->toBe('Alpha')
            ->and($page->filter(Selectors::PAGE . '.sy-pa-resource-record-Alpha')->count())->toBe(1);
    });

    it('keys and resolves by the lone identifier field it names', function (): void {
        $this->seed($article = new Article('Alpha'));
        $resource = $this::getContainer()->get(IdKeyedArticleResource::class);

        expect($resource->getRecordRouteKey($article))->toBe((string) $article->id)
            ->and($resource->resolveRecordRouteBinding((string) $article->id)?->title)->toBe('Alpha');
    });

    it('refuses a blank value on the way out', function (): void {
        expect(fn (): string => $this::getContainer()->get(TitleKeyedArticleResource::class)->getRecordRouteKey(new Article('')))
            ->toThrow(LogicException::class, 'The route key of [' . Article::class . '] is not a printable value.');
    });

    it('lets a route key that cannot be read through, where a record title falls back', function (): void {
        expect(fn (): string => $this::getContainer()->get(IdKeyedArticleResource::class)->getRecordRouteKey(new Article('Unsaved')))
            ->toThrow(UninitializedPropertyException::class);
    });

    it('raises on a value the column holds twice', function (): void {
        $this->seed(new Article('Twin'), new Article('Twin'));

        expect(fn (): ?object => $this::getContainer()->get(TitleKeyedArticleResource::class)->resolveRecordRouteBinding('Twin'))
            ->toThrow(NonUniqueResultException::class);
    });

    it('binds a non-numeric value against a numeric column as it is, which the platform matches no row for or refuses', function (): void {
        $this->seed(new Article('Alpha'));
        $resolve = fn (): ?object => $this::getContainer()->get(IdKeyedArticleResource::class)->resolveRecordRouteBinding('abc');

        match (TestPlatform::current()) {
            TestPlatform::PostgreSql => expect($resolve)->toThrow(DriverException::class),
            TestPlatform::Sqlite, TestPlatform::MySql => expect($resolve())->toBeNull()->and($this->statementsRun())->toHaveCount(1),
        };
    });

    it('refuses on first use a name that cannot name one record, before any statement runs', function (string $resource, string $reason): void {
        $this->seed(new Article('Alpha'));

        expect(fn (): ?object => $this::getContainer()->get($resource)->resolveRecordRouteBinding('1'))
            ->toThrow(LogicException::class, $reason)
            ->and($this->statementsRun())->toBe([]);
    })->with([
        'an association' => [InvoiceKeyedLineResource::class, 'Resource [' . InvoiceKeyedLineResource::class . '] names [invoice] as its $recordRouteKeyName, but [' . InvoiceLine::class . '::$invoice] is an association'],
        'no mapped field' => [UnmappedKeyArticleResource::class, 'Resource [' . UnmappedKeyArticleResource::class . '] names [slug] as its $recordRouteKeyName, but [' . Article::class . '] maps no field of that name.'],
        'one field of two' => [OrderKeyedLineResource::class, 'Resource [' . OrderKeyedLineResource::class . '] names [order] as its $recordRouteKeyName, but it is one field of the several [' . OrderLine::class . '] is identified by'],
    ]);

    it('never refuses a resource that keys and resolves its records itself', function (): void {
        $this->seed($article = new Article('Alpha'));
        $resource = $this::getContainer()->get(SluggedArticleResource::class);

        expect($resource->getRecordRouteKey($article))->toBe("article-{$article->id}")
            ->and($resource->resolveRecordRouteBinding("article-{$article->id}")?->title)->toBe('Alpha');
    });
});

describe('urls', function (): void {
    it('answers the index URL for no page name or a blank one, absolute unless asked otherwise', function (?string $name): void {
        $resource = $this::getContainer()->get(ArticleResource::class);

        expect($resource->getUrl($name))->toBe('http://localhost/admin/articles')
            ->and($resource->getUrl($name, isAbsolute: false))->toBe('/admin/articles');
    })->with([
        'no name' => [null],
        'an empty name' => [''],
        'whitespace' => ['  '],
    ]);

    it("generates a page's URL with the record's route key in place of the record", function (string $resource, object $record, string $path): void {
        $this->seed($record);

        expect($this::getContainer()->get($resource)->getUrl('edit', ['record' => $record], isAbsolute: false))->toBe($path);
    })->with([
        'a single identifier' => fn (): array => [ArticleResource::class, new Article('Alpha'), '/admin/articles/1/edit'],
        'scalar fields' => fn (): array => [OrderLineResource::class, new OrderLine(order: 7, line: 2), '/admin/order-lines/7~2/edit'],
        'an association plus a scalar' => fn (): array => [InvoiceLineResource::class, new InvoiceLine(new Invoice(7), line: 2), '/admin/invoice-lines/7~2/edit'],
    ]);

    it('generates for the panel it is handed, by object or by id, and the default panel otherwise', function (): void {
        $resource = $this::getContainer()->get(ArticleResource::class);
        $panel = $this::getContainer()->get(PanelRegistry::class)->get('resource-routes');

        expect($resource->getUrl('create', panel: $panel, isAbsolute: false))->toBe('/resource-routes/articles/create')
            ->and($resource->getUrl('create', panel: 'resource-routes', isAbsolute: false))->toBe('/resource-routes/articles/create')
            ->and($resource->getIndexUrl(panel: 'resource-routes', isAbsolute: false))->toBe('/resource-routes/articles')
            ->and($resource->getUrl('create', isAbsolute: false))->toBe('/admin/articles/create');
    });

    it('refuses a panel id no panel is registered under', function (): void {
        expect(fn (): string => $this::getContainer()->get(ArticleResource::class)->getUrl('create', panel: 'x'))
            ->toThrow(LogicException::class, 'No panel with the ID "x" is registered.');
    });

    it('composes the route base name its routes are registered under', function (): void {
        $admin = $this::getContainer()->get(PanelRegistry::class)->get('admin');
        $baseName = $this::getContainer()->get(ArticleResource::class)->getRouteBaseName($admin);

        expect($baseName)->toBe('sylament.admin.resources.articles')
            ->and($this::getContainer()->get(RouterInterface::class)->getRouteCollection()->get("{$baseName}.index")?->getPath())->toBe('/admin/articles');
    });

    it('answers for the panel its page was opened in when a live request mints the URL', function (): void {
        $this->seed($alpha = new Article('Alpha'));

        $render = $this->createLiveComponent(ListScopedArticles::class, ['panelId' => 'resource-routes'])
            ->refresh()
            ->render()
            ->crawler();

        expect($render->filter(Selectors::HEADER_ACTIONS . ' a[href]')->attr('href'))->toBe('http://localhost/resource-routes/scoped-articles/create')
            ->and($render->filter(SupportSelectors::LINKED_BREADCRUMB)->attr('href'))->toBe('http://localhost/resource-routes/scoped-articles')
            ->and(fn (): mixed => $this->createLiveComponent(ListScopedArticles::class)->render())
            ->toThrow(RuntimeError::class, 'Unable to generate a URL for the named route "sylament.admin.resources.scoped-articles.index"');
    });
});

describe('navigation', function (): void {
    it("lights the resource's item on each of its pages", function (string $uri): void {
        $active = $this->visit($uri)->filter(Selectors::ACTIVE_NAVIGATION_ITEM);

        expect($active->count())->toBe(1)
            ->and($active->attr('href'))->toBe('/admin/articles')
            ->and($active->filter(Selectors::NAVIGATION_ITEM_LABEL)->text())->toBe('Articles');
    })->with([
        'its index page' => ['/admin/articles'],
        'its create page' => ['/admin/articles/create'],
    ]);

    it('draws the item its navigation getters configure', function (): void {
        $item = $this->visit('/resource-routes/articles')->filter(Selectors::navigationGroup('Records'));

        expect($item->filter(Selectors::NAVIGATION_ITEM_LABEL)->text())->toBe('Archive')
            ->and($item->filter('a[href]')->attr('href'))->toBe('/resource-routes/articles-archive')
            ->and($item)->toContainElement(SupportSelectors::ICON, 'rectangle-stack')
            ->and($item)->toContainElement(SupportSelectors::BADGE . SupportSelectors::color('danger'), '3');
    });

    it('contributes no item for a resource with no index page', function (): void {
        $page = $this->visit('/resource-routes/articles');

        expect($page->filter(Selectors::NAVIGATION_ITEM . ' a')->each(static fn ($link): ?string => $link->attr('href')))
            ->toBe(['/resource-routes/articles', '/resource-routes/scoped-articles', '/resource-routes/title-keyed-articles', '/resource-routes/articles-archive']);
    });

    it('lights only the item whose route names continue past its base name and a dot', function (): void {
        $active = $this->visit('/resource-routes/articles-archive')->filter(Selectors::ACTIVE_NAVIGATION_ITEM);

        expect($active->count())->toBe(1)
            ->and($active->attr('href'))->toBe('/resource-routes/articles-archive');
    });
});

describe('built outside the container', function (): void {
    it('refuses whatever needs a service, naming the resource', function (Closure $call): void {
        expect(fn (): mixed => $call(new ArticleResource()))
            ->toThrow(LogicException::class, 'The resource [' . ArticleResource::class . '] was built outside the container, so it holds no injected services; register it as an autowired service.');
    })->with([
        'its record query' => [static fn (ArticleResource $resource): QueryBuilder => $resource->getRecordQuery()],
        'a URL' => [static fn (ArticleResource $resource): string => $resource->getUrl()],
        'a record title' => [static fn (ArticleResource $resource): string => $resource->getRecordTitle(new Article('Alpha'))],
    ]);

    it('still derives what needs no service', function (): void {
        $resource = new ArticleResource();
        $admin = $this::getContainer()->get(PanelRegistry::class)->get('admin');

        expect([$resource->getSlug(), $resource->getRecordLabel(), $resource->getPluralRecordLabel(), array_keys($resource->getPages()), $resource->hasPage('edit'), $resource->getRouteBaseName($admin)])
            ->toBe(['articles', 'article', 'articles', ['index', 'create', 'edit'], true, 'sylament.admin.resources.articles']);
    });
});

describe('a record class another manager maps', function (): void {
    it('lists its records and opens one by its route key', function (): void {
        $this->seed($observatory = new Observatory('Kitt Peak'));

        expect($this->visit('/secondary-manager/observatories'))->toContainElement(TablesSelectors::row((string) $observatory->id), 'Kitt Peak')
            ->and($this->visit("/secondary-manager/observatories/{$observatory->id}/edit")->filter(Selectors::field('name'))->attr('value'))->toBe('Kitt Peak');
    });

    it('refuses a record class no manager maps, naming it', function (): void {
        expect(fn (): mixed => $this::getContainer()->get(UnmappedRecordResource::class)->getRecordQuery())
            ->toThrow(LogicException::class, 'No Doctrine manager handles stdClass.');
    });
});
