<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit\Concerns;

use Sylament\Actions\Action;
use Sylament\Actions\Tests\Fixtures\Twig\Components\ActionHost;

it('has no pending redirect by default', function (): void {
    expect(Action::make('save')->getPendingRedirectUrl())->toBeNull();
});

it('stores the redirect url a dispatch resolves, fluently', function (): void {
    $action = Action::make('save')->successRedirectUrl('/after');

    expect($action->dispatchSuccessRedirect())->toBe($action)
        ->and($action->getPendingRedirectUrl())->toBe('/after');
});

it('evaluates a closure redirect url', function (): void {
    $action = Action::make('save')
        ->successRedirectUrl(fn (Action $action): string => '/after/' . $action->getName())
        ->dispatchSuccessRedirect();

    expect($action->getPendingRedirectUrl())->toBe('/after/save');
});

it('dispatches the failure url separately from the success url', function (): void {
    $action = Action::make('save')
        ->successRedirectUrl('/ok')
        ->failureRedirectUrl('/nope')
        ->dispatchFailureRedirect();

    expect($action->getPendingRedirectUrl())->toBe('/nope');
});

it('stores an explicit redirect without a dispatch', function (): void {
    $action = Action::make('save');
    $action->redirect('/elsewhere');

    expect($action->getPendingRedirectUrl())->toBe('/elsewhere');
});

it('no-ops when neither the action nor a live component supplies a url', function (): void {
    $detached = Action::make('save')->dispatchSuccessRedirect();
    $hosted = Action::make('save')->liveComponent(new ActionHost())->dispatchFailureRedirect();

    expect($detached->getPendingRedirectUrl())->toBeNull()
        ->and($hosted->getPendingRedirectUrl())->toBeNull();
});

it('falls back to the host default when the action carries no url', function (): void {
    $action = Action::make('save')->liveComponent(new ActionHost());

    expect($action->getLiveComponent()->getDefaultActionSuccessRedirectUrl($action))->toBeNull()
        ->and($action->getLiveComponent()->getDefaultActionFailureRedirectUrl($action))->toBeNull();
});

it('clears the pending redirect on demand', function (): void {
    $action = Action::make('save')->successRedirectUrl('/x');
    $action->dispatchSuccessRedirect();
    $action->resetPendingRedirect();

    expect($action->getPendingRedirectUrl())->toBeNull();
});
