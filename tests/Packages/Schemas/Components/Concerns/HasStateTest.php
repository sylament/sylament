<?php

declare(strict_types=1);

use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Components\Group;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Schemas\NoteForm;
use Sylament\Tests\Fixtures\Schemas\OldStateForm;
use Sylament\Tests\Fixtures\Schemas\Selectors;
use Sylament\Tests\Fixtures\Schemas\StateForm;
use Sylament\Tests\Fixtures\Schemas\TaggingStateCast;

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

describe('state casts', function (): void {
    it('stores a value it is given through its casts last cast first, and reads and saves it through them first cast first', function (): void {
        $host = new NoteForm();
        $schema = $host->makeSchema()->statePath('data')->components([
            TextInput::make('code')->stateCast(TaggingStateCast::First)->stateCast(TaggingStateCast::Second),
        ]);
        [$field] = $schema->getComponents();

        $field->state('X1');

        expect($host->data)->toBe(['code' => 'X1 second:set first:set'])
            ->and($field->getState())->toBe('X1 second:set first:set first:get second:get')
            ->and($schema->getStateSnapshot())->toBe(['code' => 'X1 second:set first:set first:get second:get']);
    });

    it('casts a path two fields share once as it fills, through the casts of the first', function (): void {
        $host = new NoteForm();
        $host->makeSchema()->statePath('data')->components([
            TextInput::make('code')->stateCast(TaggingStateCast::First),
            TextInput::make('code')->stateCast(TaggingStateCast::Second),
        ])->fill(['code' => 'X1']);

        expect($host->data)->toBe(['code' => 'X1 first:set']);
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

describe('afterStateUpdated', function (): void {
    it('runs the hooks of a field the client changed, and of a field a hook writes with its hooks asked for', function (): void {
        $tester = $this->createLiveComponent(StateForm::class)
            ->fillForm(['title' => 'Hello World'])
            ->assertFormSet(['slug' => 'hello world']);

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['slug: hello world']);

        $tester->fillForm(['slug' => 'by-hand']);

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['slug: by-hand']);
    });

    it('runs no hook of a field whose value the request left as it was', function (): void {
        $this->createLiveComponent(StateForm::class)
            ->fillForm(['title' => 'Hello World'])
            ->fillForm(['code' => 'X1']);

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe([]);
    });

    it('hands a hook the value its field held before the request, read through its casts and as stored', function (): void {
        $this->createLiveComponent(OldStateForm::class)->fillForm(['motto' => 'After']);

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(["old: Before first:get; raw: 'Before'"]);
    });
});

describe('validation mutation', function (): void {
    it('validates the mutated value and leaves the one the client wrote in the state', function (): void {
        $this->createLiveComponent(StateForm::class)
            ->fillForm(['code' => ' X1 '])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertFormSet(['code' => ' X1 ']);
    });
});
