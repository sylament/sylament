<?php

declare(strict_types=1);

use Sylament\Panels\Navigation\NavigationGroup;
use Sylament\Panels\Navigation\NavigationItem;
use Sylament\Tests\Fixtures\Panels\Selectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Symfony\Component\DomCrawler\Crawler;

describe('label', function (): void {
    it("makes a collapsible group's label a disclosure of its item list, with the group's icon and the up chevron", function (): void {
        $page = $this->visit('/shell/shelf');
        $toggle = $page->filter(Selectors::navigationGroup('Library') . ' ' . Selectors::NAVIGATION_GROUP_TOGGLE);

        expect($toggle->text())->toContain('Library')
            ->and([$toggle->attr('type'), $toggle->attr('aria-expanded'), $toggle->attr('aria-controls'), $toggle->attr('data-action')])
            ->toBe(['button', 'true', Selectors::navigationGroupItemsId(1), 'sylament--panels--sidebar#toggleGroup'])
            ->and($toggle->filter('svg')->each(fn (Crawler $icon): string => $icon->text()))->toBe(['Star, large', 'chevron-up-20-solid']);
    });

    it("renders a static group's label as a plain labelled element, with no disclosure to operate", function (): void {
        $page = $this->visit('/shell/shelf');
        $label = $page->filter(Selectors::navigationGroup('Help') . ' ' . Selectors::NAVIGATION_GROUP_LABEL);

        expect($label->nodeName())->toBe('div')
            ->and($label->text())->toBe('Help')
            ->and([$label->attr('type'), $label->attr('aria-expanded'), $label->attr('aria-controls'), $label->attr('data-action')])->toBe([null, null, null, null])
            ->and($label->filter('svg')->count())->toBe(0);
    });

    it('renders a collapsed group collapsed, its disclosure saying so', function (): void {
        $page = $this->visit('/shell/shelf');

        expect($page->filter(Selectors::navigationGroup('Archive') . SupportSelectors::COLLAPSED . ' ' . Selectors::NAVIGATION_GROUP_TOGGLE)->attr('aria-expanded'))->toBe('false')
            ->and($page->filter(Selectors::navigationGroup('Library'))->count())->toBe(1)
            ->and($page->filter(Selectors::navigationGroup('Library') . SupportSelectors::COLLAPSED)->count())->toBe(0);
    });

    it('renders no label for a group without one, only its items', function (): void {
        $group = $this->visit('/shell/shelf')->filter(Selectors::NAVIGATION . ' ' . Selectors::UNGROUPED_NAVIGATION_GROUP);

        expect($group->filter(Selectors::NAVIGATION_ITEM_LABEL)->each(fn (Crawler $label): string => $label->text()))->toBe(['Slugged Settings Page', 'Inbox'])
            ->and($group->filter(Selectors::NAVIGATION_GROUP_LABEL)->count())->toBe(0);
    });
});

it('marks the group holding the page shown as active', function (): void {
    $active = fn (string $uri): array => $this->visit($uri)->filter(Selectors::NAVIGATION . ' ' . Selectors::NAVIGATION_GROUP)->each(fn (Crawler $group): array => [$group->attr('data-group-label'), $group->matches(SupportSelectors::ACTIVE)]);

    expect($active('/shell/shelf'))->toBe([[null, false], ['Library', true], ['Archive', false], ['Help', false]])
        ->and($active('/shell/settings'))->toBe([[null, true], ['Library', false], ['Archive', false], ['Help', false]]);
});

it('lists only its visible items', function (): void {
    $group = NavigationGroup::make('Drafts')->items([
        NavigationItem::make('Outline')->url('/outline'),
        NavigationItem::make('Scrapped')->visible(false)->url('/scrapped'),
    ]);

    $labels = $this->renderTwigComponent('SylamentPanels:SidebarGroup', ['group' => $group])->crawler()->filter(Selectors::NAVIGATION_ITEM_LABEL)->each(fn (Crawler $label): string => $label->text());

    expect($labels)->toBe(['Outline']);
});
