<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Tests\Fixtures\Actions\DefaultUrlHost;
use Sylament\Tests\Fixtures\Actions\Selectors;

it('links to its url, in a new tab when told either way, in place of a live click', function (Closure $look, string $selector): void {
    $link = $this->renderViewComponent($look(Action::make('orders')->url('/orders')));

    expect($link)->toContainElement('a[href="/orders"]:not([target])' . Selectors::TRIGGER . $selector)
        ->and($link->filter(Selectors::LIVE_CLICK))->toHaveCount(0)
        ->and($this->renderViewComponent($look(Action::make('orders')->url('/orders', shouldOpenInNewTab: true))))
        ->toContainElement('a[href="/orders"][target="_blank"]' . $selector)
        ->and($this->renderViewComponent($look(Action::make('orders')->url('/orders')->openUrlInNewTab())))
        ->toContainElement('a[href="/orders"][target="_blank"]' . $selector);
})->with('trigger looks');

it('opens its modal rather than its url only when told it has one, not for modal content alone', function (): void {
    $modal = $this->renderViewComponent(Action::make('orders')->url('/orders')->modal());

    expect($modal)->toContainElement('button' . Selectors::TRIGGER, 'Orders')
        ->and($modal->filter('a[href]'))->toHaveCount(0)
        ->and($this->renderViewComponent(Action::make('orders')->url('/orders')->modalHeading('Orders')))
        ->toContainElement('a[href="/orders"]' . Selectors::TRIGGER, 'Orders');
});

it('links to the url its host gives it by name, unless it has its own or opens a modal', function (): void {
    $modal = $this->renderViewComponent(Action::make('orders')->modal()->liveComponent(new DefaultUrlHost()));

    expect($this->renderViewComponent(Action::make('orders')->liveComponent(new DefaultUrlHost())))
        ->toContainElement('a[href="/orders"]' . Selectors::TRIGGER, 'Orders')
        ->and($this->renderViewComponent(Action::make('orders')->url('/archive')->liveComponent(new DefaultUrlHost())))
        ->toContainElement('a[href="/archive"]' . Selectors::TRIGGER, 'Orders')
        ->and($this->renderViewComponent(Action::make('drafts')->liveComponent(new DefaultUrlHost())))
        ->toContainElement('button' . Selectors::TRIGGER . Selectors::LIVE_CLICK, 'Drafts')
        ->and($modal)->toContainElement('button' . Selectors::TRIGGER, 'Orders')
        ->and($modal->filter('a[href]'))->toHaveCount(0);
});
