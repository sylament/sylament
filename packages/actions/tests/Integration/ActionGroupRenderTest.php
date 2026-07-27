<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration;

use LogicException;
use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;

function renderGroup(ActionGroup $group): string
{
    return (string) $group->render(test()->getContainer()->get('twig'));
}

it('renders the dropdown with trigger and grouped items', function (): void {
    $html = renderGroup(ActionGroup::make([Action::make('edit'), Action::make('delete')]));

    expect($html)->toContain('sy sy-dropdown')
        ->toContain('data-controller="sylament--support--dropdown"')
        ->toContain('sy sy-icon-btn')
        ->toContain('sy-dropdown-item')
        ->toContain('data-live-name-param="edit"')
        ->toContain('data-live-name-param="delete"');
});

it('feeds dropdown configuration into the primitive', function (): void {
    $html = renderGroup(
        ActionGroup::make([Action::make('edit')])
            ->dropdownPlacement('top-end')
            ->dropdownOffset(4)
            ->dropdownWidth('16rem'),
    );

    expect($html)->toContain('data-sylament--support--dropdown-placement-value="top-end"')
        ->toContain('data-sylament--support--dropdown-offset-value="4"')
        ->toContain('--sy-dropdown-width: 16rem;');
});

it('skips hidden items', function (): void {
    $html = renderGroup(ActionGroup::make([Action::make('edit'), Action::make('delete')->hidden()]));

    expect($html)->toContain('data-live-name-param="edit"')
        ->not->toContain('data-live-name-param="delete"');
});

it('renders an inline row under dropdown(false)', function (): void {
    $html = renderGroup(ActionGroup::make([Action::make('edit')])->dropdown(false));

    expect($html)->toContain('sy-ac-group')
        ->toContain('sy sy-btn sy-ac')
        ->not->toContain('sy-dropdown-panel');
});

it('refuses to render a nested group', function (): void {
    renderGroup(ActionGroup::make([ActionGroup::make([Action::make('edit')])]));
})->throws(LogicException::class, 'Submenu');
