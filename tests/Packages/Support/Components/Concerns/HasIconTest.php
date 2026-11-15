<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;

it("shows its icon, the component's default when none is set, and none when set blank", function (): void {
    $trigger = 'button[aria-label="Actions"]';

    expect($this->renderViewComponent(ActionGroup::make([Action::make('edit')])->icon('support:star')))
        ->toContainElement("{$trigger} svg", 'Star')
        ->and($this->renderViewComponent(ActionGroup::make([Action::make('edit')])))
        ->toContainElement("{$trigger} svg", 'ellipsis-vertical-20-solid');

    foreach (['', null] as $blank) {
        $rendered = $this->renderViewComponent(ActionGroup::make([Action::make('edit')])->icon($blank));

        expect($rendered)->toContainElement($trigger)
            ->and($rendered->filter("{$trigger} svg")->count())->toBe(0);
    }
});
