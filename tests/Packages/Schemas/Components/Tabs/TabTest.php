<?php

declare(strict_types=1);

use Doctrine\ORM\QueryBuilder;
use Sylament\Schemas\Components\Tabs;
use Sylament\Schemas\Components\Tabs\Tab;
use Sylament\Schemas\Components\Text;
use Sylament\Support\Enums\IconPosition;
use Sylament\Tests\Fixtures\Entity\Article;
use Sylament\Tests\Fixtures\Schemas\Selectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('shows its label on its tab button, keyed by its label under the state path', function (): void {
    expect($this->renderSchemaComponent(Tabs::make('Orders')->tabs([Tab::make('Awaiting Dispatch')])))
        ->toContainElement(Selectors::TAB . '[data-tab-key="awaiting-dispatch::data::tab"]', 'Awaiting Dispatch');
});

it('renders its panel as a tabpanel named by its tab button, active while its tab is', function (): void {
    expect($this->renderSchemaComponent(Tabs::make('Orders')->tabs([
        Tab::make('Open')->components([Text::make('Awaiting dispatch')]),
        Tab::make('Closed')->components([Text::make('Delivered')]),
    ])))
        ->toContainElement(Selectors::TAB . '[id="schema.open::data::tab-tab"][aria-controls="schema.open::data::tab"]', 'Open')
        ->toContainElement(Selectors::TAB_PANEL . '[id="schema.open::data::tab"][aria-labelledby="schema.open::data::tab-tab"][data-tab-key="open::data::tab"].sy-active', 'Awaiting dispatch')
        ->toContainElement(Selectors::TAB_PANEL . '[aria-labelledby="schema.closed::data::tab-tab"][data-tab-key="closed::data::tab"]:not(.sy-active)', 'Delivered');
});

it('keeps its tab button but renders no panel when everything it holds is hidden', function (): void {
    $tabs = $this->renderSchemaComponent(Tabs::make('Orders')->tabs([
        Tab::make('Open')->components([Text::make('Awaiting dispatch')]),
        Tab::make('Returns')->components([Text::make('No returns')->hidden()]),
    ]));

    expect($tabs)->toContainButtons(Selectors::TAB, ['Open', 'Returns'])
        ->toContainElement(Selectors::TAB_PANEL . '[data-tab-key="open::data::tab"]', 'Awaiting dispatch')
        ->not->toContainElement(Selectors::TAB_PANEL . '[data-tab-key="returns::data::tab"]');
});

it('lays out what it holds in the columns of its tabs, unless it sets its own', function (): void {
    expect($this->renderSchemaComponent(Tabs::make('Orders')->columns(2)->tabs([
        Tab::make('Open')->components([Text::make('Awaiting dispatch')]),
        Tab::make('Closed')->columns(3)->components([Text::make('Delivered')]),
    ])))
        ->toContainElement(Selectors::TAB_PANEL . ' [style*="--cols-lg: repeat(2, minmax(0, 1fr))"]', 'Awaiting dispatch')
        ->toContainElement(Selectors::TAB_PANEL . ' [style*="--cols-lg: repeat(3, minmax(0, 1fr))"]', 'Delivered');
});

it('shows its icon beside its label, after it when told', function (): void {
    expect($this->renderSchemaComponent(Tabs::make('Orders')->tabs([
        Tab::make('Open')->icon('heroicons:check'),
        Tab::make('Closed')->icon('heroicons:check')->iconPosition(IconPosition::After),
    ])))->toContainButtons(Selectors::TAB, ['checkOpen', 'Closedcheck']);
});

it('shows its badge icon before its badge, or after it when told', function (): void {
    expect($this->renderSchemaComponent(Tabs::make('Orders')->tabs([
        Tab::make('Open')->badge('3')->badgeIcon('heroicons:check'),
        Tab::make('Closed')->badge('5')->badgeIcon('heroicons:check')->badgeIconPosition(IconPosition::After),
    ])))
        ->toContainElement(Selectors::TAB . ' ' . SupportSelectors::BADGE, 'check3')
        ->toContainElement(Selectors::TAB . ' ' . SupportSelectors::BADGE, '5check');
});

it('scopes the query it is handed in place, keeping no other builder its scope returns, and leaves it as it was without one', function (): void {
    $this->seed(new Article('Published'), new Article('Draft'));
    $articles = fn (): QueryBuilder => $this->entityManager()->createQueryBuilder()->select('a.title')->from(Article::class, 'a')->orderBy('a.title');
    $scoped = $articles();
    $unscoped = $articles();
    $drafts = $articles()->where("a.title = 'Draft'");

    $returned = Tab::make('Published')
        ->query(static function (QueryBuilder $query) use ($drafts): QueryBuilder {
            $query->andWhere("a.title = 'Published'");

            return $drafts;
        })
        ->modifyQuery($scoped);

    expect($returned)->toBe($scoped)
        ->and(array_column($scoped->getQuery()->getArrayResult(), 'title'))->toBe(['Published'])
        ->and(array_column(Tab::make('All')->modifyQuery($unscoped)->getQuery()->getArrayResult(), 'title'))->toBe(['Draft', 'Published']);
});
