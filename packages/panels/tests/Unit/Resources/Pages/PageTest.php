<?php

declare(strict_types=1);

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
