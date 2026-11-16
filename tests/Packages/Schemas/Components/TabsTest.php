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

describe('client mode', function (): void {
    it('names its tablist after its label and shows each visible tab with its panel, leaving a hidden one out of both', function (): void {
        $tabs = $this->renderSchemaComponent(Tabs::make('Orders')->tabs([
            Tab::make('Open')->components([Text::make('Awaiting dispatch')]),
            Tab::make('Drafts')->hidden()->components([Text::make('Not yet placed')]),
            Tab::make('Closed')->components([Text::make('Delivered')]),
        ]));

        expect($tabs)->toContainButtons('nav[role="tablist"][aria-label="Orders"] [role="tab"]', ['Open', 'Closed'])
            ->toContainElement('[role="tabpanel"]', 'Awaiting dispatch')
            ->toContainElement('[role="tabpanel"]', 'Delivered')
            ->and($tabs->filter('[role="tabpanel"]')->count())->toBe(2);
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
            ->toContainElement('[role="tab"][aria-selected="true"]', 'Open')
            ->toContainElement('[role="tab"][aria-selected="false"]', 'Closed')
            ->toContainElement('[role="tabpanel"].sy-active', 'Awaiting dispatch')
            ->toContainElement('[role="tabpanel"]:not(.sy-active)', 'Delivered')
            ->and($this->renderSchemaComponent(Tabs::make('Orders')->activeTab(2)->tabs($tabs())))
            ->toContainElement(Selectors::TABS . '[data-sylament--schemas--tabs-active-tab-value="2"]')
            ->toContainElement('[role="tab"][aria-selected="true"]', 'Closed')
            ->toContainElement('[role="tab"][aria-selected="false"]', 'Open')
            ->toContainElement('[role="tabpanel"].sy-active', 'Delivered');
    });

    it('wires the tabs controller to its nav, its tabs and its panels', function (): void {
        expect($this->renderSchemaComponent(Tabs::make('Orders')->tabs([Tab::make('Open')->components([Text::make('Awaiting dispatch')])])))
            ->toContainElement(Selectors::TABS . '.sy-contained[data-controller="sylament--schemas--tabs"][data-sylament--schemas--tabs-schema-key-value="schema"][data-action~="reset-schema-component-state@window->sylament--schemas--tabs#resetState"]')
            ->toContainElement('nav[role="tablist"][data-sylament--schemas--tabs-target="nav"]')
            ->toContainElement('[role="tab"][data-sylament--schemas--tabs-target="tab"][data-action="click->sylament--schemas--tabs#select"][data-tab-key="open::data::tab"][aria-controls="schema.open::data::tab"]', 'Open')
            ->toContainElement('[role="tabpanel"][data-sylament--schemas--tabs-target="panel"][data-action="expand->sylament--schemas--tabs#expand"]', 'Awaiting dispatch');
    });

    it('points a tab at no panel when the tab has no id', function (): void {
        expect($this->renderSchemaComponent(Tabs::make('Orders')->tabs([Tab::make('Open')->id('')->components([Text::make('Awaiting dispatch')])])))
            ->toContainElement('[role="tab"]:not([id]):not([aria-controls])', 'Open');
    });

    it("lets a tab's extra attributes override its ARIA props but never its controller wiring", function (): void {
        expect($this->renderSchemaComponent(Tabs::make('Orders')->tabs([
            Tab::make('Open')->extraAttributes(['title' => 'Awaiting dispatch', 'aria-selected' => 'mixed', 'data-action' => 'click->tracker#log']),
        ])))->toContainElement('[role="tab"][title="Awaiting dispatch"][aria-selected="mixed"][data-action="click->sylament--schemas--tabs#select"]', 'Open');
    });

    it('stacks its tab strip vertically when told', function (): void {
        expect($this->renderSchemaComponent(Tabs::make('Orders')->vertical()->tabs([Tab::make('Open')])))
            ->toContainElement(Selectors::TABS . '.sy-vertical nav.sy-vertical[role="tablist"]', 'Open');
    });

    it('folds its tabs into an overflow dropdown when not scrollable, and keeps them all in the strip otherwise', function (): void {
        $tabs = static fn (): array => [Tab::make('Open')->badge('3'), Tab::make('Closed')];

        expect($this->renderSchemaComponent(Tabs::make('Orders')->scrollable(false)->activeTab(2)->tabs($tabs())))
            ->toContainElement(Selectors::TABS . '[data-sylament--schemas--tabs-scrollable-value="false"][data-action~="resize@window->sylament--schemas--tabs#resize"]')
            ->toContainElement('[data-sylament--schemas--tabs-target="triggerTab"]', 'chevron-downOpen 3')
            ->toContainElement('[data-sylament--schemas--tabs-target="triggerTab"][aria-current="true"]', 'chevron-downClosed')
            ->toContainElement('[data-sylament--schemas--tabs-target="dropdown"] [data-sylament--schemas--tabs-target="moreButton"]', 'ellipsis-horizontal')
            ->toContainElement('[data-sylament--schemas--tabs-target="dropdownItem"]', 'Open 3')
            ->toContainElement('[data-sylament--schemas--tabs-target="dropdownItem"].sy-selected', 'Closed')
            ->and($this->renderSchemaComponent(Tabs::make('Orders')->tabs($tabs())))
            ->toContainElement(Selectors::TABS . ':not([data-sylament--schemas--tabs-scrollable-value])')
            ->toContainButtons('[role="tab"]', ['Open 3', 'Closed'])
            ->not->toContainElement('[data-sylament--schemas--tabs-target="dropdown"]');
    });

    it('hides itself until its controller has restored a persisted tab, and only then', function (): void {
        expect($this->renderSchemaComponent(Tabs::make('Orders')->persistTab()->tabs([Tab::make('Open')])))
            ->toContainElement(Selectors::TABS . '.sy-cloak', 'Open')
            ->and($this->renderSchemaComponent(Tabs::make()->persistTab()->tabs([Tab::make('Open')])))
            ->toContainElement(Selectors::TABS . ':not(.sy-cloak)', 'Open')
            ->and($this->renderSchemaComponent(Tabs::make('Orders')->tabs([Tab::make('Open')])))
            ->toContainElement(Selectors::TABS . ':not(.sy-cloak)', 'Open');
    });
});

