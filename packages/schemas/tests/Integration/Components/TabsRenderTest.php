<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Integration\Components;

use Sylament\Schemas\Components\Tabs;
use Sylament\Schemas\Components\Tabs\Tab;
use Sylament\Schemas\Components\Text;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Concerns\HasSchemasFixture;
use Sylament\Support\Colors\ColorManager;
use Sylament\Support\Enums\IconPosition;
use Sylament\Support\Twig\Extension\IconExtension;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

use function Sylament\Schemas\Tests\renderToHtml;

uses(InteractsWithLiveComponents::class);

/**
 * Render the Tabs as a child of a real Schema, the way it is used: the parent Schema is
 * the container supplying the live component, and the whole DOM contract the
 * `sylament--schemas--tabs` controller binds to comes out of one render.
 */
function renderTabs(Tabs $tabs): string
{
    return renderToHtml(Schema::make(new HasSchemasFixture())->components([$tabs]));
}

function articleTabs(): Tabs
{
    return Tabs::make('Article')->tabs([
        Tab::make('Content')->components([Text::make('a')]),
        Tab::make('Meta')->components([Text::make('b')]),
    ]);
}

it('registers Tab as a non-shared container service', function (): void {
    $first = $this::getContainer()->get(Tab::class);
    $second = $this::getContainer()->get(Tab::class);

    expect($first)->toBeInstanceOf(Tab::class)
        ->and($second)->toBeInstanceOf(Tab::class)
        ->and($second)->not->toBe($first);
});

it('renders the controller, its values and the reset action on the root', function (): void {
    $root = new Crawler(renderTabs(articleTabs()))->filter('div.sy.sy-sc-tabs');

    expect($root)->toHaveCount(1)
        ->and($root->attr('id'))->toBe('article::tabs')
        ->and($root->attr('data-controller'))->toBe('sylament--schemas--tabs')
        ->and($root->attr('data-sylament--schemas--tabs-active-tab-value'))->toBe('1')
        // An unnamed root schema has no key of its own; a host-resolved one carries its name.
        ->and($root->attr('data-sylament--schemas--tabs-schema-key-value'))->toBe('')
        ->and($root->attr('data-sylament--schemas--tabs-scrollable-value'))->toBeNull()
        ->and($root->attr('data-sylament--schemas--tabs-persist-value'))->toBeNull()
        ->and($root->attr('data-sylament--schemas--tabs-key-value'))->toBeNull()
        ->and($root->attr('data-sylament--schemas--tabs-deferred-badges-value'))->toBeNull()
        ->and($root->attr('class'))->toContain('sy-contained')
        ->and($root->attr('class'))->not->toContain('sy-cloak')
        ->and($root->attr('data-action'))->toContain('reset-schema-component-state@window->sylament--schemas--tabs#resetState')
        ->and($root->attr('data-action'))->not->toContain('resize@window');
});

it('renders the nav and one tab button per tab', function (): void {
    $crawler = new Crawler(renderTabs(articleTabs()));

    $nav = $crawler->filter('nav.sy.sy-tabs');
    expect($nav)->toHaveCount(1)
        ->and($nav->attr('role'))->toBe('tablist')
        ->and($nav->attr('aria-label'))->toBe('Article')
        ->and($nav->attr('data-sylament--schemas--tabs-target'))->toBe('nav')
        ->and($nav->attr('class'))->toContain('sy-contained');

    $buttons = $crawler->filter('nav.sy-tabs > button.sy-tabs-item');
    expect($buttons)->toHaveCount(2);

    $first = $buttons->eq(0);
    expect($first->attr('role'))->toBe('tab')
        ->and($first->attr('type'))->toBe('button')
        ->and($first->attr('data-tab-key'))->toBe('content::tab')
        ->and($first->attr('id'))->toBe('content::tab-tab')
        ->and($first->attr('aria-controls'))->toBe('content::tab')
        ->and($first->attr('aria-selected'))->toBe('true')
        ->and($first->attr('aria-current'))->toBe('true')
        ->and($first->attr('class'))->toContain('sy-active')
        ->and($first->attr('data-sylament--schemas--tabs-target'))->toBe('tab')
        ->and($first->attr('data-action'))->toBe('click->sylament--schemas--tabs#select')
        ->and($first->filter('span.sy-tabs-item-label')->text())->toBe('Content');

    $second = $buttons->eq(1);
    expect($second->attr('data-tab-key'))->toBe('meta::tab')
        ->and($second->attr('aria-selected'))->toBe('false')
        ->and($second->attr('aria-current'))->toBeNull()
        ->and($second->attr('class'))->not->toContain('sy-active');
});

