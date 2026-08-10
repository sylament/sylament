<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration;

use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;

function renderDispatchAction(Action|ActionGroup $action): string
{
    return (string) $action->render(test()->getContainer()->get('twig'));
}

it('renders a dispatch action with payload attributes and no live binding', function (): void {
    $html = renderDispatchAction(Action::make('undo')->label('Undo')->dispatch('undoEditingPost', ['post' => 5]));

    expect($html)->toContain('data-sylament-action-event="undoEditingPost"')
        ->toContain('data-sylament-action-event-data=')
        ->not->toContain('data-live-action-param')
        ->not->toContain('mountAction');
});

it('renders dispatchTo with the target component and plain dispatch without it', function (): void {
    expect(renderDispatchAction(Action::make('a')->dispatchTo('App:PostList', 'x')))
        ->toContain('data-sylament-action-dispatch-to="App:PostList"');

    expect(renderDispatchAction(Action::make('a')->dispatch('x')))
        ->not->toContain('data-sylament-action-dispatch-to');
});

it('renders the close marker on a closing dispatch and on a bare close', function (): void {
    expect(renderDispatchAction(Action::make('a')->dispatch('x')->close()))
        ->toContain('data-sylament-action-event="x"')
        ->toContain('data-sylament-action-close');

    expect(renderDispatchAction(Action::make('a')->close()))
        ->toContain('data-sylament-action-close');
});

it('renders event data JSON only when non-empty', function (): void {
    expect(renderDispatchAction(Action::make('a')->dispatch('x')))
        ->not->toContain('data-sylament-action-event-data');
});

it('keeps the url arm winning over dispatch', function (): void {
    $html = renderDispatchAction(Action::make('a')->url('/x')->dispatch('ignored'));

    expect($html)->toContain('href="/x"')->not->toContain('data-sylament-action-event');
});

it('renders the same arms through the icon-button template', function (): void {
    expect(renderDispatchAction(Action::make('a')->iconButton()->icon('heroicons:x-mark')->dispatch('x')->close()))
        ->toContain('data-sylament-action-event="x"')
        ->toContain('data-sylament-action-close')
        ->not->toContain('mountAction');
});

it('renders a link-triggered group', function (): void {
    $html = renderDispatchAction(
        ActionGroup::make()->actions([Action::make('one')])->triggerTemplate(ActionGroup::LINK_TEMPLATE)->label('More'),
    );

    expect($html)->toContain('sy-link');
});
