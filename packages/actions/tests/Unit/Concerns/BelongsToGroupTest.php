<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit\Concerns;

use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;

it('has no group by default', function (): void {
    expect(Action::make('edit')->getGroup())->toBeNull();
});

it('holds a group back-reference', function (): void {
    $group = ActionGroup::make([]);
    $action = Action::make('edit')->group($group);

    expect($action->getGroup())->toBe($group);
});
