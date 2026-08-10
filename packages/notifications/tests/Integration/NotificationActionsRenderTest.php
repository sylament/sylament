<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Notifications\Notification;

function renderActionsToast(Notification $notification): string
{
    return $notification->render(test()->getContainer()->get('twig')) ?? '';
}

it('renders the actions slot with payload attrs and the root delegation action', function (): void {
    $html = renderActionsToast(Notification::make('n')->title('T')->actions([
        Action::make('view')->url('/x'),
        Action::make('undo')->dispatch('undo')->close(),
    ]));

    expect($html)->toContain('sy-no-notification-actions')
        ->toContain('href="/x"')
        ->toContain('data-sylament-action-event="undo"')
        ->toContain('data-sylament-action-close')
        ->toContain('sylament--notifications--notification#handleActionClick');
});

it('renders a group in the slot behind a small link trigger', function (): void {
    $html = renderActionsToast(Notification::make('n')->title('T')->actions([
        ActionGroup::make([Action::make('undo')->dispatch('undo')]),
    ]));

    expect($html)->toContain('sy-no-notification-actions')
        ->toContain('sy-dropdown')
        ->toContain('sy-link sy-size-sm');
});

it('renders no actions slot when there are none', function (): void {
    expect(renderActionsToast(Notification::make('n')->title('T')))
        ->not->toContain('sy-no-notification-actions');
});
