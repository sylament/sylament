<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Tabs;
use Sylament\Schemas\Components\Tabs\Tab;
use Sylament\Schemas\Components\Text;
use Sylament\Schemas\Schema;
use Sylament\Support\Colors\Color;
use Sylament\Support\Enums\IconPosition;
use Sylament\Tests\Fixtures\Schemas\PropertyDrivenTabsHost;
use Sylament\Tests\Fixtures\Schemas\Selectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Twig\Markup;

describe('client mode', function (): void {
    it('names its tablist after its label and shows each visible tab with its panel, leaving a hidden one out of both', function (): void {
        $tabs = $this->renderSchemaComponent(Tabs::make('Orders')->tabs([
            Tab::make('Open')->components([Text::make('Awaiting dispatch')]),
            Tab::make('Drafts')->hidden()->components([Text::make('Not yet placed')]),
            Tab::make('Closed')->components([Text::make('Delivered')]),
        ]));

        expect($tabs)->toContainButtons('nav[role="tablist"][aria-label="Orders"] ' . Selectors::TAB, ['Open', 'Closed'])
            ->toContainElement(Selectors::TAB_PANEL, 'Awaiting dispatch')
            ->toContainElement(Selectors::TAB_PANEL, 'Delivered')
            ->and($tabs->filter(Selectors::TAB_PANEL)->count())->toBe(2);
    });

    it('is keyed by its label under the state path, and leaves the key of what its tabs hold as it was', function (): void {
        $inner = Text::make('Awaiting dispatch')->key('inner');
        $schema = Schema::make()->key('schema')->components([
            Tabs::make('Open Orders')->tabs([Tab::make('Open')->components([$inner])]),
        ]);

        expect($this->renderSchemaComponent(Tabs::make('Open Orders')->tabs([Tab::make('Open')])))
            ->toContainElement(Selectors::TABS . '[id="schema.open-orders::data::tabs"]')
            ->and($schema->getComponent('inner'))->toBe($inner);
    });

    it('opens on its first visible tab, or on the visible position activeTab() names', function (): void {
        $tabs = static fn (): array => [
            Tab::make('Drafts')->hidden(),
            Tab::make('Open')->components([Text::make('Awaiting dispatch')]),
            Tab::make('Closed')->components([Text::make('Delivered')]),
        ];

        expect($this->renderSchemaComponent(Tabs::make('Orders')->tabs($tabs())))
            ->toContainElement(Selectors::SELECTED_TAB, 'Open')
            ->toContainElement(Selectors::TAB . '[aria-selected="false"]', 'Closed')
            ->toContainElement(Selectors::TAB_PANEL . SupportSelectors::ACTIVE, 'Awaiting dispatch')
            ->toContainElement(Selectors::TAB_PANEL . ':not(' . SupportSelectors::ACTIVE . ')', 'Delivered')
            ->and($this->renderSchemaComponent(Tabs::make('Orders')->activeTab(2)->tabs($tabs())))
            ->toContainElement(Selectors::TABS . '[data-sylament--schemas--tabs-active-tab-value="2"]')
            ->toContainElement(Selectors::SELECTED_TAB, 'Closed')
            ->toContainElement(Selectors::TAB . '[aria-selected="false"]', 'Open')
            ->toContainElement(Selectors::TAB_PANEL . SupportSelectors::ACTIVE, 'Delivered');
    });

    it('wires the tabs controller to its nav, its tabs and its panels', function (): void {
        expect($this->renderSchemaComponent(Tabs::make('Orders')->tabs([Tab::make('Open')->components([Text::make('Awaiting dispatch')])])))
            ->toContainElement(Selectors::TABS . SupportSelectors::CONTAINED . '[data-controller="sylament--schemas--tabs"][data-sylament--schemas--tabs-schema-key-value="schema"][data-action~="reset-schema-component-state@window->sylament--schemas--tabs#resetState"]')
            ->toContainElement('nav[role="tablist"]' . Selectors::tabsTarget('nav'))
            ->toContainElement(Selectors::TAB . Selectors::tabsTarget('tab') . '[data-action="click->sylament--schemas--tabs#select"][data-tab-key="open::data::tab"][aria-controls="schema.open::data::tab"]', 'Open')
            ->toContainElement(Selectors::TAB_PANEL . Selectors::tabsTarget('panel') . '[data-action="expand->sylament--schemas--tabs#expand"]', 'Awaiting dispatch');
    });

    it('points a tab at no panel when the tab has no id', function (): void {
        expect($this->renderSchemaComponent(Tabs::make('Orders')->tabs([Tab::make('Open')->id('')->components([Text::make('Awaiting dispatch')])])))
            ->toContainElement(Selectors::TAB . ':not([id]):not([aria-controls])', 'Open');
    });

    it("lets a tab's extra attributes override its ARIA props but never its controller wiring", function (): void {
        expect($this->renderSchemaComponent(Tabs::make('Orders')->tabs([
            Tab::make('Open')->extraAttributes(['title' => 'Awaiting dispatch', 'aria-selected' => 'mixed', 'data-action' => 'click->tracker#log']),
        ])))->toContainElement(Selectors::TAB . '[title="Awaiting dispatch"][aria-selected="mixed"][data-action="click->sylament--schemas--tabs#select"]', 'Open');
    });

    it('stacks its tab strip vertically when told', function (): void {
        expect($this->renderSchemaComponent(Tabs::make('Orders')->vertical()->tabs([Tab::make('Open')])))
            ->toContainElement(Selectors::TABS . SupportSelectors::VERTICAL . ' nav' . SupportSelectors::VERTICAL . '[role="tablist"]', 'Open');
    });

    it('folds its tabs into an overflow dropdown when not scrollable, and keeps them all in the strip otherwise', function (): void {
        $tabs = static fn (): array => [Tab::make('Open')->badge('3'), Tab::make('Closed')];

        expect($this->renderSchemaComponent(Tabs::make('Orders')->scrollable(false)->activeTab(2)->tabs($tabs())))
            ->toContainElement(Selectors::TABS . '[data-sylament--schemas--tabs-scrollable-value="false"][data-action~="resize@window->sylament--schemas--tabs#resize"]')
            ->toContainElement(Selectors::tabsTarget('triggerTab'), 'chevron-downOpen 3')
            ->toContainElement(Selectors::tabsTarget('triggerTab') . '[aria-current="true"]', 'chevron-downClosed')
            ->toContainElement(Selectors::tabsTarget('dropdown') . ' ' . Selectors::tabsTarget('moreButton'), 'ellipsis-horizontal')
            ->toContainButtons(Selectors::tabsTarget('moreButton'), ['More tabs'])
            ->toContainButtons(Selectors::tabsTarget('triggerTab'), ['More tabs: Open', 'More tabs: Closed'])
            ->toContainElement(Selectors::tabsTarget('dropdownItem'), 'Open 3')
            ->toContainElement(Selectors::tabsTarget('dropdownItem') . SupportSelectors::SELECTED, 'Closed')
            ->and($this->renderSchemaComponent(Tabs::make('Orders')->tabs($tabs())))
            ->toContainElement(Selectors::TABS . ':not([data-sylament--schemas--tabs-scrollable-value])')
            ->toContainButtons(Selectors::TAB, ['Open 3', 'Closed'])
            ->not->toContainElement(Selectors::tabsTarget('dropdown'));
    });

    it("names an overflow trigger after the text of its tab's label", function (): void {
        expect($this->renderSchemaComponent(Tabs::make('Orders')->scrollable(false)->tabs([Tab::make('Open')->label(new Markup('<em>Open</em> &amp; now', 'UTF-8'))])))
            ->toContainButtons(Selectors::tabsTarget('triggerTab'), ['More tabs: Open & now']);
    });

    it('hides itself until its controller has restored a persisted tab, and only then', function (): void {
        expect($this->renderSchemaComponent(Tabs::make('Orders')->persistTab()->tabs([Tab::make('Open')])))
            ->toContainElement(Selectors::TABS . SupportSelectors::CLOAK, 'Open')
            ->and($this->renderSchemaComponent(Tabs::make()->persistTab()->tabs([Tab::make('Open')])))
            ->toContainElement(Selectors::TABS . ':not(' . SupportSelectors::CLOAK . ')', 'Open')
            ->and($this->renderSchemaComponent(Tabs::make('Orders')->tabs([Tab::make('Open')])))
            ->toContainElement(Selectors::TABS . ':not(' . SupportSelectors::CLOAK . ')', 'Open');
    });
});

