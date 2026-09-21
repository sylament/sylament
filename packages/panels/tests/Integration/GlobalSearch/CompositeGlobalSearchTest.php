<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Panels\GlobalSearch\GlobalSearchResult;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Resources\ResourceRegistry;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Catalogue;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Colophon;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Imprint;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Stamp;
use Sylament\Panels\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Panels\Tests\Fixtures\Resources\ColophonResource;
use Sylament\Panels\Tests\Fixtures\Resources\FlatStampResource;
use Sylament\Panels\Tests\Fixtures\Resources\ImprintResource;
use Sylament\Panels\Tests\Fixtures\Resources\StampResource;
use Sylament\Panels\Twig\Components\GlobalSearch;
use Sylament\Tables\Query\SearchConstraint;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * The searchable fixtures sit on the `key` panel, which is not the default one,
 * and a hit URL is minted with no panel argument — so every flow here has to
 * carry that panel: a resource read in-process through a request standing on
 * the stack, and the live component through the panel id it re-stamps itself.
 *
 * Hit URLs are written as literals; asking the codec for the expected key would
 * pass against any grammar at all.
 */

/**
 * @param class-string<Resource> $class
 */
function compositeSearchResource(string $class): Resource
{
    $registry = test()->getContainer()->get(ResourceRegistry::class);

    expect($registry)->toBeInstanceOf(ResourceRegistry::class);
    assert($registry instanceof ResourceRegistry);

    return $registry->get($class);
}

function compositeSearchEntityManager(): EntityManagerInterface
{
    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);

    expect($entityManager)->toBeInstanceOf(EntityManagerInterface::class);
    assert($entityManager instanceof EntityManagerInterface);

    return $entityManager;
}

/**
 * One catalogue and a record of every searchable composite flavour hanging off
 * it, plus one imprint with no catalogue at all — the row a dotted attribute
 * must leave out.
 */
function compositeSearchSeed(EntityManagerInterface $entityManager): void
{
    $catalogue = new Catalogue()->setTitle('First');

    EntityManagerFactory::seed(
        $entityManager,
        $catalogue,
        new Imprint()->setHouse('www.example.com')->setYear('/blog 2026')->setLabel('Escaped')->setCatalogue($catalogue),
        new Imprint()->setHouse('plain')->setYear('about')->setLabel('Plain'),
        new Stamp()->setCatalogue($catalogue)->setCode('A/B C')->setCaption('Alpha'),
        new Colophon()->setCatalogue($catalogue)->setNote('Colophon note'),
    );
}

/**
 * A resource read outside a kernel request resolves the default panel, so a
 * case reading one in-process stands a request carrying this panel up first.
 */
function compositeSearchPushKeyPanelRequest(): void
{
    $request = new Request();
    $request->attributes->set('_sylament_panel', 'key');

    test()->getContainer()->get(RequestStack::class)->push($request);
}

/**
 * The titles a search answered with, sorted so a case pins the set rather than
 * the database's undefined ordering.
 *
 * @param list<GlobalSearchResult> $results
 *
 * @return list<string>
 */
function compositeSearchTitles(array $results): array
{
    $titles = array_map(fn (GlobalSearchResult $result): string => (string) $result->title, $results);

    sort($titles);

    return $titles;
}

it('builds a hit on a scalar-pair root with the escaped key in its url', function (): void {
    compositeSearchSeed(compositeSearchEntityManager());
    compositeSearchPushKeyPanelRequest();

    $results = compositeSearchResource(ImprintResource::class)->getGlobalSearchResults('escaped');

    expect(compositeSearchTitles($results))->toBe(['Escaped'])
        ->and($results[0]->url)->toEndWith('/key/imprints/www=2Eexample=2Ecom~=2Fblog=202026/edit');
});

