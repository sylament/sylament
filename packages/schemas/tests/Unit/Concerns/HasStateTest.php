<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Concerns;

use DateTimeImmutable;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Components\Text;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\Casts\BooleanStateCast;
use Sylament\Schemas\State\Casts\DateTimeStateCast;
use Sylament\Schemas\State\Casts\EnumStateCast;
use Sylament\Schemas\Tests\Fixtures\Components\MutatingField;
use Sylament\Schemas\Tests\Fixtures\Components\StateField;
use Sylament\Schemas\Tests\Fixtures\Components\StateGroup;
use Sylament\Schemas\Tests\Fixtures\Concerns\MultiSchemaHostFixture;
use Sylament\Schemas\Tests\Fixtures\Concerns\RecordFixture;
use Sylament\Schemas\Tests\Fixtures\Concerns\StateHostFixture;
use Sylament\Schemas\Tests\Fixtures\State\Author;
use Sylament\Schemas\Tests\Fixtures\State\Casts\CastStatus;
use Sylament\Schemas\Tests\Fixtures\State\Post;

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

it('nulls a defaultless leaf and applies a defaulted sibling on a defaults fill', function (): void {
    $host = new StateHostFixture();
    $host->data = ['author' => ['name' => 'Ada', 'surname' => 'Lovelace']];

    $schema = Schema::make($host)->statePath('data')->components([
        StateGroup::make()->statePath('author')->components([
            StateField::make('name')->default('Anonymous'),
            StateField::make('surname'),
        ]),
    ]);

    $schema->fill();

    expect($host->data)->toBe(['author' => ['name' => 'Anonymous', 'surname' => null]]);
});