describe('query string', function (): void {
    it('opens on the tab its query string names, by the key the URL carries', function (): void {
        $this::getContainer()->get('request_stack')->push(Request::create('/orders?tab=closed::data::tab&view=returned::data::tab'));
        $tabs = static fn (): array => [Tab::make('Open'), Tab::make('Closed'), Tab::make('Returned')];

        expect($this->renderSchemaComponent(Tabs::make('Orders')->persistTabInQueryString()->tabs($tabs())))
            ->toContainElement(Selectors::TABS . '[data-sylament--schemas--tabs-query-string-key-value="tab"]')
            ->toContainElement(Selectors::SELECTED_TAB, 'Closed')
            ->and($this->renderSchemaComponent(Tabs::make('Orders')->persistTabInQueryString('view')->tabs($tabs())))
            ->toContainElement(Selectors::TABS . '[data-sylament--schemas--tabs-query-string-key-value="view"]')
            ->toContainElement(Selectors::SELECTED_TAB, 'Returned')
            ->and($this->renderSchemaComponent(Tabs::make('Orders')->tabs($tabs())))
            ->toContainElement(Selectors::TABS . ':not([data-sylament--schemas--tabs-query-string-key-value])')
            ->toContainElement(Selectors::SELECTED_TAB, 'Open');
    });

    it('keeps its declared tab when the query string names none of its tabs', function (string $uri): void {
        $this::getContainer()->get('request_stack')->push(Request::create($uri));

        expect($this->renderSchemaComponent(Tabs::make('Orders')->persistTabInQueryString()->activeTab(2)->tabs([Tab::make('Open'), Tab::make('Closed')])))
            ->toContainElement(Selectors::SELECTED_TAB, 'Closed');
    })->with([
        'an unknown key' => '/orders?tab=returned::data::tab',
        'a list' => '/orders?tab[]=open::data::tab',
    ]);
});

