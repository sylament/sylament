<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration;

use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;

function renderGroupedAction(Action $action): string
{
    return (string) $action->render(test()->getContainer()->get('twig'));
}

it('renders a grouped action as a dropdown item row wired to mountAction', function (): void {
    $delete = Action::make('delete')->icon('heroicons:x-mark');
    ActionGroup::make([$delete])->getActions();

    $html = renderGroupedAction($delete);

    expect($html)->toContain('sy sy-dropdown-item sy-ac')
        ->toContain('<svg')
        ->toContain('Delete')
        ->toContain('type="button"')
        ->toContain('data-action="live#action"')
        ->toContain('data-live-action-param="mountAction"')
        ->toContain('data-live-name-param="delete"')
        ->toContain('sy-dropdown-item-label');
});

it('renders a url grouped action as a link without mount wiring', function (): void {
    $edit = Action::make('edit')->url('/articles/1/edit');
    ActionGroup::make([$edit])->getActions();

    $html = renderGroupedAction($edit);

    expect($html)->toContain('<a')
        ->toContain('href="/articles/1/edit"')
        ->not->toContain('live#action')
        ->not->toContain('type=');
});

it('renders the grouped icon as the icon default inside a dropdown item', function (): void {
    $delete = Action::make('delete')->groupedIcon('heroicons:x-mark');
    ActionGroup::make([$delete])->getActions();

    $html = renderGroupedAction($delete);

    expect($html)->toContain('<svg');
});

it('renders a disabled grouped action', function (): void {
    $delete = Action::make('delete')->disabled();
    ActionGroup::make([$delete])->getActions();

    $html = renderGroupedAction($delete);

    expect($html)->toContain(' disabled')
        ->toContain('aria-disabled="true"')
        ->toContain('sy-disabled');
});

it('renders type="submit" and the wired form id for a submit-capable grouped action', function (): void {
    $save = Action::make('save')->submit('callMountedAction')->formId('my-form');
    ActionGroup::make([$save])->getActions();

    $html = renderGroupedAction($save);

    expect($html)->toContain('type="submit"')
        ->toContain('form="my-form"');
});
