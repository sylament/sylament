<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Panels\Selectors;
use Symfony\Component\DomCrawler\Crawler;

it('mounts the narrow-screen menu ahead of the content, and the band once at the edge its position names', function (string $uri, array $order): void {
    $hooks = [
        Selectors::SUB_NAVIGATION_MENU => 'menu',
        Selectors::SUB_NAVIGATION => 'sidebar',
        Selectors::SUB_NAVIGATION_TABS => 'tabs',
        Selectors::PAGE_CONTENT => 'content',
    ];

    $drawn = $this->visit($uri)->filter(implode(', ', array_keys($hooks)))->each(fn (Crawler $node): ?string => array_find($hooks, fn (string $name, string $selector): bool => $node->matches($selector)));

    expect($drawn)->toBe($order);
})->with([
    'start' => ['/navigation-start/banded', ['menu', 'sidebar', 'content']],
    'end' => ['/navigation-band/banded', ['menu', 'content', 'sidebar']],
    'top' => ['/navigation-band/top-banded', ['menu', 'tabs', 'content']],
]);