describe('lazy-loaded badges', function (): void {
    it('leaves a lazy-loaded badge of a visible tab to the browser, which asks the tabs for it by tab key', function (): void {
        $tabs = Tabs::make('Orders')->tabs([
            'drafts' => Tab::make('Drafts')->hidden()->badge('1')->lazyLoadedBadge(),
            'open' => Tab::make('Open')->badge('3')->lazyLoadedBadge()->badgeIcon('heroicons:check')->badgeIconPosition(IconPosition::After)->badgeTooltip('Awaiting dispatch'),
            'closed' => Tab::make('Closed')->badge('5'),
        ]);
        $rendered = $this->renderSchemaComponent($tabs);
        $badges = $tabs->getLazyLoadedTabBadges();

        expect($rendered)
            ->toContainElement(Selectors::TABS . '[data-sylament--schemas--tabs-deferred-badges-value="true"][data-sylament--schemas--tabs-key-value="schema.orders::data::tabs"]')
            ->toContainElement(Selectors::TAB . ' ' . Selectors::tabsTarget('badgePlaceholder') . '[data-tab-key="open::data::tab"]')
            ->toContainElement(Selectors::TAB . ' ' . SupportSelectors::BADGE, '5')
            ->not->toContainElement(Selectors::TAB . ' ' . SupportSelectors::BADGE, '3')
            ->and(array_keys($badges))->toBe(['open::data::tab'])
            ->and($badges['open::data::tab'])->toMatchArray(['badge' => '3', 'badgeIconPosition' => 'after', 'badgeTooltip' => 'Awaiting dispatch'])
            ->and(new Crawler((string) $badges['open::data::tab']['badgeIconHtml'])->text())->toBe('check')
            ->and($this->renderSchemaComponent(Tabs::make('Orders')->tabs([Tab::make('Drafts')->hidden()->badge('1')->lazyLoadedBadge(), Tab::make('Open')])))
            ->toContainElement(Selectors::TABS . ':not([data-sylament--schemas--tabs-deferred-badges-value]):not([data-sylament--schemas--tabs-key-value])', 'Open');
    });

    it("answers the browser's request for the badges of its lazy-loaded tabs in a browser event", function (): void {
        $tester = $this->createLiveComponent(PropertyDrivenTabsHost::class)
            ->call('callSchemaComponentMethod', ['componentKey' => 'schema.orders::tabs', 'method' => 'getLazyLoadedTabBadges']);

        $events = $this->dispatchedBrowserEvents($tester, 'sylament:schema-component-method-result');

        expect($events)->toHaveCount(1)
            ->and($events[0]['method'])->toBe('getLazyLoadedTabBadges')
            ->and(array_keys($events[0]['result']))->toBe(['open'])
            ->and($events[0]['result']['open'])->toMatchArray(['badge' => '7']);
    });

    it('paints a lazy-loaded badge in its colour, a palette or primary', function (string|array|null $color, string $class, string $style): void {
        $tabs = Tabs::make('Orders')->tabs([Tab::make('Open')->badge('3')->badgeColor($color)->lazyLoadedBadge()]);
        $this->renderSchemaComponent($tabs);
        $badge = $tabs->getLazyLoadedTabBadges()['open::data::tab'];

        expect(explode(' ', $badge['badgeColorClasses']))->toContain($class)
            ->and(explode('; ', $badge['badgeColorStyles']))->toContain($style);
    })->with([
        'a named colour' => ['success', 'sy-color-success', ''],
        'a palette' => [Color::Rose, 'sy-color', '--sy-color-500: ' . Color::Rose[500]],
        'no colour' => [null, 'sy-color-primary', ''],
    ]);
});

