<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components;

use LogicException;
use Sylament\Actions\Action;
use Sylament\Forms\Components\Repeater;
use Sylament\Forms\Components\TextInput;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Schemas\Schema;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * The structural actions through the real mounted-action pipeline: a trigger's
 * `mountAction(name, arguments, context)` resolves the action from the repeater's
 * `schemaComponent` key, runs it without a modal, and the mutated item map
 * survives the round-trip in the host's `$data`. Items stay uuid-keyed here (no
 * `fake()`), so every test reads the keys back off the host rather than assuming
 * them — which is exactly what a trigger does when it binds `['item' => key]`.
 */

/**
 * The mount payload a repeater trigger emits: the action's name, its per-item
 * arguments, and the repeater's absolute key as the schema-component context.
 *
 * @param array<string, mixed> $arguments
 *
 * @return array{name: string, arguments: array<string, mixed>, context: array<string, string>}
 */
function repeaterMount(string $name, array $arguments = []): array
{
    return [
        'name' => $name,
        'arguments' => $arguments,
        'context' => ['schemaComponent' => 'schema.tags'],
    ];
}

/**
 * Wire a repeater under a `data`-bag host, for the gates that are answered
 * without a round-trip.
 *
 * @param array<string, mixed> $data
 */
function wireActionRepeater(Repeater $repeater, array $data = []): Repeater
{
    $host = new FieldRenderHost();
    $host->data = $data;

    Schema::make($host)->statePath('data')->components([$repeater])->getComponents();

    return $repeater;
}

it('names its structural actions and labels the add trigger from the field label', function (): void {
    $this::getContainer();

    $repeater = wireActionRepeater(Repeater::make('tags')->components([TextInput::make('name')]));

    expect($repeater->getAddActionName())->toBe('add')
        ->and($repeater->getDeleteActionName())->toBe('delete')
        ->and($repeater->getCloneActionName())->toBe('clone')
        ->and($repeater->getMoveUpActionName())->toBe('moveUp')
        ->and($repeater->getMoveDownActionName())->toBe('moveDown')
        ->and($repeater->getAddActionLabel())->toBe('Add to tags')
        ->and($repeater->addActionLabel('Add a tag')->getAddActionLabel())->toBe('Add a tag');
});

it('appends an item and fills its defaults on the add action', function (): void {
    $component = $this->createLiveComponent('RepeaterForm', ['data' => ['tags' => [['name' => 'a']]]])
        ->call('mountAction', repeaterMount('add'));

    $tags = $component->component()->data['tags'];

    expect($tags)->toHaveCount(2)
        ->and(array_values($tags))->toBe([['name' => 'a'], ['name' => 'x']]);
});

it('removes exactly the mounted item on the delete action', function (): void {
    $component = $this->createLiveComponent('RepeaterForm', [
        'data' => ['tags' => [['name' => 'a'], ['name' => 'b'], ['name' => 'c']]],
    ]);

    $keys = array_keys($component->component()->data['tags']);

    $component->call('mountAction', repeaterMount('delete', ['item' => $keys[1]]));

    $tags = $component->component()->data['tags'];

    expect(array_values($tags))->toBe([['name' => 'a'], ['name' => 'c']])
        ->and(array_keys($tags))->toBe([$keys[0], $keys[2]]);
});

it('duplicates the mounted item under a fresh key on the clone action', function (): void {
    $component = $this->createLiveComponent('RepeaterForm', [
        'data' => ['tags' => [['name' => 'a'], ['name' => 'b']]],
        'isCloneable' => true,
    ]);

    $keys = array_keys($component->component()->data['tags']);

    $component->call('mountAction', repeaterMount('clone', ['item' => $keys[0]]));

    $tags = $component->component()->data['tags'];

    expect(array_values($tags))->toBe([['name' => 'a'], ['name' => 'b'], ['name' => 'a']])
        ->and(array_slice(array_keys($tags), 0, 2))->toBe($keys)
        ->and(array_keys($tags)[2])->not->toBeIn($keys);
});

