<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Concerns;

use Sylament\Schemas\Schema;
use Sylament\Schemas\State\Casts\BooleanStateCast;
use Sylament\Schemas\Tests\Fixtures\Components\StateField;
use Sylament\Schemas\Tests\Fixtures\Components\StateGroup;
use Sylament\Schemas\Tests\Fixtures\Concerns\MultiSchemaHostFixture;
use Sylament\Schemas\Tests\Fixtures\Concerns\RecordFixture;
use Sylament\Schemas\Tests\Fixtures\Concerns\StateHostFixture;

it('fills the bag and null-fills unset leaves', function (): void {
    $host = new StateHostFixture();

    $host->fill(['title' => 'Hi']);

    expect($host->data)->toBe(['title' => 'Hi', 'subtitle' => null]);
});

it('assembles the dehydrated bag from getState()', function (): void {
    $host = new StateHostFixture();
    $host->fill(['title' => 'Hi', 'subtitle' => 'Yo']);

    expect($host->getSchema()->getState())->toBe(['title' => 'Hi', 'subtitle' => 'Yo']);
});

it('binds multiple schemas on one host to independent state paths', function (): void {
    $host = new MultiSchemaHostFixture();

    $host->getSchema('editForm')->fill(['title' => 'Edit me']);
    $host->getSchema('filterForm')->fill(['search' => 'query']);

    expect($host->editData)->toBe(['title' => 'Edit me'])
        ->and($host->filterData)->toBe(['search' => 'query']);
});

it('drops a component key when dehydrated(false)', function (): void {
    $host = new StateHostFixture();
    $schema = Schema::make($host)->statePath('data')->components([
        StateField::make('title'),
        StateField::make('secret')->dehydrated(false),
    ]);

    $schema->fill(['title' => 'Hi', 'secret' => 'shh']);

    expect($schema->getState())->toBe(['title' => 'Hi']);
});

it('dehydrates cast values as their typed form', function (): void {
    $host = new StateHostFixture();
    $schema = Schema::make($host)->statePath('data')->components([
        StateField::make('flag')->stateCast(new BooleanStateCast(isStoredAsInt: true))->default(true),
    ]);

    $schema->fill();

    expect($host->data['flag'])->toBe(1)
        ->and($schema->getState())->toBe(['flag' => true]);
});

it('caches which components are dehydrated by absolute path', function (): void {
    $host = new StateHostFixture();
    $schema = Schema::make($host)->statePath('data')->components([
        StateField::make('title'),
        StateField::make('secret')->dehydrated(false),
    ]);

    expect($schema->hasDehydratedComponent('data.title'))->toBeTrue()
        ->and($schema->hasDehydratedComponent('data.secret'))->toBeFalse();
});

it('keeps a shared state path when a dehydrated sibling owns it', function (): void {
    $host = new StateHostFixture();
    $schema = Schema::make($host)->statePath('data')->components([
        StateField::make('shared')->dehydrated(false),
        StateField::make('shared'),
    ]);
    $host->data = ['shared' => 'v'];

    expect($schema->getState())->toBe(['shared' => 'v']);
});

it('forgets only its own descendants when a sibling shares the path', function (): void {
    $host = new StateHostFixture();
    $schema = Schema::make($host)->statePath('data')->components([
        StateGroup::make()->statePath('shared')->components([StateField::make('a')])->dehydrated(false),
        StateGroup::make()->statePath('shared')->components([StateField::make('b')])->dehydrated(false),
        StateField::make('keep'),
    ]);
    $host->data = ['shared' => ['a' => 'AA', 'b' => 'BB'], 'keep' => 'KK'];

    expect($schema->getState())->toBe(['keep' => 'KK']);
});

it('assembles a nested (layout-wrapped) schema, dropping a non-dehydrated leaf', function (): void {
    $host = new StateHostFixture();
    $schema = Schema::make($host)->statePath('data')->components([
        StateGroup::make()->components([
            StateField::make('a'),
            StateField::make('b')->dehydrated(false),
        ]),
    ]);
    $host->data = ['a' => 'AA', 'b' => 'BB'];

    expect($schema->getState())->toBe(['a' => 'AA']);
});

it('fills a path-bearing group from an empty bag', function (): void {
    $host = new StateHostFixture();
    $schema = Schema::make($host)->statePath('data')->components([
        StateGroup::make()->statePath('author')->components([
            StateField::make('name'),
            StateField::make('surname'),
        ]),
    ]);

    $schema->fill();

    expect($host->data)->toBe(['author' => ['name' => null, 'surname' => null]])
        ->and($schema->getState())->toBe(['author' => ['name' => null, 'surname' => null]]);
});

it('fills a path-bearing group nested two deep from an empty bag', function (): void {
    $host = new StateHostFixture();
    $schema = Schema::make($host)->statePath('data')->components([
        StateGroup::make()->statePath('a')->components([
            StateGroup::make()->statePath('b')->components([
                StateField::make('c'),
            ]),
        ]),
    ]);

    $schema->fill();

    expect($host->data)->toBe(['a' => ['b' => ['c' => null]]]);
});

