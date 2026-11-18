<?php

declare(strict_types=1);

use Sylament\Panels\Twig\Components\GlobalSearch;
use Sylament\Tests\Fixtures\Entity\Article;
use Sylament\Tests\Fixtures\Panels\GlobalSearch\ShelfGlobalSearchProvider;
use Sylament\Tests\Fixtures\Panels\Selectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;

it('renders a labelled search field on a controller of its own inside the live root, and no results panel before a term', function (): void {
    $tester = $this->createLiveComponent(GlobalSearch::class, ['panelId' => 'search']);
    $search = $tester->render()->crawler();
    $input = $search->filter('input' . Selectors::GLOBAL_SEARCH_INPUT);

    expect($search->filter(Selectors::GLOBAL_SEARCH)->attr('data-controller'))->toBe('live')
        ->and($search->filter(Selectors::GLOBAL_SEARCH . ' ' . Selectors::GLOBAL_SEARCH_CONTROLLER))->toHaveCount(1)
        ->and($search->filter('label[for="sy-pa-global-search-input"]')->text())->toBe('Global search')
        ->and([$input->attr('type'), $input->attr('placeholder'), $input->attr('data-sylament--panels--global-search-target'), $input->attr('data-action')])
        ->toBe(['search', 'Search', 'input', 'keydown.down->sylament--panels--global-search#focusFirstResult'])
        ->and($search->filter(SupportSelectors::PREFIX_ICON)->text())->toBe('magnifying-glass-20-solid')
        ->and($search->filter(Selectors::GLOBAL_SEARCH_RESULTS))->toHaveCount(0)
        ->and($tester->set('search', '   ')->render()->crawler()->filter(Selectors::GLOBAL_SEARCH_RESULTS))->toHaveCount(0);
});

it('says a search found nothing in the results panel the controller navigates', function (): void {
    $results = $this->createLiveComponent(GlobalSearch::class, ['panelId' => 'search'])->set('search', 'Moon')->render()->crawler()->filter(Selectors::GLOBAL_SEARCH_RESULTS);

    expect([$results->attr('data-sylament--panels--global-search-target'), $results->attr('data-action'), $results->text()])
        ->toBe(['resultsPanel', 'keydown->sylament--panels--global-search#navigate', 'No search results found.'])
        ->and($results->filter(Selectors::GLOBAL_SEARCH_RESULT_GROUP))->toHaveCount(0);
});

it('lists each category under its heading, every result a link that closes the panel, with its details labelled or not and its visible actions', function (): void {
    $this->seed($landing = new Article('Moon landing'), $harvest = new Article('Harvest moon'));

    $search = $this->createLiveComponent(GlobalSearch::class, ['panelId' => 'search'])->set('search', 'moon')->render()->crawler();
    $group = $search->filter(Selectors::GLOBAL_SEARCH_RESULT_GROUP)->reduce(fn (Crawler $group): bool => $group->filter(Selectors::GLOBAL_SEARCH_RESULT_GROUP_HEADING)->text() === 'annotated articles');

    $results = $group->filter('.sy-pa-global-search-result')->each(fn (Crawler $result): array => [
        $result->filter(Selectors::GLOBAL_SEARCH_RESULT_LINK)->attr('href'),
        $result->filter(Selectors::GLOBAL_SEARCH_RESULT_LINK)->attr('data-action'),
        $result->filter(Selectors::GLOBAL_SEARCH_RESULT_HEADING . ' b')->text(),
        $result->filter('dt')->each(fn (Crawler $label): string => $label->text()),
        $result->filter('dd')->each(fn (Crawler $value): string => $value->text()),
        $result->filter('.sy-pa-global-search-result-actions ' . SupportSelectors::SMALL_LINK)->each(fn (Crawler $action): string => (string) $action->attr('href')),
    ]);
    usort($results, static fn (array $one, array $other): int => strcmp($one[2], $other[2]));

    expect($results)->toBe([
        ["http://localhost/search/annotated-article-searches/{$harvest->id}", 'sylament--panels--global-search#close', 'Harvest moon', [], ['Harvest moon'], ["/open/{$harvest->id}", '/pinned/Pinned']],
        ["http://localhost/search/annotated-article-searches/{$landing->id}", 'sylament--panels--global-search#close', 'Moon landing', ['Title:', 'Words:'], ['Moon landing', '2'], ["/open/{$landing->id}", '/pinned/Pinned']],
    ]);
});

it('searches the term it was given trimmed, so a padded term searched whole still matches', function (): void {
    $this->seed(new Article('Red oak'), new Article('Oak, red'));

    $search = $this->createLiveComponent(GlobalSearch::class, ['panelId' => 'search'])->set('search', '  Red oak ')->render()->crawler();

    expect($this->globalSearchCategories($search)['verbatim articles'] ?? null)->toBe(['Red oak']);
});

it('searches the panel it mounted on in every live request after', function (): void {
    $this->seed(new Article('Moon'));
    $this::getContainer()->get('request_stack')->push(new Request(attributes: ['_sylament_panel' => 'search']));

    $search = $this->createLiveComponent(GlobalSearch::class)->set('search', 'Moon')->render()->crawler();

    expect(array_keys($this->globalSearchCategories($search)))->toContain('annotated articles')
        ->and(array_unique($search->filter(Selectors::GLOBAL_SEARCH_RESULT_LINK)->each(fn (Crawler $link): string => mb_substr((string) $link->attr('href'), 0, 24))))->toBe(['http://localhost/search/']);
});

it('renders no results panel for a provider answering nothing at all', function (): void {
    $tester = $this->createLiveComponent(GlobalSearch::class, ['panelId' => 'search-custom']);

    expect($this->globalSearchCategories($tester->set('search', 'maps')->render()->crawler()))->toBe(['Shelf' => ['Shelf of maps']])
        ->and($tester->set('search', ShelfGlobalSearchProvider::SILENT_TERM)->render()->crawler()->filter(Selectors::GLOBAL_SEARCH_RESULTS))->toHaveCount(0);
});
