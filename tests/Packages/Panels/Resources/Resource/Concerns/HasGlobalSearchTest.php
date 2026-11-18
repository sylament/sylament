<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Panels\GlobalSearch\GlobalSearchResult;
use Sylament\Panels\Resources\ResourceRegistry;
use Sylament\Tests\Fixtures\Entity\Article;
use Sylament\Tests\Fixtures\Entity\Garden;
use Sylament\Tests\Fixtures\Entity\Invoice;
use Sylament\Tests\Fixtures\Entity\InvoiceLine;
use Sylament\Tests\Fixtures\Entity\OrderLine;
use Sylament\Tests\Fixtures\Entity\Seedling;
use Sylament\Tests\Fixtures\Panels\Resources\AnnotatedArticleSearchResource;
use Sylament\Tests\Fixtures\Panels\Resources\ArticleResource;
use Sylament\Tests\Fixtures\Panels\Resources\CuratedArticleSearchResource;
use Sylament\Tests\Fixtures\Panels\Resources\FetchJoinedGardenSearchResource;
use Sylament\Tests\Fixtures\Panels\Resources\InvoiceLineResource;
use Sylament\Tests\Fixtures\Panels\Resources\OptedOutArticleSearchResource;
use Sylament\Tests\Fixtures\Panels\Resources\OrderLineResource;
use Sylament\Tests\Fixtures\Panels\Resources\SeedlingGardenSearchResource;
use Sylament\Tests\Fixtures\Panels\Resources\UnroutedArticleSearchResource;
use Sylament\Tests\Fixtures\Panels\Resources\UntitledArticleSearchResource;
use Sylament\Tests\Fixtures\Panels\Resources\VerbatimArticleSearchResource;
use Sylament\Tests\Fixtures\Panels\Selectors;
use Sylament\Tests\Infrastructure\Doctrine\TestPlatform;
use Symfony\Component\HttpFoundation\Request;
use Twig\Markup;

beforeEach(function (): void {
    $this::getContainer()->get('request_stack')->push(new Request(attributes: ['_sylament_panel' => 'search']));

    $this->resources = $this::getContainer()->get(ResourceRegistry::class);
});

it('searches a resource by its record title attribute while it stays searchable, and none naming no attribute or opting out', function (): void {
    $this->seed(new Article('Moon'));

    expect($this->resources->get(ArticleResource::class)->getGloballySearchableAttributes())->toBe(['title'])
        ->and($this->resources->get(ArticleResource::class)->canGloballySearch())->toBeTrue()
        ->and($this->globalSearchTitles($this->resources->get(ArticleResource::class)->getGlobalSearchResults('Moon')))->toBe(['Moon'])
        ->and($this->resources->get(UntitledArticleSearchResource::class)->getGloballySearchableAttributes())->toBe([])
        ->and($this->resources->get(UntitledArticleSearchResource::class)->canGloballySearch())->toBeFalse()
        ->and($this->resources->get(OptedOutArticleSearchResource::class)->getGloballySearchableAttributes())->toBe(['title'])
        ->and($this->resources->get(OptedOutArticleSearchResource::class)->canGloballySearch())->toBeFalse();
});

it('answers a result per matching record, titled by the record and linked to its edit page, with no details or actions', function (): void {
    $this->seed($landing = new Article('Moon landing'), $harvest = new Article('Harvest moon'), new Article('Sunrise'));

    $results = array_map(fn (GlobalSearchResult $result): array => [$result->title, $result->url, $result->details, $result->actions], $this->resources->get(ArticleResource::class)->getGlobalSearchResults('moon'));
    usort($results, static fn (array $one, array $other): int => strcmp((string) $one[0], (string) $other[0]));

    expect($results)->toBe([
        ['Harvest moon', "http://localhost/search/articles/{$harvest->id}/edit", [], []],
        ['Moon landing', "http://localhost/search/articles/{$landing->id}/edit", [], []],
    ]);
});

it('stops at fifty results, or at a lower limit of its own, in the order its own query reads them', function (): void {
    $this->seed(...array_map(fn (int $number): Article => new Article(sprintf('Moon %02d', $number)), range(51, 1)));

    expect($this->resources->get(ArticleResource::class)->getGlobalSearchResults('Moon'))->toHaveCount(50)
        ->and($this->globalSearchTitles($this->resources->get(CuratedArticleSearchResource::class)->getGlobalSearchResults('Moon')))->toBe(['Moon 01', 'Moon 02']);
});

it('searches only what the record query and the search hook leave', function (): void {
    $this->seed(new Article('Moon'), new Article('Archived Moon'), new Article('Secret Moon'));

    expect($this->globalSearchTitles($this->resources->get(CuratedArticleSearchResource::class)->getGlobalSearchResults('Moon')))->toBe(['Moon'])
        ->and($this->globalSearchTitles($this->resources->get(ArticleResource::class)->getGlobalSearchResults('Moon')))->toEqualCanonicalizing(['Moon', 'Archived Moon', 'Secret Moon']);
});

it('spends its limit before it drops a hit with no URL, so fewer results come back', function (): void {
    $this->seed(new Article('Alpha draft'), new Article('Beta'), new Article('Gamma'));

    $results = $this->resources->get(CuratedArticleSearchResource::class)->getGlobalSearchResults('a');

    expect(array_map(fn (GlobalSearchResult $result): array => [$result->title, $result->url], $results))->toBe([['Beta', 'http://localhost/search/curated-article-searches']]);
});

