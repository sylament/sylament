<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit;

use LogicException;
use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;

it('keeps its actions and back-references each one', function (): void {
    $edit = Action::make('edit');
    $delete = Action::make('delete');
    $group = ActionGroup::make([$edit, $delete]);

    expect($group->getActions())->toBe([$edit, $delete])
        ->and($edit->getGroup())->toBe($group)
        ->and($delete->getGroup())->toBe($group);
});

it('builds an eager name-keyed flat map', function (): void {
    $edit = Action::make('edit');
    $delete = Action::make('delete');

    expect(ActionGroup::make([$edit, $delete])->getFlatActions())
        ->toBe(['edit' => $edit, 'delete' => $delete]);
});

it('recurses nested groups into the flat map', function (): void {
    $edit = Action::make('edit');
    $archive = Action::make('archive');
    $delete = Action::make('delete');
    $group = ActionGroup::make([$edit, ActionGroup::make([$archive, $delete])]);

    expect($group->getFlatActions())
        ->toBe(['edit' => $edit, 'archive' => $archive, 'delete' => $delete]);
});

it('rejects an unnamed action', function (): void {
    ActionGroup::make([new class (null) extends Action {}]);
})->throws(LogicException::class);

it('defaults its label to Actions and accepts an override', function (): void {
    expect(ActionGroup::make([])->getLabel())->toBe('Actions')
        ->and(ActionGroup::make([])->label('More')->getLabel())->toBe('More')
        ->and(ActionGroup::make([])->label(fn (): string => 'Extra')->getLabel())->toBe('Extra');
});

it('falls back to an ellipsis icon that an explicit icon overrides', function (): void {
    expect(ActionGroup::make([])->getIcon(ActionGroup::DEFAULT_ICON))->toBe('heroicons:ellipsis-vertical')
        ->and(ActionGroup::make([])->icon('heroicons:x-mark')->getIcon(ActionGroup::DEFAULT_ICON))->toBe('heroicons:x-mark');
});