it('keeps a group default a defaultless child would otherwise clobber', function (): void {
    $host = new StateHostFixture();

    $schema = Schema::make($host)->statePath('data')->components([
        StateGroup::make()->statePath('author')->default(['name' => 'Ada'])->components([
            StateField::make('name'),
            StateField::make('surname'),
        ]),
    ]);

    $schema->fill();

    expect($host->data)->toBe(['author' => ['name' => 'Ada', 'surname' => null]]);
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

/*
 * The object-value skip exempts an object leaf only where the component's own
 * state casts flatten it out of the bag. The bag is a writable `array` LiveProp
 * and `LiveComponentHydrator::isValueValidDehydratedValue()` refuses an object
 * anywhere inside one, so a leaf whose casts leave the instance in place must
 * skip and null-fill rather than reach dehydration.
 */

it('reads a backed enum whose component casts it to a scalar', function (): void {
    $record = new RecordFixture();
    $record->setStatus(CastStatus::Published);

    $field = StateField::make('status')->stateCast(new EnumStateCast(CastStatus::class));
    $schema = Schema::make(new StateHostFixture())->statePath('data')->components([$field]);

    $schema->fill($record);

    expect($schema->getRawState())->toBe(['status' => 'published'])
        ->and($field->getState())->toBe(CastStatus::Published);
});

it('skips a backed enum no state cast can carry, leaving no object in the bag', function (): void {
    $record = new RecordFixture();
    $record->setTitle('Hello');
    $record->setStatus(CastStatus::Published);

    $schema = Schema::make(new StateHostFixture())
        ->statePath('data')
        ->components([StateField::make('status'), StateField::make('author')]);

    $schema->fill($record);

    expect($schema->getRawState())->toBe(['status' => null, 'author' => null]);
});

it('reads a date-time leaf whose component casts it to a string', function (): void {
    $record = new RecordFixture();
    $record->setPublishedAt(new DateTimeImmutable('2026-08-18 14:30:00'));

    $field = StateField::make('publishedAt')->stateCast(new DateTimeStateCast(
        format: 'Y-m-d H:i:s',
        internalFormat: 'Y-m-d\TH:i',
        timezone: date_default_timezone_get(),
        objectClass: DateTimeImmutable::class,
    ));
    $schema = Schema::make(new StateHostFixture())->statePath('data')->components([$field]);

    $schema->fill($record);

    expect($schema->getRawState())->toBe(['publishedAt' => '2026-08-18T14:30'])
        ->and($field->getState())->toEqual(new DateTimeImmutable('2026-08-18 14:30:00'));
});

it('skips a date-time leaf no state cast can carry', function (): void {
    $record = new RecordFixture();
    $record->setPublishedAt(new DateTimeImmutable('2026-08-18 14:30:00'));

    $schema = Schema::make(new StateHostFixture())
        ->statePath('data')
        ->components([StateField::make('publishedAt')]);

    $schema->fill($record);

    expect($schema->getRawState())->toBe(['publishedAt' => null]);
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

it('fills a nested object path through the record graph', function (): void {
    $post = new Post();
    $post->setAuthor(new Author(name: 'Ada', handle: '@ada'));

    $host = new StateHostFixture();
    Schema::make($host)->statePath('data')->components([
        StateField::make('title'),
        Section::make('Author')->statePath('author')->components([
            StateField::make('name'),
            StateField::make('handle'),
        ]),
    ])->fill($post);

    expect($host->data['author'])->toBe(['name' => 'Ada', 'handle' => '@ada']);
});

it('fills null past a null nested object without throwing', function (): void {
    $host = new StateHostFixture();
    Schema::make($host)->statePath('data')->components([
        Section::make('Author')->statePath('author')->components([
            StateField::make('name'),
        ]),
    ])->fill(new Post());

    expect($host->data['author'])->toBe(['name' => null]);
});

it('fills a leaf decorated with a path-less child schema', function (): void {
    $post = new Post();
    $post->setTitle('Hello');
    $post->setAuthor(new Author(name: 'Ada', handle: '@ada'));

    $host = new StateHostFixture();
    Schema::make($host)->statePath('data')->components([
        StateField::make('title')->childComponents([Text::make('Some help')], 'belowContent'),
        Section::make('Author')->statePath('author')->components([
            StateField::make('name'),
        ]),
    ])->fill($post);

    expect($host->data)->toBe(['title' => 'Hello', 'author' => ['name' => 'Ada']]);
});

it('fills an array-backed branch by declared leaves only', function (): void {
    $post = new Post();
    $post->setMeta(['seo' => 'Hi', 'unmanaged' => 'secret']);

    $host = new StateHostFixture();
    Schema::make($host)->statePath('data')->components([
        Section::make('Meta')->statePath('meta')->components([
            StateField::make('seo'),
        ]),
    ])->fill($post);

    expect($host->data['meta'])->toBe(['seo' => 'Hi']);
});

it('fills a numeric-string path from a record without a type error', function (): void {
    $schema = Schema::make(new StateHostFixture())
        ->statePath('data')
        ->components([StateField::make('0')]);

    $schema->fill(new Post());

    expect($schema->getRawState())->toBe(['0' => null]);
});

it('mutates only the paths the map holds, through the owning component, children first', function (): void {
    $host = new StateHostFixture();
    $schema = Schema::make($host)->statePath('data')->components([
        MutatingField::make('title')->mutateStateForValidationUsing(fn (mixed $state): string => mb_strtoupper((string) $state)),
        Section::make('Group')->components([
            MutatingField::make('inner')->trim(),
        ]),
        MutatingField::make('untouched'),
    ]);
    $schema->getComponents();

    $state = $schema->mutateStateForValidation(['data.title' => 'abc', 'data.inner' => '  x ', 'data.untouched' => ' y ']);

    expect($state)->toBe(['data.title' => 'ABC', 'data.inner' => 'x', 'data.untouched' => ' y ']);
});

it('leaves a path the map does not hold alone', function (): void {
    $host = new StateHostFixture();
    $schema = Schema::make($host)->statePath('data')->components([
        MutatingField::make('title')->trim(),
    ]);
    $schema->getComponents();

    expect($schema->mutateStateForValidation(['data.other' => ' y ']))->toBe(['data.other' => ' y ']);
});

it('does not mutate a component that is neither dehydrated nor validated', function (): void {
    $host = new StateHostFixture();
    $schema = Schema::make($host)->statePath('data')->components([
        MutatingField::make('title')->trim()->dehydrated(false)->validatedWhenNotDehydrated(false),
    ]);
    $schema->getComponents();

    expect($schema->mutateStateForValidation(['data.title' => ' x ']))->toBe(['data.title' => ' x ']);
});
