<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration;

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
        ->toContain('--sy-dropdown-width: 16rem');
});

it('skips hidden items', function (): void {
    $html = renderGroup(ActionGroup::make([Action::make('edit'), Action::make('delete')->hidden()]));

    expect($html)->toContain('data-live-name-param="edit"')
        ->not->toContain('data-live-name-param="delete"');
});

it('renders an inline row under dropdown(false)', function (): void {
    $html = renderGroup(ActionGroup::make([Action::make('edit')])->dropdown(false));

    expect($html)->toContain('sy-ac-group')
        ->toContain('sy sy-btn sy-size-md sy-color sy-color-primary sy-ac')
        ->not->toContain('sy-dropdown-panel');
});

it('renders fine when a nested group is hidden', function (): void {
    $html = renderGroup(ActionGroup::make([
        Action::make('edit'),
        ActionGroup::make([Action::make('archive')])->hidden(),
    ]));

    expect($html)->toContain('data-live-name-param="edit"');
});

it('renders a nested group inline when the parent is not a dropdown', function (): void {
    $html = renderGroup(ActionGroup::make([ActionGroup::make([Action::make('edit')])])->dropdown(false));

    expect($html)->toContain('sy-ac-group')
        ->toContain('sy sy-dropdown')
        ->toContain('data-live-name-param="edit"');
});

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

it('renders a nested dropdown group as an embedded submenu instead of throwing', function (): void {
    $html = renderGroup(ActionGroup::make([
        Action::make('one'),
        ActionGroup::make([Action::make('two')])->label('More'),
    ]));

    expect(mb_substr_count($html, 'sy-dropdown-trigger'))->toBe(2)
        ->and($html)->toContain('data-sylament--support--dropdown-placement-value="right-start"')
        ->and($html)->toContain('More');
});

it('renders flattened subgroups as separate dropdown list sections', function (): void {
    $html = renderGroup(ActionGroup::make([
        Action::make('one'),
        ActionGroup::make([Action::make('two'), Action::make('three')])->dropdown(false),
    ]));

    expect(mb_substr_count($html, 'sy-dropdown-list'))->toBe(2)
        ->and(mb_substr_count($html, 'sy-dropdown-trigger'))->toBe(1)
        ->and($html)->toContain('two')
        ->and($html)->toContain('three')
        // Flattened children must render as dropdown items, not standalone buttons:
        // three item labels, and no `sy-btn` anywhere (the group's own trigger is the
        // icon-button template, which emits `sy-icon-btn` — a different string).
        ->and(mb_substr_count($html, 'sy-dropdown-item-label'))->toBe(3)
        ->and($html)->not->toContain('sy-btn');
});

it('renders the nested group trigger as a grouped dropdown item', function (): void {
    $html = renderGroup(ActionGroup::make([
        ActionGroup::make([Action::make('a')])->label('Sub'),
    ]));

    expect($html)->toMatch('/sy-dropdown-item[^>]*>.*Sub/s');
});
