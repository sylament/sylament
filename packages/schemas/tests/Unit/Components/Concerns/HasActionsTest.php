<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Components\Concerns;

use Sylament\Actions\Action;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Tests\Fixtures\Components\AffixActionField;
use Sylament\Schemas\Tests\Fixtures\Components\ExtraItemActionsContainer;

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

it('warms the actions cache when resolving the main action by blank name', function (): void {
    $component = new Component();
    $action = Action::make('save');
    $component->action($action);

    expect($component->getAction()->getSchemaComponent())->toBe($component);
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

it("descends a dotted name into a registered action's modal actions", function (): void {
    $component = new Component();
    $component->registerActions([
        Action::make('save')->extraModalFooterActions([Action::make('confirm')->label('Really?')]),
    ]);

    $modalAction = $component->getAction('save.confirm');

    expect($modalAction)->toBeInstanceOf(Action::class)
        ->and($modalAction->getName())->toBe('confirm');
});

it('accepts the array form of a dotted modal-action path', function (): void {
    $component = new Component();
    $component->registerActions([
        Action::make('save')->extraModalFooterActions([Action::make('confirm')]),
    ]);

    expect($component->getAction(['save', 'confirm'])?->getName())->toBe('confirm');
});

it('walks every dotted segment through getModalAction()', function (): void {
    $component = new Component();
    $component->registerActions([
        Action::make('save')->extraModalFooterActions([
            Action::make('confirm')->extraModalFooterActions([Action::make('really')]),
        ]),
    ]);

    expect($component->getAction('save.confirm.really')?->getName())->toBe('really');
});

it('splices affix actions between the default and registered actions', function (): void {
    $component = new class extends AffixActionField {
        public function getDefaultActions(): array
        {
            return [Action::make('defaultOne')];
        }
    };
    $component
        ->prefixActions(['prefixOne' => Action::make('prefixOne')])
        ->suffixActions(['suffixOne' => Action::make('suffixOne')])
        ->registerActions([Action::make('registered')]);

    expect(array_keys($component->getActions()))
        ->toBe(['defaultOne', 'prefixOne', 'suffixOne', 'registered']);
});

it('lets a registered action override a same-named affix action', function (): void {
    $component = new AffixActionField();
    $component
        ->prefixActions(['save' => Action::make('save')->label('Prefix')])
        ->registerActions([Action::make('save')->label('Registered')]);

    expect($component->getActions()['save']->getLabel())->toBe('Registered');
});

it('lets a suffix action override a same-named prefix action', function (): void {
    $component = new AffixActionField();
    $component
        ->prefixActions(['save' => Action::make('save')->label('Prefix')])
        ->suffixActions(['save' => Action::make('save')->label('Suffix')]);

    expect($component->getActions()['save']->getLabel())->toBe('Suffix');
});

it('resolves an affix action by name through getAction()', function (): void {
    $component = new AffixActionField();
    $createOption = Action::make('createOption');
    $component->suffixActions(['createOption' => $createOption]);

    expect($component->getAction('createOption'))->toBe($createOption)
        ->and($component->hasAction('createOption'))->toBeTrue();
});

it('returns null when a dotted tail does not resolve', function (): void {
    $component = new Component();
    $component->registerActions([Action::make('save')]);

    expect($component->getAction('save.missing'))->toBeNull()
        ->and($component->getAction('missing.confirm'))->toBeNull();
});

it('splices the extra item actions into the action cache between the affix and registered actions', function (): void {
    $component = ExtraItemActionsContainer::make('rows')
        ->extraItemActions([Action::make('preview')])
        ->registerActions([Action::make('registered')]);

    expect($component->getAction('preview'))->toBeInstanceOf(Action::class)
        ->and(array_keys($component->getActions()))->toBe(['preview', 'registered']);
});

it('lets a registered action of the same name win, as Filament orders them', function (): void {
    $component = ExtraItemActionsContainer::make('rows')
        ->extraItemActions([Action::make('preview')->label('Extra')])
        ->registerActions([Action::make('preview')->label('Registered')]);

    expect($component->getAction('preview')?->getLabel())->toBe('Registered');
});
