<?php

declare(strict_types=1);

use Sylament\Panels\Resources\Pages\CreateRecord;
use Sylament\Panels\Resources\Pages\EditRecord;
use Sylament\Panels\Resources\Pages\ListRecords;
use Sylament\Panels\Resources\Pages\Page as ResourcePage;
use Sylament\Panels\Resources\Pages\PageRegistration;
use Sylament\Panels\Tests\Fixtures\Resources\ArticleResource;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\CreateArticle;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditArticle;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\ListArticles;

final class UnwiredResourcePage extends ResourcePage
{
    protected static string $resource = ArticleResource::class;
}

it('names the resource it belongs to through the static', function (): void {
    expect(UnwiredResourcePage::getResource())->toBe(ArticleResource::class);
});

it('registers a route as a plain page/path pair', function (): void {
    $registration = UnwiredResourcePage::route('/{record}/edit');

    expect($registration)->toBeInstanceOf(PageRegistration::class)
        ->and($registration->page)->toBe(UnwiredResourcePage::class)
        ->and($registration->path)->toBe('/{record}/edit');
});

it('fails fast when the resource registry setter never fired', function (): void {
    new UnwiredResourcePage()->getResourceInstance();
})->throws(LogicException::class, 'register it as an autowired service');

it('defaults the header actions to none', function (): void {
    expect(new UnwiredResourcePage()->getHeaderActions())->toBe([]);
});

it('names the trail crumb of each shipped resource page', function (string $page, string $breadcrumb): void {
    expect(new $page()->getBreadcrumb())->toBe($breadcrumb);
})->with([
    [ListArticles::class, 'List'],
    [CreateArticle::class, 'Create'],
    [EditArticle::class, 'Edit'],
]);

it('falls back to the page title for a resource page naming no crumb of its own', function (): void {
    $named = new class extends ResourcePage {
        protected static string $resource = ArticleResource::class;

        protected static ?string $breadcrumb = 'Archive';
    };

    expect(new UnwiredResourcePage()->getBreadcrumb())->toBe('Unwired Resource Page')
        ->and($named->getBreadcrumb())->toBe('Archive');
});

/*
 * PHP inserts a trait's members over an inherited class's, so a page that
 * re-`use`s actions' `HasActions` silently replaces the base's url hook with the
 * trait's null-returning default — every `CreateAction` on it would then render
 * with no href and no error. `getDeclaringClass()` on a trait method answers the
 * *using* class, so this reads as the base for exactly as long as no subclass
 * re-uses the trait.
 */
it('keeps the url hook declared on the base for every shipped resource page', function (string $page): void {
    expect(new ReflectionMethod($page, 'getDefaultActionUrl')->getDeclaringClass()->getName())
        ->toBe(ResourcePage::class);
})->with([ResourcePage::class, ListRecords::class, CreateRecord::class, EditRecord::class]);
