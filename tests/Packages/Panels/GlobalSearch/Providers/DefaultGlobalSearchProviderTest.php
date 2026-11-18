<?php

declare(strict_types=1);

use Sylament\Panels\GlobalSearch\GlobalSearchProviderRegistry;
use Sylament\Panels\GlobalSearch\GlobalSearchResults;
use Sylament\Panels\GlobalSearch\Providers\DefaultGlobalSearchProvider;
use Sylament\Tests\Fixtures\Entity\Article;
use Symfony\Component\HttpFoundation\Request;

beforeEach(function (): void {
    $this->requests = $this::getContainer()->get('request_stack');
    $this->provider = $this::getContainer()->get(GlobalSearchProviderRegistry::class)->get(DefaultGlobalSearchProvider::class);
});

it("answers a category per searchable resource that found something, under its plural label, ordered by sort and else by the panel's order", function (): void {
    $this->seed($moon = new Article('Moon'));

    $this->requests->push(new Request(attributes: ['_sylament_panel' => 'search']));

    expect($this->globalSearchUrls($this->provider->getResults('Moon')))->toBe([
        'verbatim articles' => ["http://localhost/search/verbatim-article-searches/{$moon->id}/edit"],
        'articles' => ["http://localhost/search/articles/{$moon->id}/edit"],
        'curated articles' => ['http://localhost/search/curated-article-searches'],
        'annotated articles' => ["http://localhost/search/annotated-article-searches/{$moon->id}"],
    ])
        // one statement per searchable resource, the one with no page and the one with nothing to find included
        ->and($this->statementsRun())->toHaveCount(6);
});

it('searches the resources of the panel the request is on', function (): void {
    $this->seed($moon = new Article('Moon'));

    $this->requests->push(new Request(attributes: ['_sylament_panel' => 'admin']));
    $admin = $this->globalSearchUrls($this->provider->getResults('Moon'));
    $this->requests->push(new Request(attributes: ['_sylament_panel' => 'search']));

    expect($admin)->toBe(['articles' => ["http://localhost/admin/articles/{$moon->id}/edit"]])
        ->and(array_keys($this->provider->getResults('Moon')->getCategories()))->toContain('annotated articles');
});

it('lets a resource that throws take the whole search down', function (): void {
    $this->seed(new Article('Kew'));

    $this->requests->push(new Request(attributes: ['_sylament_panel' => 'search-custom']));

    expect(fn (): GlobalSearchResults => $this->provider->getResults('Kew'))->toThrow(LogicException::class, '[seedlings]');
});
