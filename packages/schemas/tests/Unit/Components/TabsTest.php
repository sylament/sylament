<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Components;

use Sylament\Schemas\Components\Tabs;
use Sylament\Schemas\Components\Tabs\Tab;
use Sylament\Schemas\Components\Text;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Concerns\HasSchemasFixture;

/**
 * Wire the tabs into a real parent Schema, as a render does: the key closure reads the
 * state path, which is only reachable through the container chain.
 */
function wireTabs(Tabs $tabs): Tabs
{
    Schema::make(new HasSchemasFixture())->components([$tabs])->getComponents();

    return $tabs;
}

it('keys itself from its label, non-inheritable, and has no key without one', function (): void {
    $unlabelled = Tabs::make();
    $labelled = Tabs::make('Order details');
    Schema::make(new HasSchemasFixture())->components([$unlabelled, $labelled])->getComponents();

    expect($unlabelled->getKey(isAbsolute: false))->toBeNull()
        ->and($labelled->getKey(isAbsolute: false))->toBe('order-details::tabs')
        ->and($labelled->isKeyInheritable())->toBeFalse();
});

it('leaves the absolute keys of nested components untouched', function (): void {
    $schema = Schema::make(new HasSchemasFixture())->components([
        Tabs::make('Article')->tabs([Tab::make('Content')->components([Text::make('inner')->key('inner')])]),
    ]);

    // The non-inheritable keys mean no tabs/tab segment joins the child's absolute key.
    expect($schema->getComponent('inner'))->not->toBeNull();
});

it('applies configureUsing() closures on make()', function (): void {
    Tabs::configureUsing(function (Tabs $tabs): void {
        $tabs->vertical();
    });

    expect(Tabs::make('Article')->isVertical())->toBeTrue();
});

it('defaults to the first tab without a request stack', function (): void {
    $tabs = wireTabs(Tabs::make('T')->tabs([Tab::make('One'), Tab::make('Two')])->persistTabInQueryString());

    expect($tabs->getActiveTab())->toBe(1)
        ->and($tabs->getActiveTabKey())->toBe('one::tab');
});

it('reads the active tab from activeTab(), 1-based, and answers its key', function (): void {
    $tabs = wireTabs(Tabs::make('T')->tabs([Tab::make('One'), Tab::make('Two')])->activeTab(2));

    expect($tabs->getActiveTab())->toBe(2)
        ->and($tabs->getActiveTabKey())->toBe('two::tab');
});

it('evaluates an activeTab closure', function (): void {
    $tabs = wireTabs(Tabs::make('T')->tabs([Tab::make('One'), Tab::make('Two')])->activeTab(fn (): int => 2));

    expect($tabs->getActiveTab())->toBe(2);
});

it('answers a null active key when the active position has no tab', function (): void {
    $tabs = wireTabs(Tabs::make('T')->tabs([Tab::make('One')])->activeTab(5));

    expect($tabs->getActiveTabKey())->toBeNull();
});

it('lists the visible tabs only, in order', function (): void {
    $tabs = wireTabs(Tabs::make('T')->tabs([
        Tab::make('One'),
        Tab::make('Hidden')->hidden(),
        Tab::make('Two'),
    ]));

    expect(array_map(
        static fn (Tab $tab): ?string => $tab->getKey(isAbsolute: false),
        $tabs->getTabs(),
    ))->toBe(['one::tab', 'two::tab']);
});

it('persistTabInQueryString() defaults the key to "tab" and accepts a custom key', function (): void {
    expect(Tabs::make('T')->isTabPersistedInQueryString())->toBeFalse()
        ->and(Tabs::make('T')->getTabQueryStringKey())->toBeNull()
        ->and(Tabs::make('T')->persistTabInQueryString()->getTabQueryStringKey())->toBe('tab')
        ->and(Tabs::make('T')->persistTabInQueryString()->isTabPersistedInQueryString())->toBeTrue()
        ->and(Tabs::make('T')->persistTabInQueryString('section')->getTabQueryStringKey())->toBe('section')
        ->and(Tabs::make('T')->persistTabInQueryString(fn (): string => 'view')->getTabQueryStringKey())->toBe('view')
        ->and(Tabs::make('T')->persistTabInQueryString(null)->isTabPersistedInQueryString())->toBeFalse();
});

it('scrollable() defaults to true; vertical() and persistTab() default to false', function (): void {
    expect(Tabs::make('T')->isScrollable())->toBeTrue()
        ->and(Tabs::make('T')->scrollable(false)->isScrollable())->toBeFalse()
        ->and(Tabs::make('T')->scrollable(fn (): bool => false)->isScrollable())->toBeFalse()
        ->and(Tabs::make('T')->isVertical())->toBeFalse()
        ->and(Tabs::make('T')->vertical()->isVertical())->toBeTrue()
        ->and(Tabs::make('T')->isTabPersisted())->toBeFalse()
        ->and(Tabs::make('T')->persistTab()->isTabPersisted())->toBeTrue();
});

it('is contained by default and can be uncontained', function (): void {
    expect(Tabs::make('T')->isContained())->toBeTrue()
        ->and(Tabs::make('T')->contained(false)->isContained())->toBeFalse();
});

it('liveComponentProperty() re-keys tabs by their array key', function (): void {
    $tabs = Tabs::make()->liveComponentProperty('activeTab')->tabs([
        'all' => Tab::make('All'),
        'published' => Tab::make('Published'),
    ]);
    Schema::make(new HasSchemasFixture())->components([$tabs])->getComponents();

    expect($tabs->getLiveComponentProperty())->toBe('activeTab')
        ->and(array_map(
            static fn (Tab $tab): ?string => $tab->getKey(isAbsolute: false),
            $tabs->getTabs(),
        ))->toBe(['all', 'published']);
});

it('keys tabs from their labels without a live component property', function (): void {
    $tabs = wireTabs(Tabs::make()->tabs([
        'all' => Tab::make('All'),
        'published' => Tab::make('Published'),
    ]));

    expect(array_map(
        static fn (Tab $tab): ?string => $tab->getKey(isAbsolute: false),
        $tabs->getTabs(),
    ))->toBe(['all::tab', 'published::tab']);
});

it('hasDeferredBadges() reflects any deferred tab', function (): void {
    expect(wireTabs(Tabs::make('T')->tabs([Tab::make('A'), Tab::make('B')]))->hasDeferredBadges())->toBeFalse()
        ->and(wireTabs(Tabs::make('T')->tabs([Tab::make('A'), Tab::make('B')->deferBadge()]))->hasDeferredBadges())->toBeTrue();
});