describe('server mode', function (): void {
    it('opens the tab its host property names, rendering only that panel, and has each tab write the property', function (): void {
        $tester = $this->createLiveComponent(PropertyDrivenTabsHost::class);

        expect($tester->render()->crawler())
            ->toContainElement(Selectors::TABS . ':not([data-controller]):not(' . SupportSelectors::CLOAK . ')')
            ->toContainElement(Selectors::SELECTED_TAB . '[data-action="live#update"][data-model="activeTab"][data-value="open"][data-loading="addAttribute(disabled)"]', 'Open 7')
            ->toContainElement(Selectors::TAB . '[aria-selected="false"][data-action="live#update"][data-model="activeTab"][data-value="closed"]', 'Archived')
            ->toContainElement(Selectors::TAB . ' ' . SupportSelectors::BADGE, '7')
            ->not->toContainElement(Selectors::tabsTarget('badgePlaceholder'))
            ->toContainElement(Selectors::TAB_PANEL, 'Orders awaiting dispatch')
            ->not->toContainElement(Selectors::TAB_PANEL, 'Orders delivered')
            ->and($tester->set('activeTab', 'closed')->render()->crawler())
            ->toContainElement(Selectors::SELECTED_TAB, 'Archived')
            ->toContainElement(Selectors::TAB_PANEL, 'Orders delivered')
            ->not->toContainElement(Selectors::TAB_PANEL, 'Orders awaiting dispatch')
            ->and($tester->component()->getSchema()->getComponent('delivered'))->toBeInstanceOf(Text::class);
    });

    it('opens no tab when its host property names none of them', function (): void {
        $tabs = $this->createLiveComponent(PropertyDrivenTabsHost::class)->set('activeTab', 'returned')->render()->crawler();

        expect($tabs)->toContainButtons(Selectors::TAB . '[aria-selected="false"]', ['Open 7', 'Archived'])
            ->and($tabs->filter(Selectors::SELECTED_TAB . ', ' . Selectors::TAB_PANEL)->count())->toBe(0);
    });

    it('switches tabs in the browser when its property name is empty', function (): void {
        expect($this->renderSchemaComponent(Tabs::make('Orders')->liveComponentProperty('')->tabs([Tab::make('Open')->components([Text::make('Awaiting dispatch')])])))
            ->toContainElement(Selectors::TABS . '[data-controller="sylament--schemas--tabs"]')
            ->toContainElement(Selectors::SELECTED_TAB . '[data-action="click->sylament--schemas--tabs#select"]', 'Open')
            ->toContainElement(Selectors::TAB_PANEL, 'Awaiting dispatch');
    });
});
