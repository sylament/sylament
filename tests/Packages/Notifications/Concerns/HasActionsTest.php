<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Notifications\Notification;
use Sylament\Tests\Fixtures\Actions\Selectors as ActionsSelectors;
use Sylament\Tests\Fixtures\Notifications\Selectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('renders its actions and groups as small links in the actions row, unless one chose its own look', function (): void {
    $toast = $this->renderViewComponent(Notification::make()->title('Saved')->actions([
        Action::make('view')->url('/articles/1'),
        ActionGroup::make([Action::make('share')->url('/articles/1/share')])->label('More'),
        Action::make('undo')->button(),
    ]));

    expect($toast)
        ->toContainButtons(Selectors::ACTIONS . ' ' . SupportSelectors::SMALL_LINK, ['View', 'More'])
        ->toContainButtons(Selectors::ACTIONS . ' ' . SupportSelectors::BUTTON, ['Undo']);
});

it("teleports a group's dropdown out of the toast unless the group says otherwise, also once sent", function (): void {
    $this->startSessionRequest();

    Notification::make()->title('Saved')->actions([ActionGroup::make([Action::make('share')->url('/share')])])->send();
    $teleported = $this->renderTwigComponent('SylamentNotifications:Notifications')->crawler();

    Notification::make()->title('Saved')->actions([ActionGroup::make([Action::make('share')->url('/share')])->dropdownTeleport(false)])->send();
    $kept = $this->renderTwigComponent('SylamentNotifications:Notifications')->crawler();

    expect($teleported)->toContainElement(Selectors::ACTIONS . ' ' . SupportSelectors::TELEPORTED_DROPDOWN)
        ->and($kept)->toContainElement(Selectors::ACTIONS . ' ' . SupportSelectors::KEPT_DROPDOWN);
});

it("closes the toast from an action that closes, through the toast's own click handler", function (): void {
    $toast = $this->renderViewComponent(Notification::make()->title('Saved')->actions([
        Action::make('undo')->dispatch('undo')->close(),
    ]));

    expect($toast)
        ->toContainElement(Selectors::TOAST_HANDLING_ACTION_CLICKS)
        ->toContainElement(Selectors::ACTIONS . ' ' . ActionsSelectors::CLOSE_MARKER, 'Undo');
});