describe('query string', function (): void {
    it('opens on the tab its query string names, by the key the URL carries', function (): void {
        $this::getContainer()->get('request_stack')->push(Request::create('/orders?tab=closed::data::tab&view=returned::data::tab'));
        $tabs = static fn (): array => [Tab::make('Open'), Tab::make('Closed'), Tab::make('Returned')];

        expect($this->renderSchemaComponent(Tabs::make('Orders')->persistTabInQueryString()->tabs($tabs())))
            ->toContainElement(Selectors::TABS . '[data-sylament--schemas--tabs-query-string-key-value="tab"]')
            ->toContainElement('[role="tab"][aria-selected="true"]', 'Closed')
            ->and($this->renderSchemaComponent(Tabs::make('Orders')->persistTabInQueryString('view')->tabs($tabs())))
            ->toContainElement(Selectors::TABS . '[data-sylament--schemas--tabs-query-string-key-value="view"]')
            ->toContainElement('[role="tab"][aria-selected="true"]', 'Returned')
            ->and($this->renderSchemaComponent(Tabs::make('Orders')->tabs($tabs())))
            ->toContainElement(Selectors::TABS . ':not([data-sylament--schemas--tabs-query-string-key-value])')
            ->toContainElement('[role="tab"][aria-selected="true"]', 'Open');
    });

    it('keeps its declared tab when the query string names none of its tabs', function (string $uri): void {
        $this::getContainer()->get('request_stack')->push(Request::create($uri));

        expect($this->renderSchemaComponent(Tabs::make('Orders')->persistTabInQueryString()->activeTab(2)->tabs([Tab::make('Open'), Tab::make('Closed')])))
            ->toContainElement('[role="tab"][aria-selected="true"]', 'Closed');
    })->with([
        'an unknown key' => '/orders?tab=returned::data::tab',
        'a list' => '/orders?tab[]=open::data::tab',
    ]);
});

