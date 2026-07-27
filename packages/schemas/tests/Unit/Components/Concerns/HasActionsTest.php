<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Components\Concerns;

use Sylament\Actions\Action;
use Sylament\Schemas\Components\Component;

it('keys registered actions by name', function (): void {
    $component = new Component();
    $save = Action::make('save');
    $cancel = Action::make('cancel');
    $component->registerActions([$save, $cancel]);

    expect($component->getActions())->toBe(['save' => $save, 'cancel' => $cancel]);
});

it('evaluates closure action entries lazily with component injection', function (): void {
    $component = new Component();
    $received = null;
    $component->registerActions([
        function (Component $component) use (&$received): Action {
            $received = $component;

            return Action::make('lazy');
        },
    ]);

    expect($received)->toBeNull();

    $component->getActions();

    expect($received)->toBe($component);
});

it('lets a duplicate action name win by last registration', function (): void {
    $component = new Component();
    $component->registerActions([
        Action::make('save')->label('First'),
        Action::make('save')->label('Second'),
    ]);

    expect($component->getActions()['save']->getLabel())->toBe('Second');
});

it('lets a registered action override a same-named default action', function (): void {
    $component = new class extends Component {
        public function getDefaultActions(): array
        {
            return [Action::make('save')->label('Default')];
        }
    };
    $component->registerActions([Action::make('save')->label('Registered')]);

    expect($component->getActions()['save']->getLabel())->toBe('Registered');
});

it('stamps the schema component onto every prepared action', function (): void {
    $component = new Component();
    $action = Action::make('save');
    $component->registerActions([$action]);

    $prepared = $component->getActions()['save'];

    expect($prepared)->toBe($action)
        ->and($prepared->getSchemaComponent())->toBe($component);
});

it('sets the main action and registers it', function (): void {
    $component = new Component();
    $action = Action::make('save');

    $component->action($action);

    expect($component->getAction())->toBe($action)
        ->and($component->getActions()['save'])->toBe($action);
});

it('resolves getAction by default to the main action, by name, and by miss', function (): void {
    $component = new Component();
    $main = Action::make('primary');
    $other = Action::make('secondary');
    $component->action($main);
    $component->registerActions([$other]);

    expect($component->getAction())->toBe($main)
        ->and($component->getAction('secondary'))->toBe($other)
        ->and($component->getAction('missing'))->toBeNull();
});

it('reports hasAction by presence in the actions cache', function (): void {
    $component = new Component();
    $component->registerActions([Action::make('save')]);

    expect($component->hasAction('save'))->toBeTrue()
        ->and($component->hasAction('missing'))->toBeFalse();
});

it('memoizes the actions cache across repeated calls', function (): void {
    $component = new Component();
    $calls = 0;
    $component->registerActions([
        function () use (&$calls): Action {
            $calls++;

            return Action::make('save');
        },
    ]);

    $component->getActions();
    $component->getActions();

    expect($calls)->toBe(1);
});
