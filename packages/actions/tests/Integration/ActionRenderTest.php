<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration;

use Sylament\Actions\Action;

function renderAction(Action $action): string
{
    return (string) $action->render(test()->getContainer()->get('twig'));
}

it('renders a styled trigger button', function (): void {
    $html = renderAction(Action::make('delete')->color('danger')->icon('heroicons:x-mark')->size('sm'));

    expect($html)->toContain('sy sy-btn sy-ac sy-size-sm sy-color sy-color-danger')
        ->toContain('<svg')
        ->toContain('type="button"')
        ->toContain('Delete');
});

it('wires the trigger button to the mountAction live action', function (): void {
    $html = renderAction(Action::make('delete'));

    expect($html)->toContain('data-action="live#action"')
        ->toContain('data-live-action-param="mountAction"')
        ->toContain('data-live-name-param="delete"');
});

it('renders a url action as a link without mount wiring', function (): void {
    $html = renderAction(Action::make('edit')->url('/users/1/edit')->openUrlInNewTab());

    expect($html)->toContain('<a')
        ->toContain('href="/users/1/edit"')
        ->toContain('target="_blank"')
        ->not->toContain('live#action')
        ->not->toContain('type=');
});

it('renders a disabled button', function (): void {
    $html = renderAction(Action::make('delete')->disabled());

    expect($html)->toContain(' disabled')
        ->toContain('aria-disabled="true"')
        ->toContain('sy-disabled');
});
