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
        ->toContain('data-live-name-param="delete"')
        ->toContain('data-sylament--support--dropdown-placement-value="bottom-start"')
        ->toContain('data-sylament--support--dropdown-offset-value="8"');
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

it('renders fine when a nested group is hidden', function (): void {
    $html = renderGroup(ActionGroup::make([
        Action::make('edit'),
        ActionGroup::make([Action::make('archive')])->hidden(),
    ]));

    expect($html)->toContain('data-live-name-param="edit"');
});

it('refuses to render a visible nested group under dropdown(false) too', function (): void {
    renderGroup(ActionGroup::make([ActionGroup::make([Action::make('edit')])])->dropdown(false));
})->throws(LogicException::class, 'Submenu');

it('renders extra dropdown attributes on the dropdown root, not the trigger', function (): void {
    $html = renderGroup(
        ActionGroup::make([Action::make('edit')])
            ->extraDropdownAttributes(['data-y' => '2', 'class' => 'custom-dropdown']),
    );

    expect($html)->toContain('class="sy sy-dropdown custom-dropdown" data-y="2"')
        ->and(mb_substr_count($html, 'data-y="2"'))->toBe(1);
});

it('keeps trigger extra attributes off the dropdown root', function (): void {
    $html = renderGroup(ActionGroup::make([Action::make('edit')])->extraAttributes(['data-x' => '1']));

    expect(mb_substr_count($html, 'data-x="1"'))->toBe(1)
        ->and(mb_strpos($html, 'data-x="1"'))->toBeGreaterThan(mb_strpos($html, 'sy-dropdown-trigger'));
});
