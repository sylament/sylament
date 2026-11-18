<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Tabs;
use Sylament\Schemas\Components\Tabs\Tab;
use Sylament\Schemas\Components\Wizard;
use Sylament\Schemas\Components\Wizard\Step;
use Sylament\Schemas\Twig\SchemaIconAlias;
use Sylament\Support\Icons\IconManager;
use Sylament\Tests\Fixtures\Schemas\Selectors;
use Symfony\Component\DomCrawler\Crawler;

it('swaps the icon of its own control for the one an application registers under the alias', function (string $alias, Closure $component, string $icon): void {
    $this::getContainer()->get(IconManager::class)->register([$alias => 'support:heart']);

    $rendered = $this->renderSchemaComponent($component());

    expect($rendered)->toContainElement($icon, 'Heart')
        ->and(array_count_values($rendered->filter('svg')->each(fn (Crawler $svg): string => $svg->text()))['Heart'] ?? 0)->toBe(1);
})->with([
    'the tab dropdown trigger' => [SchemaIconAlias::COMPONENTS_TABS_DROPDOWN_TRIGGER_BUTTON, fn (): Component => Tabs::make('Order')->scrollable(false)->tabs([Tab::make('Details')]), Selectors::tabsTarget('triggerTab') . ' svg'],
    'the more tabs button' => [SchemaIconAlias::COMPONENTS_TABS_MORE_TABS_BUTTON, fn (): Component => Tabs::make('Order')->scrollable(false)->tabs([Tab::make('Details')]), Selectors::tabsTarget('moreButton') . ' svg'],
    'the completed wizard step' => [SchemaIconAlias::COMPONENTS_WIZARD_COMPLETED_STEP, fn (): Component => Wizard::make([Step::make('Details')]), Selectors::WIZARD_HEADER_STEP . ' svg'],
]);