it('swaps whole items on the move actions', function (): void {
    $component = $this->createLiveComponent('RepeaterForm', [
        'data' => ['tags' => [['name' => 'a'], ['name' => 'b'], ['name' => 'c']]],
        'isReorderableWithButtons' => true,
    ]);

    $keys = array_keys($component->component()->data['tags']);

    $component->call('mountAction', repeaterMount('moveDown', ['item' => $keys[0]]));

    expect(array_keys($component->component()->data['tags']))->toBe([$keys[1], $keys[0], $keys[2]])
        ->and(array_values($component->component()->data['tags']))->toBe([['name' => 'b'], ['name' => 'a'], ['name' => 'c']]);

    $component->call('mountAction', repeaterMount('moveUp', ['item' => $keys[0]]));

    expect(array_keys($component->component()->data['tags']))->toBe($keys);
});

it('leaves the item map alone when a move action hits an edge', function (): void {
    $component = $this->createLiveComponent('RepeaterForm', [
        'data' => ['tags' => [['name' => 'a'], ['name' => 'b']]],
        'isReorderableWithButtons' => true,
    ]);

    $keys = array_keys($component->component()->data['tags']);

    $component->call('mountAction', repeaterMount('moveUp', ['item' => $keys[0]]));

    // The hook count is what separates "the edge is a no-op" from "the action
    // never resolved": the body runs either way and always fires the hooks.
    expect(array_keys($component->component()->data['tags']))->toBe($keys)
        ->and($component->component()->stateUpdates)->toBe(1);

    $component->call('mountAction', repeaterMount('moveDown', ['item' => $keys[1]]));

    expect(array_keys($component->component()->data['tags']))->toBe($keys)
        ->and($component->component()->stateUpdates)->toBe(2);
});

it('fires the repeater afterStateUpdated hooks once per structural action', function (): void {
    $component = $this->createLiveComponent('RepeaterForm', ['data' => ['tags' => [['name' => 'a']]]]);

    expect($component->component()->stateUpdates)->toBe(0);

    $component->call('mountAction', repeaterMount('add'));

    expect($component->component()->stateUpdates)->toBe(1);

    $keys = array_keys($component->component()->data['tags']);

    $component->call('mountAction', repeaterMount('delete', ['item' => $keys[0]]));

    expect($component->component()->stateUpdates)->toBe(2);
});

it('closes the add action once maxItems is reached', function (): void {
    $this::getContainer();

    $repeater = wireActionRepeater(
        Repeater::make('tags')->components([TextInput::make('name')])->maxItems(2),
        ['tags' => [['name' => 'a'], ['name' => 'b']]],
    );

    expect($repeater->isAddable())->toBeFalse()
        ->and($repeater->getAction($repeater->getAddActionName())?->isVisible())->toBeFalse();
});

it('closes every structural surface when the field is disabled', function (): void {
    $this::getContainer();

    $repeater = wireActionRepeater(
        Repeater::make('tags')
            ->components([TextInput::make('name')])
            ->cloneable()
            ->reorderableWithButtons()
            ->disabled(),
        ['tags' => [['name' => 'a']]],
    );

    expect($repeater->isAddable())->toBeFalse()
        ->and($repeater->isDeletable())->toBeFalse()
        ->and($repeater->isReorderable())->toBeFalse()
        ->and($repeater->isReorderableWithButtons())->toBeFalse()
        ->and($repeater->isCloneable())->toBeFalse()
        ->and($repeater->getAction($repeater->getDeleteActionName())?->isVisible())->toBeFalse();
});

it('reorders the items to the keys the client sent, appending the ones it did not and dropping unknown ones', function (): void {
    $component = $this->createLiveComponent('RepeaterForm', [
        'data' => ['tags' => [['name' => 'a'], ['name' => 'b'], ['name' => 'c']]],
    ]);
    [$a, $b, $c] = array_keys($component->component()->data['tags']);

    $component->call('mountAction', repeaterMount('reorder', ['items' => [$c, $a, $b]]));
    expect(array_keys($component->component()->data['tags']))->toBe([$c, $a, $b]);

    $component->call('mountAction', repeaterMount('reorder', ['items' => [$b, 'zzz']]));
    expect(array_keys($component->component()->data['tags']))->toBe([$b, $c, $a])
        ->and($component->component()->stateUpdates)->toBe(2);
});