it('builds a hit on a derived-identity root', function (): void {
    compositeSearchSeed(compositeSearchEntityManager());
    compositeSearchPushKeyPanelRequest();

    $results = compositeSearchResource(FlatStampResource::class)->getGlobalSearchResults('alpha');

    // The column holds `a/b c`; the hit is keyed off the record's own value.
    expect(compositeSearchTitles($results))->toBe(['Alpha'])
        ->and($results[0]->url)->toEndWith('/key/flat-stamps/1~A=2FB=20C/edit');
});

it('builds a hit on a single derived identity root', function (): void {
    compositeSearchSeed(compositeSearchEntityManager());
    compositeSearchPushKeyPanelRequest();

    $results = compositeSearchResource(ColophonResource::class)->getGlobalSearchResults('colophon');

    // Not composite as Doctrine counts it, and unkeyable all the same today:
    // its one identifier value is a record rather than a scalar.
    expect(compositeSearchTitles($results))->toBe(['Colophon note'])
        ->and($results[0]->url)->toEndWith('/key/colophons/1/edit');
});

it('finds a composite root through an association dot-path that is half its own key', function (): void {
    compositeSearchSeed(compositeSearchEntityManager());
    compositeSearchPushKeyPanelRequest();

    $results = compositeSearchResource(FlatStampResource::class)->getGlobalSearchResults('First');

    expect(compositeSearchTitles($results))->toBe(['Alpha'])
        ->and($results[0]->url)->toEndWith('/key/flat-stamps/1~A=2FB=20C/edit');
});

it('correlates a dotted attribute on a composite root rather than joining it', function (): void {
    compositeSearchSeed(compositeSearchEntityManager());
    compositeSearchPushKeyPanelRequest();

    $resource = compositeSearchResource(ImprintResource::class);
    $query = $resource->getGlobalSearchRecordQuery();
    $predicate = SearchConstraint::forColumn($query, 'catalogue.title', 'First', null);

    // The hop lives inside a correlated `EXISTS`, so the outer statement joins
    // nothing and the result limit truncates whole records — which is why none
    // of the paginator hazards a joined read carries apply here.
    expect($query->getDQLPart('join'))->toBe([])
        ->and((string) $predicate)->toContain('EXISTS')
        ->and($resource->getGlobalSearchResultsLimit())->toBe(50)
        ->and(compositeSearchTitles($resource->getGlobalSearchResults('First')))->toBe(['Escaped']);
});

/**
 * Green before the seam too: a nested resource drops out before any key is
 * minted, so a composite one has to keep dropping out rather than start
 * minting a URL whose parent parameters a hit carries none of.
 */
it('yields no url and no hits for a composite resource nested under a parent', function (): void {
    $entityManager = compositeSearchEntityManager();

    compositeSearchSeed($entityManager);
    compositeSearchPushKeyPanelRequest();

    $stamp = $entityManager->getRepository(Stamp::class)->findOneBy(['caption' => 'Alpha']);
    $resource = compositeSearchResource(StampResource::class);

    expect($stamp)->toBeInstanceOf(Stamp::class);
    assert($stamp instanceof Stamp);

    expect($resource->getGlobalSearchResultUrl($stamp))->toBeNull()
        ->and($resource->getGlobalSearchResults('alpha'))->toBe([]);
});

it('links a hit of the non-default panel the component re-stamps', function (): void {
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();

    compositeSearchSeed(compositeSearchEntityManager());

    $crawler = $this->createLiveComponent(GlobalSearch::class, ['panelId' => 'key'], client: $client)
        ->set('search', 'escaped')
        ->render()
        ->crawler();

    $link = $crawler->filter('.sy-pa-global-search-result-link');

    // A live request carries no panel route default, so without the prop's
    // re-stamp the provider would walk the *default* panel's resources and this
    // href would name a route that does not exist.
    expect($crawler->filter('.sy-pa-global-search-result-group-header')->text())->toBe('imprints')
        ->and($link->count())->toBe(1)
        ->and($link->attr('href'))->toBe('http://localhost/key/imprints/www=2Eexample=2Ecom~=2Fblog=202026/edit')
        ->and($crawler->filter('.sy-pa-global-search-result-heading')->text())->toBe('Escaped');
});
