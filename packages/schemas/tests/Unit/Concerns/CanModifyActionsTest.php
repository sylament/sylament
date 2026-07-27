<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Concerns;

use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Schemas\Schema;
use TypeError;

it('returns the action unchanged when no modifyActionsUsing callback is registered', function (): void {
    $action = Action::make('save');

    expect(Schema::make()->modifyAction($action))->toBe($action);
});

it('applies the registered modifyActionsUsing callback to the action', function (): void {
    $schema = Schema::make()->modifyActionsUsing(fn (Action $action): Action => $action->label('Modified'));

    expect($schema->modifyAction(Action::make('save'))->getLabel())->toBe('Modified');
});

it('falls back to the original action when the modifyActionsUsing callback returns null', function (): void {
    $action = Action::make('save');
    $schema = Schema::make()->modifyActionsUsing(function (Action $action): void {
    });

    expect($schema->modifyAction($action))->toBe($action);
});

it('replaces the previous modifyActionsUsing callback rather than stacking it', function (): void {
    $schema = Schema::make()
        ->modifyActionsUsing(fn (Action $action): Action => $action->label('First'))
        ->modifyActionsUsing(fn (Action $action): Action => $action->label('Second'));

    expect($schema->modifyAction(Action::make('save'))->getLabel())->toBe('Second');
});

it('invokes the modifyActionsUsing callback plainly rather than through the closure evaluator', function (): void {
    $schema = Schema::make()->modifyActionsUsing(fn (Action $action): Action => $action->label('Plain'));

    expect($schema->modifyAction(Action::make('save'))->getLabel())->toBe('Plain');
});

it('throws when the modifyActionsUsing callback returns something other than an Action or null', function (): void {
    $schema = Schema::make()->modifyActionsUsing(fn (Action $action): string => 'not an action');

    expect(fn (): Action => $schema->modifyAction(Action::make('save')))->toThrow(TypeError::class);
});

it('returns the action group unchanged when no modifyActionGroupsUsing callback is registered', function (): void {
    $group = ActionGroup::make();

    expect(Schema::make()->modifyActionGroup($group))->toBe($group);
});

it('applies the registered modifyActionGroupsUsing callback to the action group', function (): void {
    $schema = Schema::make()->modifyActionGroupsUsing(fn (ActionGroup $group): ActionGroup => $group->label('Modified'));

    expect($schema->modifyActionGroup(ActionGroup::make())->getLabel())->toBe('Modified');
});

it('falls back to the original action group when the modifyActionGroupsUsing callback returns null', function (): void {
    $group = ActionGroup::make();
    $schema = Schema::make()->modifyActionGroupsUsing(function (ActionGroup $group): void {
    });

    expect($schema->modifyActionGroup($group))->toBe($group);
});

it('replaces the previous modifyActionGroupsUsing callback rather than stacking it', function (): void {
    $schema = Schema::make()
        ->modifyActionGroupsUsing(fn (ActionGroup $group): ActionGroup => $group->label('First'))
        ->modifyActionGroupsUsing(fn (ActionGroup $group): ActionGroup => $group->label('Second'));

    expect($schema->modifyActionGroup(ActionGroup::make())->getLabel())->toBe('Second');
});

it('invokes the modifyActionGroupsUsing callback plainly rather than through the closure evaluator', function (): void {
    $schema = Schema::make()->modifyActionGroupsUsing(fn (ActionGroup $group): ActionGroup => $group->label('Plain'));

    expect($schema->modifyActionGroup(ActionGroup::make())->getLabel())->toBe('Plain');
});

it('throws when the modifyActionGroupsUsing callback returns something other than an ActionGroup or null', function (): void {
    $schema = Schema::make()->modifyActionGroupsUsing(fn (ActionGroup $group): string => 'not a group');

    expect(fn (): ActionGroup => $schema->modifyActionGroup(ActionGroup::make()))->toThrow(TypeError::class);
});
