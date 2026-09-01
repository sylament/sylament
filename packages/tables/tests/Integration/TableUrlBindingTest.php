<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;

/*
 * The query-string half of the browsing state, driven the only way it can be:
 * through a real GET of a real page. UX seeds url-bound props from the *main*
 * request at PostMount, so a component mounted outside an HTTP request — which
 * is what `TestLiveComponent` does — is never seeded at all, and neither the
 * seeding nor the session hook would be exercised by one.
 *
 * The fixture page renders two hosts over the same six-song table: one binding
 * every state prop, one binding none. Both read the same query string; only the
 * first may act on it. The table's select filter carries a real default, so a
 * seeded filter has something to beat.
 */

/**
 * A GET of the fixture page. `test.client` is not a shared service, so a test
 * needing two requests in one session must hand the first client back in —
 * a second `get()` would come with an empty cookie jar.
 *
 * @return array{KernelBrowser, Crawler}
 */
function browseTo(string $query = '', ?KernelBrowser $client = null): array
{
    $client ??= test()->getContainer()->get('test.client');
    $client->disableReboot();

    return [$client, $client->request('GET', '/fixtures/browsing-state' . $query)];
}

/**
 * The props one host published, decoded off its live root.
 *
 * @return array<string, mixed>
 */
function publishedProps(Crawler $crawler, string $selector): array
{
    $props = json_decode($crawler->filter($selector . ' [data-live-props-value]')->attr('data-live-props-value') ?? '', true, flags: JSON_THROW_ON_ERROR);

    expect($props)->toBeArray();
    assert(is_array($props));

    /** @var array<string, mixed> $props */
    return $props;
}

/**
 * The text one host rendered, its own live root and everything under it.
 */
function renderedRowsOf(Crawler $crawler, string $componentName): string
{
    return $crawler->filter(sprintf('[data-live-name-value="%s"]', $componentName))->text();
}

it('seeds page, sort, search and filters from the query string of the page it is on', function (): void {
    [, $crawler] = browseTo('?page=2&sort=title:desc&search=a&filters[plays][value]=200');

    $props = publishedProps($crawler, '#bound-table');

    expect($props)->toMatchArray([
        'tablePage' => 2,
        'tableSort' => 'title:desc',
        'tableSearch' => 'a',
    ])->and($props['tableFilters'])->toBe(['plays' => ['value' => '200'], 'featured' => ['value' => null]]);
});

it('renders the page and order the query string asked for', function (): void {
    [, $crawler] = browseTo('?page=2&sort=title:asc');

    $rows = renderedRowsOf($crawler, 'UrlBoundSongsTable');

    expect($rows)->toContain('Let Down')
        ->and($rows)->toContain('No Surprises')
        ->and($rows)->not->toContain('Airbag');
});

it('floors a blank page rather than paginating from zero', function (): void {
    [, $crawler] = browseTo('?page=&sort=title:asc');

    expect(publishedProps($crawler, '#bound-table')['tablePage'])->toBe(1)
        ->and(renderedRowsOf($crawler, 'UrlBoundSongsTable'))->toContain('Airbag');
});

it('leaves the seeded page alone, because a seeded search fires no update hook', function (): void {
    [, $crawler] = browseTo('?page=2&search=o');

    expect(publishedProps($crawler, '#bound-table'))->toMatchArray([
        'tablePage' => 2,
        'tableSearch' => 'o',
    ]);
});

it('seeds nothing into a host that binds nothing, from the very same URL', function (): void {
    [, $crawler] = browseTo('?page=2&sort=title:desc&search=a&filters[plays][value]=200');

    $props = publishedProps($crawler, '#unbound-table');

    expect($props)->toMatchArray([
        'tablePage' => 1,
        'tableSort' => null,
        'tableSearch' => '',
    ])->and($props['tableFilters'])->toBe(['plays' => ['value' => '100'], 'featured' => ['value' => null]])
        ->and(renderedRowsOf($crawler, 'UnboundSongsTable'))->toContain('Airbag');
});

it('ignores a seeded sort naming a column the table never declared sortable', function (): void {
    [, $crawler] = browseTo('?sort=isFeatured:desc');

    $rows = renderedRowsOf($crawler, 'UrlBoundSongsTable');

    expect($rows)->toContain('No Surprises')
        ->and($rows)->toContain('Let Down')
        ->and(publishedProps($crawler, '#bound-table')['tableSort'])->toBe('isFeatured:desc');
});

it('rebuilds the URL with the mapped names after a live round trip', function (): void {
    [$client, $crawler] = browseTo('?sort=title:desc');

    $root = $crawler->filter('#bound-table [data-live-props-value]');

    $client->request(
        'POST',
        $root->attr('data-live-url-value') ?? '',
        parameters: ['data' => json_encode(['props' => json_decode($root->attr('data-live-props-value') ?? '', true, flags: JSON_THROW_ON_ERROR)], flags: JSON_THROW_ON_ERROR)],
        server: [
            'HTTP_X_LIVE_URL' => '/fixtures/browsing-state?sort=title%3Adesc',
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ],
    );

    $rebuilt = $client->getResponse()->headers->get('X-Live-Url') ?? '';

    expect($rebuilt)->toStartWith('/fixtures/browsing-state?')
        ->and($rebuilt)->toContain('sort=title%3Adesc')
        ->and($rebuilt)->toContain('page=1')
        ->and($rebuilt)->toContain('filters%5Bplays%5D%5Bvalue%5D=100')
        ->and($rebuilt)->not->toContain('tableSort');
});
