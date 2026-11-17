<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Tests\Fixtures\Actions\Selectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

describe('payload attributes', function (): void {
    it('dispatches its event with its payload on click in place of a live click, marking a closing one', function (Closure $look, string $selector): void {
        $withPayload = $this->renderViewComponent($look(Action::make('refresh')->dispatch('refreshed', ['id' => 7])));
        $bare = $this->renderViewComponent($look(Action::make('refresh')->dispatch('refreshed')));
        $closing = $this->renderViewComponent($look(Action::make('refresh')->dispatch('refreshed')->close()));

        expect($withPayload->filter($selector . Selectors::dispatchedEvent('refreshed'))->attr('data-sylament-action-event-data'))->toBe('{"id":7}')
            ->and($withPayload->filter(Selectors::LIVE_CLICK))->toHaveCount(0)
            ->and($withPayload->filter(Selectors::CLOSE_MARKER))->toHaveCount(0)
            ->and($bare)->toContainElement($selector . Selectors::dispatchedEvent('refreshed') . ':not([data-sylament-action-event-data])')
            ->and($closing)->toContainElement($selector . Selectors::dispatchedEvent('refreshed') . Selectors::CLOSE_MARKER)
            ->and($closing->filter(Selectors::DISMISS))->toHaveCount(0);
    })->with('trigger looks');

    it('dispatches to itself or to the component it names, as its last dispatch call says', function (Closure $look, string $selector): void {
        $event = $selector . Selectors::dispatchedEvent('refreshed');

        expect($this->renderViewComponent($look(Action::make('refresh')->dispatchSelf('refreshed'))))
            ->toContainElement($event . '[data-sylament-action-dispatch-self]:not(' . Selectors::dispatchedTo() . ')')
            ->and($this->renderViewComponent($look(Action::make('refresh')->dispatchTo('Cart', 'refreshed'))))
            ->toContainElement($event . Selectors::dispatchedTo('Cart') . ':not([data-sylament-action-dispatch-self])')
            ->and($this->renderViewComponent($look(Action::make('refresh')->dispatchTo('Cart', 'refreshed')->dispatch('refreshed'))))
            ->toContainElement($event . ':not(' . Selectors::dispatchedTo() . '):not([data-sylament-action-dispatch-self])');
    })->with('trigger looks');

    it('dispatches nothing from a disabled icon button', function (): void {
        $render = $this->renderViewComponent(Action::make('refresh')->iconButton()->dispatch('refreshed')->disabled());

        expect($render)->toContainElement('button[disabled]' . SupportSelectors::ICON_BUTTON)
            ->and($render->filter(Selectors::dispatchedEvent('refreshed')))->toHaveCount(0);
    });
});
