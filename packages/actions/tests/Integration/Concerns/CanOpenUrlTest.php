<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration\Concerns;

use Sylament\Actions\Action;
use Sylament\Actions\Tests\Fixtures\Concerns\DefaultActionUrlHostFixture;

function renderHostedAction(Action $action): string
{
    return (string) $action->render(test()->getContainer()->get('twig'));
}

it('renders an anchor at the url its host supplies', function (): void {
    $host = new DefaultActionUrlHostFixture();
    $action = $host->getAction('edit');

    expect($action)->not->toBeNull();

    $html = renderHostedAction($action);

    expect($html)->toContain('<a')
        ->toContain('href="/hosted/edit"')
        ->toContain('Edit')
        ->not->toContain('data-live-action-param="mountAction"');
});

it('renders a mount trigger when the host declines to supply a url', function (): void {
    $action = Action::make('publish')->liveComponent(new DefaultActionUrlHostFixture());

    $html = renderHostedAction($action);

    expect($html)->toContain('data-live-action-param="mountAction"')
        ->not->toContain('href=');
});
