<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Panels\Selectors;
use Sylament\Tests\Fixtures\Tables\Selectors as TablesSelectors;
use Symfony\Component\DomCrawler\Crawler;

it("wraps the page's live root in the one main landmark the skip link targets, the rest of the shell static around it", function (): void {
    $page = $this->visit('/shell/shelf');

    expect($page->filter('.sy-pa-body ' . Selectors::SKIP_LINK . '.sy-pa-skip-link')->text())->toBe('Skip to content')
        ->and($page->filter(Selectors::MAIN)->count())->toBe(1)
        ->and($page->filter(Selectors::MAIN . Selectors::SKIP_LINK_TARGET . ' ' . Selectors::PAGE)->count())->toBe(1)
        ->and(array_map(fn (string $id): int => $page->filter("[id=\"{$id}\"]")->count(), ['sy-pa-topbar', 'sy-pa-sidebar', 'sy-pa-main']))->toBe([1, 1, 1])
        ->and($page->filter(Selectors::THEME_SWITCHER)->count())->toBe(1)
        ->and($page->filter(TablesSelectors::LIVE_PROPS . ' ' . Selectors::THEME_SWITCHER)->count())->toBe(0)
        ->and($page->filter(TablesSelectors::LIVE_PROPS . ' ' . Selectors::TOPBAR)->count())->toBe(0)
        ->and($page->filter(TablesSelectors::LIVE_PROPS . ' ' . Selectors::SIDEBAR)->count())->toBe(0)
        ->and($page->filter('.sy-pa-body script')->count())->toBe(0);
});

it("wires the drawer to the shell root, opened by the topbar's toggle and closed by the overlay or Escape", function (): void {
    $page = $this->visit('/shell/shelf');
    $root = '.sy-pa-body[data-controller="sylament--panels--sidebar"]';
    $overlay = $root . ' [data-action~="click->sylament--panels--sidebar#close"]';

    expect($page->filter($root . '[data-action~="keydown@window->sylament--panels--sidebar#closeOnEscape"]')->count())->toBe(1)
        ->and($page->filter($root . ' ' . Selectors::TOPBAR . ' button[data-action~="sylament--panels--sidebar#toggle"]')->count())->toBe(1)
        ->and($page->filter($overlay)->count())->toBe(1)
        ->and($page->filter($root . ' ' . Selectors::SIDEBAR)->count())->toBe(1)
        ->and($page->filter($overlay . ' ' . Selectors::SIDEBAR)->count())->toBe(0)
        ->and($page->filter(Selectors::SIDEBAR . ' [data-action~="click->sylament--panels--sidebar#close"]')->count())->toBe(0)
        ->and($page->filter(Selectors::TOPBAR . ', ' . $overlay)->each(fn (Crawler $element): string => $element->matches(Selectors::TOPBAR) ? 'topbar' : 'overlay'))->toBe(['topbar', 'overlay']);
});

it("carries the panel's brand to the logo at the topbar's start and in the sidebar header", function (): void {
    $page = $this->visit('/panel-branded');
    $logos = fn (string $scope): array => $page->filter($scope . ' ' . Selectors::LOGO)->each(fn (Crawler $logo): array => [$logo->attr('src'), $logo->attr('alt')]);
    $expected = [['/brand/acme.svg', 'Acme logo'], ['/brand/acme-dark.svg', 'Acme logo']];

    expect($logos(Selectors::TOPBAR . ' .sy-pa-topbar-start'))->toBe($expected)
        ->and($logos(Selectors::SIDEBAR))->toBe($expected);
});
