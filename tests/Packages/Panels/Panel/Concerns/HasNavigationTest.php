<?php

declare(strict_types=1);

use Sylament\Panels\Navigation\NavigationGroup;
use Sylament\Panels\Navigation\NavigationItem;
use Sylament\Panels\Panel;
use Sylament\Panels\PanelRegistry;
use Sylament\Tests\Fixtures\Panels\Pages\SluggedSettingsPage;
use Sylament\Tests\Fixtures\Panels\Resources\ArticleResource;
use Sylament\Tests\Fixtures\Panels\Selectors;

it("draws the panel's pages, resources and items sorted as one list, bucketed into the groups registered by key or label, then the unregistered ones", function (): void {
    $page = $this->visit('/panel-branded');
    $labels = fn (string $scope): array => $page->filter($scope . ' ' . Selectors::NAVIGATION_ITEM_LABEL)->each(fn ($label): string => $label->text());

    expect($labels(Selectors::NAVIGATION))->toBe(['Pinned', 'Overview', 'Articles', 'Year End Report Page', 'Quarterly', 'Catalogue', 'Glossary'])
        ->and($page->filter(Selectors::NAVIGATION . ' [data-group-label]')->each(fn ($group): ?string => $group->attr('data-group-label')))->toBe(['Reports', 'Library', 'Misc'])
        ->and($labels(Selectors::navigationGroup('Library')))->toBe(['Catalogue']);
});

it('assembles the same navigation on every call, leaving the registered groups as they were', function (): void {
    $panel = $this::getContainer()->get(PanelRegistry::class)->get('panel-branded');
    $labels = static fn (array $groups): array => array_map(
        static fn (NavigationGroup $group): array => [$group->getLabel(), array_map(static fn (NavigationItem $item): string => $item->getLabel(), $group->getItems())],
        $groups,
    );

    $first = $labels($panel?->getNavigation() ?? []);

    expect($labels($panel?->getNavigation() ?? []))->toBe($first)
        ->and($first[2])->toBe(['Library', ['Catalogue']])
        ->and($panel?->getNavigationGroups()['library']->getItems())->toBe([]);
});

it('refuses resource navigation on a panel built with new, which assembles the rest', function (): void {
    $pages = new Panel()->id('panel-new')->pages([SluggedSettingsPage::class]);
    $resources = new Panel()->id('panel-new')->resources([ArticleResource::class]);

    expect(array_map(static fn (NavigationItem $item): string => $item->getLabel(), $pages->getNavigation()[0]->getItems()))->toBe(['Slugged Settings Page'])
        ->and(fn (): array => $resources->getNavigation())->toThrow(LogicException::class, 'Resource navigation needs the container-built panel — build panels through a PanelProvider.');
});