describe('deferred badges', function (): void {
    it('leaves a deferred badge of a visible tab to the browser, which asks the tabs for it by tab key', function (): void {
        $tabs = Tabs::make('Orders')->tabs([
            'drafts' => Tab::make('Drafts')->hidden()->badge('1')->deferBadge(),
            'open' => Tab::make('Open')->badge('3')->deferBadge()->badgeIcon('heroicons:check')->badgeIconPosition(IconPosition::After)->badgeTooltip('Awaiting dispatch'),
            'closed' => Tab::make('Closed')->badge('5'),
        ]);
        $rendered = $this->renderSchemaComponent($tabs);
        $badges = $tabs->getDeferredTabBadges();

        expect($rendered)
            ->toContainElement(Selectors::TABS . '[data-sylament--schemas--tabs-deferred-badges-value="true"][data-sylament--schemas--tabs-key-value="schema.orders::data::tabs"]')
            ->toContainElement('[role="tab"] [data-sylament--schemas--tabs-target="badgePlaceholder"][data-tab-key="open::data::tab"]')
            ->toContainElement('[role="tab"] ' . SupportSelectors::BADGE, '5')
            ->not->toContainElement('[role="tab"] ' . SupportSelectors::BADGE, '3')
            ->and(array_keys($badges))->toBe(['open::data::tab'])
            ->and($badges['open::data::tab'])->toMatchArray(['badge' => '3', 'badgeIconPosition' => 'after', 'badgeTooltip' => 'Awaiting dispatch'])
            ->and(new Crawler((string) $badges['open::data::tab']['badgeIconHtml'])->text())->toBe('check')
            ->and($this->renderSchemaComponent(Tabs::make('Orders')->tabs([Tab::make('Drafts')->hidden()->badge('1')->deferBadge(), Tab::make('Open')])))
            ->toContainElement(Selectors::TABS . ':not([data-sylament--schemas--tabs-deferred-badges-value]):not([data-sylament--schemas--tabs-key-value])', 'Open');
    });

    it('paints a deferred badge in its colour, a palette or primary', function (string|array|null $color, string $class, string $style): void {
        $tabs = Tabs::make('Orders')->tabs([Tab::make('Open')->badge('3')->badgeColor($color)->deferBadge()]);
        $this->renderSchemaComponent($tabs);
        $badge = $tabs->getDeferredTabBadges()['open::data::tab'];

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
            ->toContainElement(Selectors::TABS . ':not([data-controller]):not(.sy-cloak)')
            ->toContainElement('[role="tab"][aria-selected="true"][data-action="live#update"][data-model="activeTab"][data-value="open"][data-loading="addAttribute(disabled)"]', 'Open 7')
            ->toContainElement('[role="tab"][aria-selected="false"][data-action="live#update"][data-model="activeTab"][data-value="closed"]', 'Archived')
            ->toContainElement('[role="tab"] ' . SupportSelectors::BADGE, '7')
            ->not->toContainElement('[data-sylament--schemas--tabs-target="badgePlaceholder"]')
            ->toContainElement('[role="tabpanel"]', 'Orders awaiting dispatch')
            ->not->toContainElement('[role="tabpanel"]', 'Orders delivered')
            ->and($tester->set('activeTab', 'closed')->render()->crawler())
            ->toContainElement('[role="tab"][aria-selected="true"]', 'Archived')
            ->toContainElement('[role="tabpanel"]', 'Orders delivered')
            ->not->toContainElement('[role="tabpanel"]', 'Orders awaiting dispatch')
            ->and($tester->component()->getSchema()->getComponent('delivered'))->toBeInstanceOf(Text::class);
    });

    it('opens no tab when its host property names none of them', function (): void {
        $tabs = $this->createLiveComponent(PropertyDrivenTabsHost::class)->set('activeTab', 'returned')->render()->crawler();

        expect($tabs)->toContainButtons('[role="tab"][aria-selected="false"]', ['Open 7', 'Archived'])
            ->and($tabs->filter('[role="tab"][aria-selected="true"], [role="tabpanel"]')->count())->toBe(0);
    });

    it('switches tabs in the browser when its property name is empty', function (): void {
        expect($this->renderSchemaComponent(Tabs::make('Orders')->liveComponentProperty('')->tabs([Tab::make('Open')->components([Text::make('Awaiting dispatch')])])))
            ->toContainElement(Selectors::TABS . '[data-controller="sylament--schemas--tabs"]')
            ->toContainElement('[role="tab"][aria-selected="true"][data-action="click->sylament--schemas--tabs#select"]', 'Open')
            ->toContainElement('[role="tabpanel"]', 'Awaiting dispatch');
    });
});
