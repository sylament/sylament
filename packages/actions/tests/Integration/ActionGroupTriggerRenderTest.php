<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Integration;

use Sylament\Actions\ActionGroup;

function renderTrigger(ActionGroup $group): string
{
    return test()->getContainer()->get('twig')->render($group->getTriggerTemplate(), ['group' => $group]);
}

it('renders the default icon-button trigger with the ellipsis icon and an accessible label', function (): void {
    $html = renderTrigger(ActionGroup::make([]));

    expect($html)->toContain('sy sy-icon-btn')
        ->toContain('<svg')
        ->toContain('aria-label="Actions"');
});

it('renders the labeled button trigger', function (): void {
    $html = renderTrigger(ActionGroup::make([])->button()->label('More'));

    expect($html)->toContain('sy sy-btn')
        ->toContain('More');
});

it('renders group extra attributes on the icon-button trigger root', function (): void {
    $html = renderTrigger(ActionGroup::make([])->extraAttributes(['data-x' => '1', 'class' => 'custom-trigger']));

    expect($html)->toContain('data-x="1"')
        ->toMatch('/class="[^"]*sy-icon-btn[^"]*custom-trigger/');
});

it('renders group extra attributes on the button trigger root', function (): void {
    $html = renderTrigger(ActionGroup::make([])->button()->extraAttributes(['data-x' => '1', 'class' => 'custom-trigger']));

    expect($html)->toContain('data-x="1"')
        ->toMatch('/class="[^"]*sy-btn[^"]*custom-trigger/');
});

it('renders the outlined variant on the button trigger', function (): void {
    $html = renderTrigger(ActionGroup::make([])->button()->outlined());

    expect($html)->toMatch('/class="[^"]*sy-btn[^"]*sy-outlined/');
});

it('token-merges the outlined class with extra attribute classes', function (): void {
    $html = renderTrigger(
        ActionGroup::make([])->button()->outlined()->extraAttributes(['class' => 'custom-trigger']),
    );

    expect($html)->toMatch('/class="[^"]*sy-btn[^"]*sy-outlined[^"]*custom-trigger/');
});