it('renders one panel per tab, the active one flagged', function (): void {
    $panels = new Crawler(renderTabs(articleTabs()))->filter('div.sy-sc-tabs-tab');

    expect($panels)->toHaveCount(2)
        ->and($panels->eq(0)->attr('role'))->toBe('tabpanel')
        ->and($panels->eq(0)->attr('id'))->toBe('content::tab')
        ->and($panels->eq(0)->attr('aria-labelledby'))->toBe('content::tab-tab')
        ->and($panels->eq(0)->attr('data-tab-key'))->toBe('content::tab')
        ->and($panels->eq(0)->attr('class'))->toContain('sy-active')
        ->and($panels->eq(0)->attr('data-sylament--schemas--tabs-target'))->toBe('panel')
        ->and($panels->eq(0)->attr('data-action'))->toBe('expand->sylament--schemas--tabs#expand')
        ->and($panels->eq(1)->attr('class'))->not->toContain('sy-active');
});

it('renders no panel for a tab with no visible children', function (): void {
    $html = renderTabs(Tabs::make('Article')->tabs([
        Tab::make('Content')->components([Text::make('a')]),
        Tab::make('Empty'),
        Tab::make('AllHidden')->components([Text::make('b')->hidden()]),
    ]));

    $panels = new Crawler($html)->filter('div.sy-sc-tabs-tab');

    expect($panels)->toHaveCount(1)
        ->and($panels->eq(0)->attr('data-tab-key'))->toBe('content::tab')
        // The buttons stay: an empty tab is still selectable, as Filament renders it.
        ->and(new Crawler($html)->filter('nav.sy-tabs > button.sy-tabs-item'))->toHaveCount(3);
});

it('moves the active state with activeTab()', function (): void {
    $crawler = new Crawler(renderTabs(articleTabs()->activeTab(2)));

    expect($crawler->filter('div.sy.sy-sc-tabs')->attr('data-sylament--schemas--tabs-active-tab-value'))->toBe('2')
        ->and($crawler->filter('nav.sy-tabs > button.sy-tabs-item')->eq(1)->attr('class'))->toContain('sy-active')
        ->and($crawler->filter('nav.sy-tabs > button.sy-tabs-item')->eq(1)->attr('aria-selected'))->toBe('true')
        ->and($crawler->filter('nav.sy-tabs > button.sy-tabs-item')->eq(0)->attr('class'))->not->toContain('sy-active')
        ->and($crawler->filter('div.sy-sc-tabs-tab')->eq(1)->attr('class'))->toContain('sy-active')
        ->and($crawler->filter('div.sy-sc-tabs-tab')->eq(0)->attr('class'))->not->toContain('sy-active');
});

it('cloaks and carries the persistence values only when the id is filled', function (): void {
    $persisted = new Crawler(renderTabs(articleTabs()->persistTab()->id('x')))->filter('div.sy.sy-sc-tabs');

    expect($persisted->attr('class'))->toContain('sy-cloak')
        ->and($persisted->attr('data-sylament--schemas--tabs-persist-value'))->toBe('true')
        ->and($persisted->attr('data-sylament--schemas--tabs-persist-id-value'))->toBe('x');

    // A label-less Tabs has no key, so `getId()` is null and there is nothing to store under.
    $idless = new Crawler(renderTabs(
        Tabs::make()->persistTab()->tabs([Tab::make('Content')->components([Text::make('a')])]),
    ))->filter('div.sy.sy-sc-tabs');

    expect($idless->attr('class'))->not->toContain('sy-cloak')
        ->and($idless->attr('data-sylament--schemas--tabs-persist-value'))->toBeNull()
        ->and($idless->attr('data-sylament--schemas--tabs-persist-id-value'))->toBeNull();
});

it('carries the query-string key when persisting there', function (): void {
    expect(new Crawler(renderTabs(articleTabs()->persistTabInQueryString()))->filter('div.sy.sy-sc-tabs')
        ->attr('data-sylament--schemas--tabs-query-string-key-value'))->toBe('tab');
});

