<?php

declare(strict_types=1);

use Sylament\Panels\Navigation\NavigationGroup;
use Sylament\Panels\Navigation\NavigationItem;
use Sylament\Support\Icons\IconManager;
use Sylament\Tests\Fixtures\Panels\Selectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

it('keeps a badge colour set before its badge, takes the one given with it, and draws primary once the colour is cleared', function (): void {
    $badge = fn (NavigationItem $item): Crawler => $this->renderTwigComponent('SylamentPanels:SidebarItem', ['item' => $item->url('/inbox')])->crawler()->filter(SupportSelectors::BADGE);

    expect($badge(NavigationItem::make('Inbox')->badgeColor('danger')->badge('12'))->matches(SupportSelectors::color('danger')))->toBeTrue()
        ->and($badge(NavigationItem::make('Inbox')->badgeColor('danger')->badge('12', 'success'))->matches(SupportSelectors::color('success')))->toBeTrue()
        ->and($badge(NavigationItem::make('Inbox')->badgeColor('danger')->badgeColor(null)->badge('12'))->matches(SupportSelectors::color('primary')))->toBeTrue();
});

it('is drawn only while it is neither hidden nor made invisible, hidden winning over visible', function (): void {
    $group = NavigationGroup::make('Drafts')->items([
        NavigationItem::make('Outline')->hidden(false)->visible()->url('/outline'),
        NavigationItem::make('Scrapped')->visible()->hidden()->url('/scrapped'),
        NavigationItem::make('Withdrawn')->hidden()->visible()->url('/withdrawn'),
    ]);

    $labels = $this->renderTwigComponent('SylamentPanels:SidebarGroup', ['group' => $group])->crawler()->filter(Selectors::NAVIGATION_ITEM_LABEL)->each(fn (Crawler $label): string => $label->text());

    expect($labels)->toBe(['Outline']);
});

it('settles whether it is active once, so its icon, its mark and its link agree', function (): void {
    $asked = 0;
    $item = NavigationItem::make('Today')->icon('support:star')->activeIcon('support:heart')->url('/today')
        ->isActiveWhen(function () use (&$asked): bool {
            return ++$asked === 1;
        });

    $entry = $this->renderTwigComponent('SylamentPanels:SidebarItem', ['item' => $item])->crawler()->filter('li');

    expect([$entry->matches(SupportSelectors::ACTIVE), $entry->filter('a')->attr('aria-current'), $entry->filter('svg')->text(), $asked])->toBe([true, 'page', 'Heart', 1]);
});

it('hands its closures the router, the request stack and the icon manager when made, and none when built with new', function (): void {
    $item = NavigationItem::make('Articles')
        ->url(fn (UrlGeneratorInterface $router): string => $router->generate('sylament.admin.resources.articles.index'))
        ->icon(fn (IconManager $icons): string => $icons->resolve('navigation.unregistered') ?? 'support:star')
        ->isActiveWhen(fn (RequestStack $requests): bool => !$requests->getCurrentRequest() instanceof Request);

    $link = $this->renderTwigComponent('SylamentPanels:SidebarItem', ['item' => $item])->crawler()->filter('a');

    expect([$link->attr('href'), $link->attr('aria-current'), $link->filter('svg')->text()])->toBe(['/admin/articles', 'page', 'Star'])
        ->and(fn (): ?string => new NavigationItem()->label('Articles')->url(fn (UrlGeneratorInterface $router): string => '/articles')->getUrl())
        ->toThrow(RuntimeException::class, 'An attempt was made to evaluate a closure for [Sylament\Panels\Navigation\NavigationItem], but [$router] was unresolvable.');
});
