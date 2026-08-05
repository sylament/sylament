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

it('resolves the outermost group as the root group', function (): void {
    $inner = ActionGroup::make([$action = Action::make('edit')]);
    $outer = ActionGroup::make([$inner]);

    expect($action->getRootGroup())->toBe($outer)
        ->and($inner->getRootGroup())->toBe($outer)
        ->and($outer->getRootGroup())->toBeNull();
});

it('has no root group without a group', function (): void {
    expect(Action::make('lone')->getRootGroup())->toBeNull();
});
