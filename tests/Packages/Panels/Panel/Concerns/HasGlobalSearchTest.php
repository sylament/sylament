<?php

declare(strict_types=1);

use Sylament\Panels\GlobalSearch\Providers\Contracts\GlobalSearchProvider;
use Sylament\Panels\Panel;
use Sylament\Panels\Twig\Components\GlobalSearch;
use Sylament\Tests\Fixtures\Entity\Article;
use Sylament\Tests\Fixtures\Panels\Selectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Symfony\Component\DomCrawler\Crawler;

it('refuses a provider class that is no provider, naming it and the contract', function (): void {
    expect(fn (): Panel => Panel::make()->globalSearch(ArrayObject::class))
        ->toThrow(InvalidArgumentException::class, sprintf('Global search provider ArrayObject does not implement the [%s] interface.', GlobalSearchProvider::class));
});

it("searches through the panel's provider: the default one over its resources, a custom one's own answer, and none with search off", function (): void {
    $this->seed(new Article('Moon'));

    $search = fn (string $panel): Crawler => $this->createLiveComponent(GlobalSearch::class, ['panelId' => $panel])->set('search', 'Moon')->render()->crawler();

    expect($this->globalSearchCategories($search('search')))->toMatchArray(['articles' => ['Moon']])
        ->and($this->globalSearchCategories($search('search-custom')))->toBe(['Shelf' => ['Shelf of Moon']])
        ->and($search('search-off')->filter(Selectors::GLOBAL_SEARCH_RESULTS))->toHaveCount(0);
});

it('mounts the field on a panel that searches a resource, and on none with search off or nothing to search', function (): void {
    $field = function (string $uri): array {
        $page = $this->visit($uri);

        return [$page->filter(Selectors::TOPBAR)->count(), $page->filter(Selectors::GLOBAL_SEARCH)->count()];
    };

    expect($field('/search/articles'))->toBe([1, 1])
        ->and($field('/search-off/articles'))->toBe([1, 0])
        ->and($field('/pages/notes-page'))->toBe([1, 0]);
});

it('tunes the field by its debounce, key bindings and suffix, and by a half-second debounce, no binding and no suffix otherwise', function (): void {
    $field = function (string $panel): array {
        $search = $this->createLiveComponent(GlobalSearch::class, ['panelId' => $panel])->render()->crawler();

        return [
            $search->filter('#sy-pa-global-search-input')->attr('data-model'),
            json_decode((string) $search->filter('[data-controller="sylament--panels--global-search"]')->attr('data-sylament--panels--global-search-key-bindings-value'), true),
            $search->filter(SupportSelectors::INLINE_SUFFIX)->each(fn (Crawler $suffix): string => $suffix->text()),
        ];
    };

    expect($field('search'))->toBe(['debounce(1000)|search', ['mod+k', 'ctrl+/'], ['Ctrl K']])
        ->and($field('admin'))->toBe(['debounce(500)|search', [], []]);
});

it('turns its debounce into the milliseconds the field waits', function (string $debounce, int $milliseconds): void {
    expect(Panel::make()->globalSearchDebounce($debounce)->getGlobalSearchDebounceMilliseconds())->toBe($milliseconds);
})->with([
    'milliseconds' => ['250ms', 250],
    'seconds' => ['1.5s', 1500],
    'a bare number' => ['300', 300],
    'nothing it can read' => ['soon', 500],
]);
