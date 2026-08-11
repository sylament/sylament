<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components;

use LogicException;
use Sylament\Forms\Components\Repeater;
use Sylament\Forms\Components\TextInput;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Forms\Tests\Fixtures\Records\TaggedPost;
use Sylament\Schemas\Schema;

/*
 * The uuid half of the item pipeline, which the fake-mode unit suite cannot see:
 * items are keyed by per-request v4 uuids in the live bag and positional on the
 * record, so every absolute state path under a repeater changes on every mount.
 * The kernel is booted so components come out of the container with their
 * `#[Required]` dependencies — the record type reader the early refusal asks.
 */

/**
 * The RFC 4122 v4 shape: version nibble `4`, variant nibble `8`–`b`.
 */
function uuidV4Pattern(): string
{
    return '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/';
}

it('re-keys hydrated items by uuid', function (): void {
    $this::getContainer();

    $host = new FieldRenderHost();
    $host->data = ['tags' => [['name' => 'a'], ['name' => 'b']]];

    $repeater = Repeater::make('tags')->components([TextInput::make('name')]);
    Schema::make($host)->statePath('data')->components([$repeater])->getComponents();

    $repeater->hydrateItems();

    $keys = array_keys($host->data['tags']);

    expect($keys)->toHaveCount(2)
        ->and($keys[0])->toMatch(uuidV4Pattern())
        ->and($keys[1])->toMatch(uuidV4Pattern())
        ->and($keys[0])->not->toBe($keys[1])
        ->and($host->data['tags'])->toBe([$keys[0] => ['name' => 'a'], $keys[1] => ['name' => 'b']]);
});

it('paths each item field under its uuid key', function (): void {
    $this::getContainer();

    $host = new FieldRenderHost();
    $host->data = ['tags' => [['name' => 'a']]];

    $repeater = Repeater::make('tags')->components([TextInput::make('name')]);
    Schema::make($host)->statePath('data')->components([$repeater])->getComponents();

    $repeater->hydrateItems();

    $key = array_key_first($host->data['tags']);
    $items = $repeater->getItems();

    expect(array_keys($items))->toBe([$key])
        ->and($items[$key]->getComponents()[0]->getStatePath())->toBe("data.tags.{$key}.name");
});

it('positionalises the uuid map on dehydration', function (): void {
    $this::getContainer();

    $host = new FieldRenderHost();
    $host->data = ['tags' => [['name' => 'a'], ['name' => 'b']]];

    $schema = Schema::make($host)->statePath('data');
    $repeater = Repeater::make('tags')->components([TextInput::make('name')]);
    $schema->components([$repeater])->getComponents();

    $repeater->hydrateItems();

    expect(array_keys($host->data['tags']))->each->toMatch(uuidV4Pattern())
        ->and($schema->getStateSnapshot())->toBe(['tags' => [['name' => 'a'], ['name' => 'b']]]);
});

it('refuses an object-collection record property through the wired type reader', function (): void {
    $this::getContainer();

    $repeater = Repeater::make('tags')->components([TextInput::make('name')]);

    Schema::make(new FieldRenderHost())
        ->statePath('data')
        ->record(TaggedPost::class)
        ->components([$repeater])
        ->getComponents();

    expect(fn (): mixed => $repeater->hydrateItems())
        ->toThrow(LogicException::class, 'Declare relationship() on this repeater');
});
