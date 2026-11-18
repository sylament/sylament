<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Panels\Selectors;

it("guards the action changes of a page in a panel that turns the alerts on, a page that composes no data alert guarding no data, and nothing in a panel that leaves them off", function (): void {
    $guarded = $this->visit('/simple-knobs/notes-page');
    $unguarded = $this->visit('/simple-shapes/notes-page');

    expect($this->unsavedChangesGuard($guarded->filter(Selectors::PAGE)))->toBe(['savedHash' => '', 'dataModel' => '', 'guardActions' => 'true'])
        ->and($guarded->filter(Selectors::PAGE)->attr('data-controller'))->toBe('live')
        ->and($unguarded->filter(Selectors::PAGE))->toHaveCount(1)
        ->and($this->unsavedChangesGuard($unguarded))->toBeNull();
});
