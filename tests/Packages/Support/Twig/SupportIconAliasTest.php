<?php

declare(strict_types=1);

use Sylament\Support\Icons\IconManager;
use Sylament\Support\Pagination\Paginator;
use Sylament\Support\Twig\SupportIconAlias;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Symfony\Component\DomCrawler\Crawler;

it('swaps the icon of its own control for the one an application registers under the alias', function (string $alias, string $component, array $props, ?string $content, string $icon): void {
    $this::getContainer()->get(IconManager::class)->register([$alias => 'support:heart']);

    $rendered = $this->renderTwigComponent($component, $props, content: $content)->crawler();

    expect($rendered)->toContainElement($icon, 'Heart')
        ->and(array_count_values($rendered->filter('svg')->each(fn (Crawler $svg): string => $svg->text()))['Heart'] ?? 0)->toBe(1);
})->with([
    'the section collapse button' => [SupportIconAlias::SECTION_COLLAPSE_BUTTON, 'SylamentSupport:Section', ['heading' => 'Details', 'collapsible' => true], null, SupportSelectors::SECTION_COLLAPSE_BUTTON . ' svg'],
    'the modal close button' => [SupportIconAlias::MODAL_CLOSE_BUTTON, 'SylamentSupport:Modal', ['heading' => 'Delete order'], null, SupportSelectors::MODAL_CLOSE_BUTTON . ' svg'],
    'the first page button' => [SupportIconAlias::PAGINATION_FIRST_BUTTON, 'SylamentSupport:Pagination', ['paginator' => new Paginator([], 30, 2, 10), 'extremeLinks' => true], null, SupportSelectors::FIRST_PAGE_BUTTON . ' svg'],
    'the previous page button' => [SupportIconAlias::PAGINATION_PREVIOUS_BUTTON, 'SylamentSupport:Pagination', ['paginator' => new Paginator([], 30, 2, 10), 'extremeLinks' => true], null, SupportSelectors::PREVIOUS_PAGE_BUTTON . ' svg'],
    'the next page button' => [SupportIconAlias::PAGINATION_NEXT_BUTTON, 'SylamentSupport:Pagination', ['paginator' => new Paginator([], 30, 2, 10), 'extremeLinks' => true], null, SupportSelectors::NEXT_PAGE_BUTTON . ' svg'],
    'the last page button' => [SupportIconAlias::PAGINATION_LAST_BUTTON, 'SylamentSupport:Pagination', ['paginator' => new Paginator([], 30, 2, 10), 'extremeLinks' => true], null, SupportSelectors::LAST_PAGE_BUTTON . ' svg'],
    'the badge delete button' => [SupportIconAlias::BADGE_DELETE_BUTTON, 'SylamentSupport:Badge', ['deletable' => true], 'Urgent', SupportSelectors::BADGE_DELETE_BUTTON . ' svg'],
    'the breadcrumb separator' => [SupportIconAlias::BREADCRUMBS_SEPARATOR, 'SylamentSupport:Breadcrumbs', ['breadcrumbs' => ['/orders' => 'Orders', '/orders/1' => 'Order 1']], null, SupportSelectors::BREADCRUMB_SEPARATOR],
    'the right-to-left breadcrumb separator' => [SupportIconAlias::BREADCRUMBS_SEPARATOR_RTL, 'SylamentSupport:Breadcrumbs', ['breadcrumbs' => ['/orders' => 'Orders', '/orders/1' => 'Order 1']], null, SupportSelectors::RTL_BREADCRUMB_SEPARATOR],
]);