it('renders the overflow dropdown when scrollable(false)', function (): void {
    $crawler = new Crawler(renderTabs(articleTabs()->scrollable(false)));

    $root = $crawler->filter('div.sy.sy-sc-tabs');
    expect($root->attr('data-sylament--schemas--tabs-scrollable-value'))->toBe('false')
        ->and($root->attr('data-action'))->toContain('resize@window->sylament--schemas--tabs#resize');

    $dropdown = $crawler->filter('nav.sy-tabs div.sy-dropdown');
    expect($dropdown)->toHaveCount(1)
        ->and($dropdown->attr('data-sylament--schemas--tabs-target'))->toBe('dropdown');

    $triggerTabs = $crawler->filter('[data-sylament--schemas--tabs-target="triggerTab"]');
    expect($triggerTabs)->toHaveCount(2)
        ->and($triggerTabs->eq(0)->attr('data-tab-key'))->toBe('content::tab')
        ->and($triggerTabs->eq(0)->attr('aria-selected'))->toBe('false')
        ->and($triggerTabs->eq(0)->attr('class'))->toContain('sy-active')
        ->and($triggerTabs->eq(0)->filter('svg.sy-icon'))->toHaveCount(1);

    expect($crawler->filter('[data-sylament--schemas--tabs-target="moreButton"]'))->toHaveCount(1)
        ->and($crawler->filter('[data-sylament--schemas--tabs-target="moreButton"] svg.sy-icon'))->toHaveCount(1);

    $items = $crawler->filter('[data-sylament--schemas--tabs-target="dropdownItem"]');
    expect($items)->toHaveCount(2)
        ->and($items->eq(0)->attr('data-tab-key'))->toBe('content::tab')
        ->and($items->eq(0)->attr('class'))->toContain('sy-selected')
        ->and($items->eq(0)->attr('data-loading'))->toBe('addAttribute(disabled)')
        ->and($items->eq(0)->attr('data-action'))->toBe('click->sylament--schemas--tabs#selectFromDropdown click->sylament--support--dropdown#close')
        ->and($items->eq(1)->attr('class'))->not->toContain('sy-selected');
});

it('renders no dropdown while scrollable', function (): void {
    expect(new Crawler(renderTabs(articleTabs()))->filter('div.sy-dropdown'))->toHaveCount(0);
});

it('drops the contained class and adds the vertical one', function (): void {
    $uncontained = new Crawler(renderTabs(articleTabs()->contained(false)));

    expect($uncontained->filter('div.sy.sy-sc-tabs')->attr('class'))->not->toContain('sy-contained')
        ->and($uncontained->filter('nav.sy-tabs')->attr('class'))->not->toContain('sy-contained');

    $vertical = new Crawler(renderTabs(articleTabs()->vertical()));

    expect($vertical->filter('div.sy.sy-sc-tabs')->attr('class'))->toContain('sy-vertical')
        ->and($vertical->filter('nav.sy-tabs')->attr('class'))->toContain('sy-vertical');
});

it('renders a tab icon and a static badge with its colour and tooltip', function (): void {
    $crawler = new Crawler(renderTabs(Tabs::make('Article')->tabs([
        Tab::make('A')
            ->icon('heroicons:star')
            ->badge('3')
            ->badgeColor('success')
            ->badgeTooltip('Three')
            ->components([Text::make('a')]),
    ])));

    $button = $crawler->filter('nav.sy-tabs > button.sy-tabs-item');
    $children = $button->children();

    expect($children->eq(0)->nodeName())->toBe('svg')
        ->and($children->eq(0)->attr('class'))->toContain('sy-icon')
        ->and($children->eq(1)->attr('class'))->toBe('sy-tabs-item-label');

    $badge = $button->filter('span.sy-badge');
    expect($badge)->toHaveCount(1)
        ->and($badge->attr('class'))->toContain('sy-size-sm')
        ->and($badge->attr('class'))->toContain('sy-color-success')
        ->and($badge->attr('title'))->toBe('Three')
        ->and($badge->filter('span.sy-badge-label')->text())->toBe('3');
});

it('honours the tab icon position', function (): void {
    $crawler = new Crawler(renderTabs(Tabs::make('Article')->tabs([
        Tab::make('A')->icon('heroicons:star')->iconPosition(IconPosition::After)->components([Text::make('a')]),
    ])));

    $children = $crawler->filter('nav.sy-tabs > button.sy-tabs-item')->children();

    expect($children->eq(0)->attr('class'))->toBe('sy-tabs-item-label')
        ->and($children->eq(1)->nodeName())->toBe('svg');
});

it('renders a loading placeholder instead of a deferred badge', function (): void {
    $html = renderTabs(Tabs::make('Article')->tabs([
        Tab::make('A')->badge('7')->deferBadge()->components([Text::make('a')]),
    ]));
    $crawler = new Crawler($html);

    $root = $crawler->filter('div.sy.sy-sc-tabs');
    expect($root->attr('data-sylament--schemas--tabs-deferred-badges-value'))->toBe('true')
        ->and($root->attr('data-sylament--schemas--tabs-key-value'))->toBe('article::tabs');

    $placeholder = $crawler->filter('span.sy-tabs-item-badge-placeholder');
    expect($placeholder)->toHaveCount(1)
        ->and($placeholder->attr('data-tab-key'))->toBe('a::tab')
        ->and($placeholder->attr('data-sylament--schemas--tabs-target'))->toBe('badgePlaceholder')
        ->and($placeholder->filter('svg.sy-loading-indicator.sy-size-sm'))->toHaveCount(1)
        ->and($crawler->filter('span.sy-badge'))->toHaveCount(0);
});

