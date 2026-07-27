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
