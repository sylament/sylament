<?php

declare(strict_types=1);

use Sylament\Panels\GlobalSearch\GlobalSearchProviderRegistry;
use Sylament\Panels\GlobalSearch\GlobalSearchResult;
use Sylament\Panels\GlobalSearch\Providers\DefaultGlobalSearchProvider;
use Sylament\Tests\Fixtures\Entity\Article;
use Symfony\Component\HttpFoundation\Request;

beforeEach(function (): void {
    $this->searchOn = function (string $panel, string $term): array {
        $this::getContainer()->get('request_stack')->push(new Request(attributes: ['_sylament_panel' => $panel]));

        return array_map(
            fn (array $results): array => array_map(fn (GlobalSearchResult $result): string => $result->url, $results),
            $this::getContainer()->get(GlobalSearchProviderRegistry::class)->get(DefaultGlobalSearchProvider::class)->getResults($term)->getCategories(),
        );
    };
});

it("answers a category per searchable resource that found something, under its plural label, ordered by sort and else by the panel's order", function (): void {
    $this->seed($moon = new Article('Moon'));

    expect(($this->searchOn)('search', 'Moon'))->toBe([
        'annotated articles' => ["http://localhost/search/annotated-article-searches/{$moon->id}"],
        'articles' => ["http://localhost/search/articles/{$moon->id}/edit"],
        'curated articles' => ['http://localhost/search/curated-article-searches'],
        'verbatim articles' => ["http://localhost/search/verbatim-article-searches/{$moon->id}/edit"],
    ])
        // one statement per searchable resource, the one with no page and the one with nothing to find included
        ->and($this->statementsRun())->toHaveCount(6);
});

it('searches the resources of the panel the request is on', function (): void {
    $this->seed($moon = new Article('Moon'));

    expect(($this->searchOn)('admin', 'Moon'))->toBe(['articles' => ["http://localhost/admin/articles/{$moon->id}/edit"]])
        ->and(array_keys(($this->searchOn)('search', 'Moon')))->toContain('annotated articles');
});

it('lets a resource that throws take the whole search down', function (): void {
    $this->seed(new Article('Kew'));

    expect(fn (): array => ($this->searchOn)('search-custom', 'Kew'))->toThrow(LogicException::class, 'an alias the query fetch-joins as a collection');
});
