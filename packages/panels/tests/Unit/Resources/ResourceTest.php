<?php

declare(strict_types=1);

use Sylament\Panels\Navigation\NavigationItem;
use Sylament\Panels\Panel;
use Sylament\Panels\Resources\Pages\PageRegistration;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Article;
use Sylament\Panels\Tests\Fixtures\Pages\SettingsPage;
use Sylament\Panels\Tests\Fixtures\Resources\ArticleResource;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditArticle;
use Sylament\Schemas\Schema;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

use function Sylament\Support\get_record_label;

final class BlogPost
{
}

final class BlogPostResource extends Resource
{
    protected static ?string $recordClass = BlogPost::class;
}

final class Status
{
}

final class StatusResource extends Resource
{
    protected static ?string $recordClass = Status::class;
}

final class Person
{
}

final class ResourceGroup extends Resource
{
    protected static ?string $recordClass = Article::class;
}

/**
 * Local to this file on purpose: the shared `ArticleResource` fixture grows an
 * `index` page in a later task, which would flip every assertion that pins the
 * pageless arm.
 */
final class PagelessResource extends Resource
{
    protected static ?string $recordClass = Article::class;
}

it('derives the slug from the class basename, pluralized and kebab-cased', function (): void {
    expect(new ArticleResource()->getSlug())->toBe('articles')
        ->and(new BlogPostResource()->getSlug())->toBe('blog-posts');
});

it('strips the Resource suffix only when the class name ends with it', function (): void {
    expect(new ResourceGroup()->getSlug())->toBe('resource-groups');
});

it('prefers an explicit slug', function (): void {
    $resource = new class extends Resource {
        protected static ?string $slug = 'my-articles';

        protected static ?string $recordClass = Article::class;
    };

    expect($resource->getSlug())->toBe('my-articles');
});

it('pluralizes the slug with the inflector last candidate', function (): void {
    expect(new StatusResource()->getSlug())->toBe('statuses');
});

it('derives labels from the record class basename', function (): void {
    $resource = new ArticleResource();

    expect($resource->getRecordLabel())->toBe('article')
        ->and($resource->getPluralRecordLabel())->toBe('articles')
        ->and($resource->getTitleCaseRecordLabel())->toBe('Article')
        ->and($resource->getTitleCasePluralRecordLabel())->toBe('Articles');
});

it('pluralizes only the last word of a multi-word label', function (): void {
    $resource = new BlogPostResource();

    expect($resource->getRecordLabel())->toBe('blog post')
        ->and($resource->getPluralRecordLabel())->toBe('blog posts')
        ->and($resource->getTitleCaseRecordLabel())->toBe('Blog Post')
        ->and($resource->getTitleCasePluralRecordLabel())->toBe('Blog Posts');
});

it('pluralizes irregular and uninflected labels with the inflector last candidate', function (): void {
    $status = new class extends Resource {
        protected static ?string $recordClass = Status::class;
    };

    $person = new class extends Resource {
        protected static ?string $recordClass = Person::class;
    };

    expect($status->getPluralRecordLabel())->toBe('statuses')
        ->and($person->getPluralRecordLabel())->toBe('people');
});

it('prefers explicit labels', function (): void {
    $resource = new class extends Resource {
        protected static ?string $recordClass = Article::class;

        protected static ?string $recordLabel = 'story';

        protected static ?string $pluralRecordLabel = 'stories';
    };

    expect($resource->getRecordLabel())->toBe('story')
        ->and($resource->getPluralRecordLabel())->toBe('stories')
        ->and($resource->getTitleCasePluralRecordLabel())->toBe('Stories');
});

it('throws when the record class is not declared', function (): void {
    $resource = new class extends Resource {};

    $resource->getRecordClass();
})->throws(LogicException::class, 'must declare its record class');

it('composes the route base name from the panel id and slug', function (): void {
    $panel = Panel::make()->id('admin');

    expect(new ArticleResource()->getRouteBaseName($panel))
        ->toBe('sylament.admin.resources.articles');
});

it('reports pages by key', function (): void {
    $withoutPages = new PagelessResource();

    $withIndex = new class extends Resource {
        protected static ?string $recordClass = Article::class;

        public function getPages(): array
        {
            return ['index' => new PageRegistration(SettingsPage::class, '/')];
        }
    };

    expect($withoutPages->hasPage('index'))->toBeFalse()
        ->and($withIndex->hasPage('index'))->toBeTrue()
        ->and($withoutPages->getPages())->toBe([]);
});

it('falls back to the record label when there is no record or no title attribute', function (): void {
    $article = new Article()->setTitle('Hello')->setSlug('hello');

    $untitled = new class extends Resource {
        protected static ?string $recordClass = Article::class;
    };

    expect(new ArticleResource()->hasRecordTitle())->toBeTrue()
        ->and(new ArticleResource()->getRecordTitle(null))->toBe('article')
        ->and($untitled->hasRecordTitle())->toBeFalse()
        ->and($untitled->getRecordTitle($article))->toBe('article');
});

