<?php

declare(strict_types=1);

use Sylament\Panels\Resources\Pages\CreateRecord;
use Sylament\Panels\Resources\Pages\EditRecord;
use Sylament\Panels\Resources\Pages\ListRecords;
use Sylament\Panels\Resources\Pages\Page as ResourcePage;
use Sylament\Panels\Resources\Pages\PageRegistration;
use Sylament\Panels\Tests\Fixtures\Resources\ArticleResource;

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