it('drives the tabs from the host property when liveComponentProperty() is set', function (): void {
    $html = (string) $this->createLiveComponent('TabsPropertyHost')->render();
    $crawler = new Crawler($html);

    $root = $crawler->filter('div.sy.sy-sc-tabs');
    expect($root->attr('data-controller'))->toBeNull()
        ->and($root->attr('id'))->toBe('schema.articles::tabs');

    $buttons = $crawler->filter('nav.sy-tabs > button.sy-tabs-item');
    expect($buttons)->toHaveCount(2);

    $all = $buttons->eq(0);
    expect($all->attr('data-tab-key'))->toBe('all')
        ->and($all->attr('data-action'))->toBe('live#update')
        ->and($all->attr('data-model'))->toBe('activeTab')
        ->and($all->attr('data-value'))->toBe('all')
        ->and($all->attr('data-loading'))->toBe('addAttribute(disabled)')
        ->and($all->attr('class'))->toContain('sy-active')
        ->and($all->attr('aria-selected'))->toBe('true')
        // The labels come from the host's generateTabLabel() hook, the tabs carrying none.
        ->and($all->filter('span.sy-tabs-item-label')->text())->toBe('All')
        ->and($all->attr('data-sylament--schemas--tabs-target'))->toBeNull();

    $published = $buttons->eq(1);
    expect($published->attr('data-value'))->toBe('published')
        ->and($published->attr('aria-selected'))->toBe('false')
        ->and($published->filter('span.sy-tabs-item-label')->text())->toBe('Published');

    // Only the active tab's panel renders, and it carries no controller wiring.
    $panels = $crawler->filter('div.sy-sc-tabs-tab');
    expect($panels)->toHaveCount(1)
        ->and($panels->attr('data-tab-key'))->toBe('all')
        ->and($panels->attr('class'))->toContain('sy-active')
        ->and($panels->attr('data-sylament--schemas--tabs-target'))->toBeNull();

    // A deferred badge cannot be filled by a controller that is not there, so it renders eagerly.
    expect($crawler->filter('span.sy-tabs-item-badge-placeholder'))->toHaveCount(0)
        ->and($crawler->filter('span.sy-badge span.sy-badge-label')->text())->toBe('7');
});

it('lets UX Live inject the bound property value onto a server-driven tab button', function (): void {
    // Passing `data-model` to a Twig component makes ux-live-component's DataModelPropsSubscriber
    // add a `value` prop holding the host property's value. It is inert on a `type="button"`
    // element; pinned here so the extra attribute is a recorded consequence, not a surprise.
    $buttons = new Crawler((string) $this->createLiveComponent('TabsPropertyHost')->render())
        ->filter('nav.sy-tabs > button.sy-tabs-item');

    expect($buttons->eq(0)->attr('value'))->toBe('all')
        ->and($buttons->eq(1)->attr('value'))->toBe('all');
});

/**
 * The query-string arm needs a request the component can read, which is what the
 * `#[Required]` setter injects in an application. Re-calling it with a stack carrying a
 * request is the test-side twin (the container's own stack has no request in a unit-ish render).
 */
function tabsWithQueryString(string $uri): Tabs
{
    $requestStack = new RequestStack();
    $requestStack->push(Request::create($uri));

    $tabs = articleTabs()->persistTabInQueryString();
    $tabs->setEvaluationServices(
        test()->getContainer()->get(IconExtension::class),
        test()->getContainer()->get(ColorManager::class),
        $requestStack,
    );

    return $tabs;
}

it('activates the tab the query string names', function (): void {
    $tabs = tabsWithQueryString('/?tab=meta::tab');
    $crawler = new Crawler(renderTabs($tabs));

    expect($tabs->getActiveTab())->toBe(2)
        ->and($tabs->getActiveTabKey())->toBe('meta::tab')
        ->and($crawler->filter('nav.sy-tabs > button.sy-tabs-item')->eq(1)->attr('class'))->toContain('sy-active')
        ->and($crawler->filter('div.sy-sc-tabs-tab')->eq(1)->attr('class'))->toContain('sy-active');
});

it('falls back to activeTab() for a query string naming no tab', function (): void {
    $tabs = tabsWithQueryString('/?tab=nope::tab')->activeTab(2);
    Schema::make(new HasSchemasFixture())->components([$tabs])->getComponents();

    expect($tabs->getActiveTab())->toBe(2)
        ->and($tabs->getActiveTabKey())->toBe('meta::tab');
});

it('ignores the query string when not persisting there', function (): void {
    $requestStack = new RequestStack();
    $requestStack->push(Request::create('/?tab=meta::tab'));

    $tabs = articleTabs();
    $tabs->setEvaluationServices(
        $this::getContainer()->get(IconExtension::class),
        $this::getContainer()->get(ColorManager::class),
        $requestStack,
    );
    Schema::make(new HasSchemasFixture())->components([$tabs])->getComponents();

    expect($tabs->getActiveTab())->toBe(1);
});
