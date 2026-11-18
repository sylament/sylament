<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Symfony\Component\DomCrawler\Crawler;

it('lists its crumbs in a navigation landmark named Breadcrumbs, or by the name the caller gives', function (): void {
    $crumbs = ['/orders' => 'Orders', '/orders/12' => 'Order 12'];
    $renamed = $this->renderTwigComponent('SylamentSupport:Breadcrumbs', ['breadcrumbs' => $crumbs, 'aria-label' => 'You are here'])->crawler();

    expect($this->renderTwigComponent('SylamentSupport:Breadcrumbs', ['breadcrumbs' => $crumbs])->crawler())
        ->toContainElement(SupportSelectors::BREADCRUMBS . ' ol li a[href="/orders"]', 'Orders')
        ->toContainElement(SupportSelectors::BREADCRUMBS . ' ol li a[href="/orders/12"]', 'Order 12')
        ->and($renamed)->toContainElement('nav[aria-label="You are here"] ol li a[href="/orders"]', 'Orders')
        ->and($renamed->filter(SupportSelectors::BREADCRUMBS))->toHaveCount(0);
});

it('links a crumb keyed by its URL and labels one keyed by a number, a quoted number included', function (): void {
    $nav = $this->renderTwigComponent('SylamentSupport:Breadcrumbs', ['breadcrumbs' => ['/orders' => 'Orders', '12' => 'Order 12', 'Edit']])->crawler();

    expect($nav)->toContainElement('a[href="/orders"]', 'Orders')
        ->toContainElement(SupportSelectors::UNLINKED_BREADCRUMB, 'Order 12')
        ->toContainElement(SupportSelectors::UNLINKED_BREADCRUMB, 'Edit')
        ->and($nav->filter('a')->each(fn (Crawler $link): string => $link->text()))->toBe(['Orders']);
});

it('separates each crumb from the one before it by a chevron for each reading direction', function (): void {
    $nav = $this->renderTwigComponent('SylamentSupport:Breadcrumbs', ['breadcrumbs' => ['/orders' => 'Orders', '/orders/12' => 'Order 12', 'Edit']])->crawler();

    expect($nav->filter('li')->each(fn (Crawler $crumb): array => [
        $crumb->filter('.sy-breadcrumbs-item-label')->text(),
        $crumb->filter(SupportSelectors::BREADCRUMB_SEPARATOR)->each(fn (Crawler $icon): string => $icon->text()),
        $crumb->filter(SupportSelectors::RTL_BREADCRUMB_SEPARATOR)->each(fn (Crawler $icon): string => $icon->text()),
    ]))->toBe([
        ['Orders', [], []],
        ['Order 12', ['chevron-right-20-solid'], ['chevron-left-20-solid']],
        ['Edit', ['chevron-right-20-solid'], ['chevron-left-20-solid']],
    ]);
});

it('marks its last crumb as the current page, a link or a label alike', function (): void {
    $endingInLink = $this->renderTwigComponent('SylamentSupport:Breadcrumbs', ['breadcrumbs' => ['/orders' => 'Orders', '/orders/12' => 'Order 12']])->crawler();
    $endingInLabel = $this->renderTwigComponent('SylamentSupport:Breadcrumbs', ['breadcrumbs' => ['/orders' => 'Orders', 'Order 12']])->crawler();

    expect($endingInLink)->toContainElement('a[href="/orders/12"][aria-current="page"]', 'Order 12')
        ->and($endingInLink->filter('[aria-current]'))->toHaveCount(1)
        ->and($endingInLabel)->toContainElement(SupportSelectors::UNLINKED_BREADCRUMB . '[aria-current="page"]', 'Order 12')
        ->and($endingInLabel->filter('[aria-current]'))->toHaveCount(1);
});