it('fails fast when a resource built outside the container needs a service', function (): void {
    new ArticleResource()->getRecordQuery();
})->throws(LogicException::class, 'was built outside the container');

it('falls back to the navigation icon for the active navigation icon', function (): void {
    $resource = new class extends Resource {
        protected static ?string $recordClass = Article::class;

        protected static string|BackedEnum|null $navigationIcon = 'heroicons:document-text';
    };

    expect($resource->getNavigationIcon())->toBe('heroicons:document-text')
        ->and($resource->getActiveNavigationIcon())->toBe('heroicons:document-text');

    $explicit = new class extends Resource {
        protected static ?string $recordClass = Article::class;

        protected static string|BackedEnum|null $navigationIcon = 'heroicons:document-text';

        protected static string|BackedEnum|null $activeNavigationIcon = 'heroicons:home';
    };

    expect($explicit->getActiveNavigationIcon())->toBe('heroicons:home');
});

it('defaults the navigation label to the title-case plural record label', function (): void {
    expect(new ArticleResource()->getNavigationLabel())->toBe('Articles')
        ->and(new ArticleResource()->getNavigationGroup())->toBe('Content')
        ->and(new ArticleResource()->getNavigationSort())->toBeNull()
        ->and(new ArticleResource()->getNavigationBadge())->toBeNull()
        ->and(new ArticleResource()->getNavigationBadgeColor())->toBeNull()
        ->and(new ArticleResource()->shouldRegisterNavigation())->toBeTrue();
});

it('contributes no navigation item without an index page', function (): void {
    $panel = Panel::make()->id('admin');

    expect(new PagelessResource()->getNavigationItems($panel))->toBe([]);
});

it('contributes one navigation item once an index page exists', function (): void {
    $panel = Panel::make()->id('admin');

    $resource = new class extends Resource {
        protected static ?string $recordClass = Article::class;

        protected static ?string $slug = 'articles';

        public function getPages(): array
        {
            return ['index' => new PageRegistration(SettingsPage::class, '/')];
        }
    };

    $items = $resource->getNavigationItems($panel);

    expect($items)->toHaveCount(1)
        ->and($items[0])->toBeInstanceOf(NavigationItem::class)
        ->and($items[0]->getLabel())->toBe('Articles');
});

it('carries an explicit navigation sort onto the contributed item', function (): void {
    $panel = Panel::make()->id('admin');

    $resource = new class extends Resource {
        protected static ?string $recordClass = Article::class;

        protected static ?string $slug = 'articles';

        protected static ?int $navigationSort = 7;

        public function getPages(): array
        {
            return ['index' => new PageRegistration(SettingsPage::class, '/')];
        }
    };

    expect($resource->getNavigationSort())->toBe(7)
        ->and($resource->getNavigationItems($panel)[0]->getSort())->toBe(7);
});

it('throws when an index url is asked of a resource without an index page', function (): void {
    new PagelessResource()->getIndexUrl();
})->throws(LogicException::class, 'does not have an [index] page');

it('routes a blank url name to the index page', function (): void {
    $panel = Panel::make()->id('admin');

    $resource = new class extends Resource {
        protected static ?string $recordClass = Article::class;

        protected static ?string $slug = 'articles';

        public function getPages(): array
        {
            return ['index' => new PageRegistration(SettingsPage::class, '/')];
        }

        protected function getUrlGenerator(): UrlGeneratorInterface
        {
            // Echoes the route name back, so each assertion reads the route the
            // arm resolved rather than a path a router would have to be fed.
            return new class implements UrlGeneratorInterface {
                public function generate(string $name, array $parameters = [], int $referenceType = self::ABSOLUTE_PATH): string
                {
                    return $name;
                }

                public function setContext(RequestContext $context): void
                {
                }

                public function getContext(): RequestContext
                {
                    return new RequestContext();
                }
            };
        }
    };

    expect($resource->getUrl(panel: $panel))->toBe('sylament.admin.resources.articles.index')
        ->and($resource->getUrl('', panel: $panel))->toBe('sylament.admin.resources.articles.index')
        ->and($resource->getUrl('  ', panel: $panel))->toBe('sylament.admin.resources.articles.index');
});

it('returns the schema untouched from infolist()', function (): void {
    $schema = Schema::make();

    expect(new ArticleResource()->infolist($schema))->toBe($schema);
});

it('derives the record label through the support helper', function (): void {
    expect(new ArticleResource()->getRecordLabel())->toBe(get_record_label(Article::class));
});

it('breadcrumbs with the title-case plural record label unless one is declared', function (): void {
    $named = new class extends Resource {
        protected static ?string $recordClass = Article::class;

        protected static ?string $breadcrumb = 'All posts';
    };

    expect(new ArticleResource()->getBreadcrumb())->toBe('Articles')
        ->and(new BlogPostResource()->getBreadcrumb())->toBe('Blog Posts')
        ->and($named->getBreadcrumb())->toBe('All posts');
});

it('contributes no record sub-navigation by default', function (): void {
    expect(new ArticleResource()->getRecordSubNavigation(new EditArticle()))->toBe([]);
});