it('refuses a reorder without an ordered key list', function (): void {
    $component = $this->createLiveComponent('RepeaterForm', ['data' => ['tags' => [['name' => 'a']]]]);

    expect(fn () => $component->call('mountAction', repeaterMount('reorder', ['items' => 'a'])))
        ->toThrow(LogicException::class);
});

it('keeps positional keys through a reorder under fake()', function (): void {
    // fake() registers a configureUsing closure on the shared ComponentManager; the
    // live host's make() runs after it, and the first assertion pins that it took.
    Repeater::fake();

    $component = $this->createLiveComponent('RepeaterForm', ['data' => ['tags' => [['name' => 'a'], ['name' => 'b']]]]);

    expect(array_keys($component->component()->data['tags']))->toBe([0, 1]);

    $component->call('mountAction', repeaterMount('reorder', ['items' => ['1', '0']]));

    expect(array_keys($component->component()->data['tags']))->toBe([1, 0])
        ->and(array_values($component->component()->data['tags']))->toBe([['name' => 'b'], ['name' => 'a']]);
});

it('inserts a filled item after the named one on the add-between action', function (): void {
    $component = $this->createLiveComponent('RepeaterForm', ['data' => ['tags' => [['name' => 'a'], ['name' => 'b']]], 'isAddBetweenVisible' => true]);
    [$a, $b] = array_keys($component->component()->data['tags']);

    $component->call('mountAction', repeaterMount('addBetween', ['afterItem' => $a]));
    $tags = $component->component()->data['tags'];

    expect(array_values($tags))->toBe([['name' => 'a'], ['name' => 'x'], ['name' => 'b']])
        ->and(array_keys($tags)[0])->toBe($a)
        ->and(array_keys($tags)[2])->toBe($b);
});

it('leaves a collapsed repeater open for the rest of the request after adding, cloning or inserting', function (): void {
    $repeater = wireActionRepeater(Repeater::make('tags')->collapsed()->components([TextInput::make('name')]), ['tags' => [['name' => 'a']]]);
    $key = array_keys($repeater->getItems())[0];

    expect($repeater->isCollapsed($repeater->getItems()[$key]))->toBeTrue();

    $repeater->getAction('add')?->call();

    expect($repeater->isCollapsible())->toBeTrue()
        ->and($repeater->isCollapsed($repeater->getItems()[$key]))->toBeFalse();
});

it('mounts an extra item action by name with the item key and injects the item\'s state as $schemaState', function (): void {
    $component = $this->createLiveComponent('RepeaterForm', ['data' => ['tags' => [['name' => 'a'], ['name' => 'b']]]]);
    [, $b] = array_keys($component->component()->data['tags']);

    $component->call('mountAction', repeaterMount('inspect', ['item' => $b]));

    expect($component->component()->inspected)->toBe(['name' => 'b']);
});

it('leaves the collapse state open after a clone and an insert as well', function (): void {
    $repeater = wireActionRepeater(Repeater::make('tags')->collapsed()->cloneable()->addBetweenAction(static fn (Action $action): Action => $action->visible())->components([TextInput::make('name')]), ['tags' => [['name' => 'a'], ['name' => 'b']]]);
    [$a] = array_keys($repeater->getItems());

    $repeater->getAction('clone')?->__invoke(['item' => $a])->call();
    expect($repeater->isCollapsed($repeater->getItems()[$a]))->toBeFalse();

    $again = wireActionRepeater(Repeater::make('tags')->collapsed()->addBetweenAction(static fn (Action $action): Action => $action->visible())->components([TextInput::make('name')]), ['tags' => [['name' => 'a'], ['name' => 'b']]]);
    [$first] = array_keys($again->getItems());

    $again->getAction('addBetween')?->__invoke(['afterItem' => $first])->call();
    expect($again->isCollapsed($again->getItems()[$first]))->toBeFalse();
});