it('fills a path-bearing group with supplied nested state', function (): void {
    $host = new StateHostFixture();
    $schema = Schema::make($host)->statePath('data')->components([
        StateGroup::make()->statePath('author')->components([
            StateField::make('name'),
            StateField::make('surname'),
        ]),
    ]);

    $schema->fill(['author' => ['name' => 'Ada']]);

    expect($schema->getState())->toBe(['author' => ['name' => 'Ada', 'surname' => null]]);
});

it('refills a path-bearing group over an already-populated bag', function (): void {
    $host = new StateHostFixture();
    $host->data = ['author' => ['name' => 'Ada', 'surname' => 'Lovelace']];

    $schema = Schema::make($host)->statePath('data')->components([
        StateGroup::make()->statePath('author')->components([
            StateField::make('name'),
            StateField::make('surname'),
        ]),
    ]);

    $schema->fill([]);

    expect($host->data)->toBe(['author' => ['name' => null, 'surname' => null]]);
});

it('reads a leaf under a group path the host holds as null', function (): void {
    $host = new StateHostFixture();
    $host->data = ['author' => null];

    $schema = Schema::make($host)->statePath('data')->components([
        StateGroup::make()->statePath('author')->components([
            StateField::make('name'),
        ]),
    ]);

    $field = $schema->getFlatComponents(withActions: false)[1];

    expect($field->getState())->toBeNull();
});

it('fills a path-bearing group when the record supplies null for it', function (): void {
    $record = new class {
        public ?object $author = null;

        public string $title = 'Hello';
    };

    $host = new StateHostFixture();
    $schema = Schema::make($host)->statePath('data')->components([
        StateField::make('title'),
        StateGroup::make()->statePath('author')->components([
            StateField::make('name'),
        ]),
    ]);

    $schema->fill($record);

    expect($host->data)->toBe(['title' => 'Hello', 'author' => ['name' => null]]);
});

it('mutates dehydrated state through nested schemas', function (): void {
    $host = new StateHostFixture();
    $schema = Schema::make($host)->statePath('data')->components([
        StateGroup::make()->components([
            StateField::make('a')->mutateDehydratedStateUsing(fn ($state): string => "[{$state}]"),
        ]),
    ]);
    $host->data = ['a' => 'x'];

    expect($schema->getState())->toBe(['a' => '[x]']);
});

it('fires beforeStateDehydrated and re-syncs mutated state when asked', function (): void {
    $host = new StateHostFixture();
    $fired = false;
    $schema = Schema::make($host)->statePath('data')->components([
        StateField::make('title')->beforeStateDehydrated(function ($component) use (&$fired): void {
            $fired = true;
            $component->state('rewritten');
        }, shouldUpdateValidatedStateAfter: true),
    ]);
    $schema->fill(['title' => 'original']);

    $state = $schema->getState();

    expect($fired)->toBeTrue()
        ->and($state)->toBe(['title' => 'rewritten']);
});

it('fills state from a record object, scoped to the schema paths', function (): void {
    $record = new RecordFixture();
    $record->setTitle('Hello');
    $record->setPerex('A summary');

    $schema = Schema::make(new StateHostFixture())
        ->statePath('data')
        ->components([StateField::make('title'), StateField::make('perex')]);

    $schema->fill($record);

    expect($schema->getRawState())->toBe(['title' => 'Hello', 'perex' => 'A summary']);
});

it('nulls fields the record cannot supply', function (): void {
    $record = new RecordFixture();
    $record->setTitle('Hello');

    $schema = Schema::make(new StateHostFixture())
        ->statePath('data')
        ->components([StateField::make('title'), StateField::make('subtitle')]);

    $schema->fill($record);

    expect($schema->getRawState())->toBe(['title' => 'Hello', 'subtitle' => null]);
});

it('skips an uninitialized typed property', function (): void {
    $schema = Schema::make(new StateHostFixture())
        ->statePath('data')
        ->components([StateField::make('title')]);

    $schema->fill(new RecordFixture());

    expect($schema->getRawState())->toBe(['title' => null]);
});

it('skips object-valued reads', function (): void {
    $record = new RecordFixture();
    $record->setTitle('Hello');

    $schema = Schema::make(new StateHostFixture())
        ->statePath('data')
        ->components([StateField::make('author'), StateField::make('title')]);

    $schema->fill($record);

    expect($schema->getRawState())->toBe(['title' => 'Hello', 'author' => null]);
});

it('reads array values from the record', function (): void {
    $record = new RecordFixture();
    $record->setTags(['a', 'b']);

    $schema = Schema::make(new StateHostFixture())
        ->statePath('data')
        ->components([StateField::make('tags')]);

    $schema->fill($record);

    expect($schema->getRawState())->toBe(['tags' => ['a', 'b']]);
});

it('still fills defaults when called with null and arrays verbatim', function (): void {
    $schema = Schema::make(new StateHostFixture())
        ->statePath('data')
        ->components([StateField::make('title')->default('Untitled')]);

    $schema->fill();

    expect($schema->getRawState())->toBe(['title' => 'Untitled']);

    $schema->fill(['title' => 'Explicit']);
    expect($schema->getRawState())->toBe(['title' => 'Explicit']);
});
