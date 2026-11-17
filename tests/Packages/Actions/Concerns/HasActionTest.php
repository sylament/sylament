<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Tests\Fixtures\Actions\ProductWriterHost;
use Sylament\Tests\Fixtures\Actions\Selectors;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('runs its closure when its host calls it, handing the closure the action', function (): void {
    $this->createLiveComponent(ProductWriterHost::class)->callAction('write');

    expect(array_map(static fn (Product $product): string => $product->name, $this->entityManager()->getRepository(Product::class)->findAll()))->toBe(['write']);
});

it('answers a string action from call() as the live action it names, running nothing', function (): void {
    expect(Action::make('save')->action('time')->call())->toBe('time');
});

it('binds its click to the live action a string names, and a closure action to mounting itself', function (Closure $look, string $selector): void {
    expect($this->renderViewComponent($look(Action::make('save')->action('callMountedAction'))))
        ->toContainElement($selector . Selectors::LIVE_CLICK . Selectors::liveParameter('action', 'callMountedAction'))
        ->and($this->renderViewComponent($look(Action::make('save')->action(static fn (): null => null))))
        ->toContainElement($selector . Selectors::LIVE_CLICK . Selectors::liveParameter('action', 'mountAction') . Selectors::liveParameter('name', 'save'));
})->with('trigger looks');

it('wires no live click or dispatch when its click handler is switched off or it submits a form, and wires either even for a form submit when switched on', function (Closure $look, string $selector): void {
    $off = $this->renderViewComponent($look(Action::make('save')->liveComponentClickHandlerEnabled(false)));
    $dispatchOff = $this->renderViewComponent($look(Action::make('save')->dispatch('saved')->liveComponentClickHandlerEnabled(false)));
    $submit = $this->renderViewComponent($look(Action::make('save')->submit('order-form')));

    expect($off)->toContainElement(Selectors::TRIGGER . $selector)
        ->and($off->filter(Selectors::LIVE_CLICK))->toHaveCount(0)
        ->and($dispatchOff)->toContainElement(Selectors::TRIGGER . $selector)
        ->and($dispatchOff->filter(Selectors::dispatchedEvent('saved')))->toHaveCount(0)
        ->and($submit)->toContainElement(Selectors::TRIGGER . $selector . '[type="submit"]')
        ->and($submit->filter(Selectors::LIVE_CLICK))->toHaveCount(0)
        ->and($this->renderViewComponent($look(Action::make('save')->submit('order-form')->liveComponentClickHandlerEnabled(true))))
        ->toContainElement($selector . '[type="submit"]' . Selectors::LIVE_CLICK . Selectors::liveParameter('action', 'mountAction'))
        ->and($this->renderViewComponent($look(Action::make('save')->submit('order-form')->dispatch('saved')->liveComponentClickHandlerEnabled(true))))
        ->toContainElement($selector . '[type="submit"]' . Selectors::dispatchedEvent('saved'));
})->with('trigger looks');

describe('click precedence', function (): void {
    it('calls the live action a string names over dispatching its event', function (Closure $look, string $selector): void {
        $render = $this->renderViewComponent($look(Action::make('save')->action('callMountedAction')->dispatch('saved')));

        expect($render)->toContainElement($selector . Selectors::LIVE_CLICK . Selectors::liveParameter('action', 'callMountedAction'))
            ->and($render->filter(Selectors::dispatchedEvent('saved')))->toHaveCount(0);
    })->with([
        'a button' => [static fn (Action $action): Action => $action, SupportSelectors::BUTTON],
        'an icon button' => [static fn (Action $action): Action => $action->iconButton(), SupportSelectors::ICON_BUTTON],
    ]);

    it("calls its parent's live action over closing its modal", function (Closure $look, string $selector): void {
        $render = $this->renderViewComponent($look(Action::make('save')->callParent('callMountedAction')->close()));

        expect($render)->toContainElement($selector . Selectors::LIVE_CLICK . Selectors::liveParameter('action', 'callMountedAction'))
            ->and($render->filter(SupportSelectors::MODAL_DISMISS))->toHaveCount(0);
    })->with([
        'a button' => [static fn (Action $action): Action => $action, SupportSelectors::BUTTON],
        'an icon button' => [static fn (Action $action): Action => $action->iconButton(), SupportSelectors::ICON_BUTTON],
    ]);

    it('links away over every click it would otherwise wire', function (Closure $look, string $selector): void {
        $render = $this->renderViewComponent($look(Action::make('save')->url('/back')->action('callMountedAction')->dispatch('saved')->close()));

        expect($render)->toContainElement('a[href="/back"]' . $selector)
            ->and($render->filter(Selectors::LIVE_CLICK))->toHaveCount(0)
            ->and($render->filter(SupportSelectors::MODAL_DISMISS))->toHaveCount(0)
            ->and($render->filter(Selectors::dispatchedEvent('saved')))->toHaveCount(0);
    })->with([
        'a button' => [static fn (Action $action): Action => $action, SupportSelectors::BUTTON],
        'an icon button' => [static fn (Action $action): Action => $action->iconButton(), SupportSelectors::ICON_BUTTON],
    ]);
});