it('links a hit to the view page, else the edit page, else the index, and drops it with no page at all', function (): void {
    $this->seed($moon = new Article('Moon'));

    $url = fn (string $class): array => array_map(fn (GlobalSearchResult $result): string => $result->url, $this->resources->get($class)->getGlobalSearchResults('Moon'));

    expect($url(AnnotatedArticleSearchResource::class))->toBe(["http://localhost/search/annotated-article-searches/{$moon->id}"])
        ->and($url(ArticleResource::class))->toBe(["http://localhost/search/articles/{$moon->id}/edit"])
        ->and($url(CuratedArticleSearchResource::class))->toBe(['http://localhost/search/curated-article-searches'])
        ->and($url(UnroutedArticleSearchResource::class))->toBe([]);
});

it('opens a composite-keyed hit on the edit page of its own record', function (string $resource, object $line, object $sibling): void {
    $this::getContainer()->get('request_stack')->push(new Request(attributes: ['_sylament_panel' => 'admin']));
    $this->seed($sibling, $line);

    $results = $this->resources->get($resource)->getGlobalSearchResults('First');

    expect($results)->toHaveCount(1)
        ->and($this->visit((string) parse_url($results[0]->url, PHP_URL_PATH))->filter(Selectors::field('note'))->attr('value'))->toBe('First');
})->with([
    'scalar fields' => fn (): array => [OrderLineResource::class, new OrderLine(order: 7, line: 2, note: 'First'), new OrderLine(order: 2, line: 7, note: 'Second')],
    'an association plus a scalar' => fn (): array => [InvoiceLineResource::class, new InvoiceLine(new Invoice(7), line: 2, note: 'First'), new InvoiceLine(new Invoice(2), line: 7, note: 'Second')],
]);

it("titles, details and arms a hit through the resource's hooks, binding each action to the hit unless it has a record of its own", function (): void {
    $this->seed(new Article('Moon landing'), new Article('Harvest moon'));

    $results = $this->resources->get(AnnotatedArticleSearchResource::class)->getGlobalSearchResults('moon');
    usort($results, fn (GlobalSearchResult $a, GlobalSearchResult $b): int => strcmp((string) $b->title, (string) $a->title));

    expect(array_map(fn (GlobalSearchResult $result): array => [
        $result->title instanceof Markup ? (string) $result->title : null,
        $result->details,
        array_map(fn (Action $action): string => $action->getRecord()->title, $result->actions),
    ], $results))->toBe([
        ['<b>Moon landing</b>', ['Title' => 'Moon landing', 'Words' => '2'], ['Moon landing', 'Pinned', 'Moon landing']],
        ['<b>Harvest moon</b>', ['Harvest moon'], ['Harvest moon', 'Pinned', 'Harvest moon']],
    ]);
});

it('needs every word of the term to match one searchable attribute or another, a related one included', function (): void {
    $kew = new Garden('Kew');
    $wisley = new Garden('Wisley');
    $kewOak = new Seedling('Oak');
    $kewOak->garden = $kew;

    $wisleyOak = new Seedling('Oak');
    $wisleyOak->garden = $wisley;

    $ash = new Seedling('Ash');
    $ash->garden = $kew;
    $this->seed($kew, $wisley, $kewOak, $wisleyOak, $ash);

    $urls = fn (string $search): array => array_map(fn (GlobalSearchResult $result): string => $result->url, $this->resources->get(SeedlingGardenSearchResource::class)->getGlobalSearchResults($search));

    expect($urls('oak KEW'))->toBe(["/seedlings/{$kewOak->id}"])
        ->and($urls('oak'))->toEqualCanonicalizing(["/seedlings/{$kewOak->id}", "/seedlings/{$wisleyOak->id}"])
        ->and($urls('oak ash'))->toBe([]);
});

it('searches the term whole with splitting off, and word by word otherwise', function (): void {
    $this->seed(new Article('Red oak'), new Article('Oak, red'));

    expect($this->globalSearchTitles($this->resources->get(VerbatimArticleSearchResource::class)->getGlobalSearchResults('Red oak')))->toBe(['Red oak'])
        ->and($this->globalSearchTitles($this->resources->get(ArticleResource::class)->getGlobalSearchResults('Red oak')))->toEqualCanonicalizing(['Red oak', 'Oak, red']);
});

it("folds case where forced on, keeps it where forced off, and leaves it to the platform's own matching otherwise", function (): void {
    $this->seed(new Article('MOON'), new Seedling('OAK'));

    $search = function (string $class, string $term): array {
        $this->seed();
        $titles = $this->globalSearchTitles($this->resources->get($class)->getGlobalSearchResults($term));

        return [$titles, str_contains($this->statementsRun()[0], 'LOWER(')];
    };

    expect($search(SeedlingGardenSearchResource::class, 'oak'))->toBe([['OAK'], true])
        ->and($search(VerbatimArticleSearchResource::class, 'moon'))->toBe(match (TestPlatform::current()) {
            TestPlatform::PostgreSql => [[], false],
            TestPlatform::Sqlite, TestPlatform::MySql => [['MOON'], false],
        })
        ->and($search(ArticleResource::class, 'moon'))->toBe(match (TestPlatform::current()) {
            TestPlatform::PostgreSql => [['MOON'], true],
            TestPlatform::Sqlite, TestPlatform::MySql => [['MOON'], false],
        });
});

it('refuses a dotted attribute whose head its query fetch-joins as a collection, before the first search runs a statement', function (): void {
    $this->seed(new Garden('Kew'));

    expect(fn (): array => $this->resources->get(FetchJoinedGardenSearchResource::class)->getGlobalSearchResults('Kew'))
        ->toThrow(LogicException::class, '[seedlings]')
        ->and($this->statementsRun())->toBe([]);
});
