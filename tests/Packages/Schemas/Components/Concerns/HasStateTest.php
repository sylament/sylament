<?php

declare(strict_types=1);

use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Components\Group;
use Sylament\Tests\Fixtures\Schemas\Selectors;
use Sylament\Tests\Fixtures\Schemas\StateForm;

describe('state path', function (): void {
    it('binds a field under the state path of every ancestor that has one, and of no pathless one', function (): void {
        expect($this->renderSchemaComponent(Group::make([
            Group::make([TextInput::make('city')])->statePath('address'),
            Group::make([TextInput::make('zip')]),
        ])))
            ->toContainElement('input' . Selectors::boundTo('data.address.city'))
            ->toContainElement('input' . Selectors::boundTo('data.zip'));
    });

    it('binds a field under the state path it is given after its path was read', function (): void {
        $field = TextInput::make('city');

        expect($this->renderSchemaComponent($field))->toContainElement('input' . Selectors::boundTo('data.city'));

        $field->statePath('town');

        expect($this->renderSchemaComponent($field))->toContainElement('input' . Selectors::boundTo('data.town'));
    });
});

describe('defaults fill', function (): void {
    it('writes each default over the state, nulls a field with none, and keeps the default of a group from its own fields', function (): void {
        $this->createLiveComponent(StateForm::class)
            ->fillForm(['author' => ['name' => 'Grace', 'surname' => 'Hopper'], 'title' => 'Notes'])
            ->call('fillDefaults')
            ->assertFormSet(['author' => ['name' => 'Ada', 'surname' => null], 'title' => null]);
    });

    it('formats the state a fill writes', function (): void {
        $this->createLiveComponent(StateForm::class)
            ->call('fillDefaults')
            ->assertFormSet(['nickname' => 'Ada']);
    });
});

describe('dehydration', function (): void {
    it('saves a path a saved field shares with an unsaved one, empty or not, and leaves out the fields of an unsaved layout component', function (): void {
        $filled = $this->createLiveComponent(StateForm::class)
            ->fillForm(['tag' => 'urgent', 'note' => 'private'])
            ->assertFormSet(['note' => 'private'])
            ->component()->getSchema('form')->getStateSnapshot();

        $empty = $this->createLiveComponent(StateForm::class)->component()->getSchema('form')->getStateSnapshot();

        expect($filled)->toHaveKey('tag', 'urgent')
            ->not->toHaveKey('note')
            ->and(array_key_exists('tag', $empty))->toBeTrue()
            ->and($empty['tag'])->toBeNull();
    });
});

describe('validation mutation', function (): void {
    it('validates the mutated value and leaves the one the client wrote in the state', function (): void {
        $this->createLiveComponent(StateForm::class)
            ->fillForm(['code' => '   '])
            ->call('save')
            ->assertHasFormErrors(['code'])
            ->assertFormSet(['code' => '   '])
            ->fillForm(['code' => ' X1 '])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertFormSet(['code' => ' X1 ']);
    });
});
