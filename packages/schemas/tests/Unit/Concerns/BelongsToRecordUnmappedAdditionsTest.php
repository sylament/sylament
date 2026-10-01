<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Concerns;

use Closure;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Components\StateField;
use Sylament\Schemas\Tests\Fixtures\Components\StateGroup;
use Sylament\Schemas\Tests\Fixtures\Components\SubtreeOwner;
use Sylament\Schemas\Tests\Fixtures\Concerns\StateHostFixture;
use Sylament\Schemas\Tests\Fixtures\State\Post;

/*
 * findUnmappedAdditions(): what a write seam's hook added to the state it was
 * handed that no component of the schema maps — every leaf path the hook
 * introduced, a mapped branch it replaced with something that is no array
 * included, less every path a component maps or sits beneath. No statement runs.
 */

function unmappedAdditionsSchema(Component ...$components): Schema
{
    return Schema::make(new StateHostFixture())->statePath('data')->record(new Post())->components($components);
}

it('answers the leaf paths a hook added that no component maps', function (Closure $components, array $before, array $after, array $expected): void {
    expect(unmappedAdditionsSchema(...$components())->findUnmappedAdditions($before, $after))->toBe($expected);
})->with([
    'a key no component maps' => [fn (): array => [StateField::make('title')], ['title' => 'a'], ['title' => 'a', 'status' => 'draft'], ['status']],
    'an overwrite of a mapped key' => [fn (): array => [StateField::make('title')], ['title' => 'a'], ['title' => 'A'], []],
    'a key the state already held, passed through' => [fn (): array => [StateField::make('title')], ['title' => 'a', 'injected' => 'x'], ['title' => 'a', 'injected' => 'x'], []],
    'a key the state already held, overwritten' => [fn (): array => [StateField::make('title')], ['title' => 'a', 'injected' => 'x'], ['title' => 'a', 'injected' => 'y'], []],
    'a key added under a mapped branch' => [fn (): array => [StateGroup::make()->statePath('address')->components([StateField::make('street')])], ['address' => ['street' => 'a']], ['address' => ['street' => 'a', 'zip' => '1']], ['address.zip']],
    'a whole branch added, one leaf of it mapped' => [fn (): array => [StateGroup::make()->statePath('address')->components([StateField::make('street')])], [], ['address' => ['street' => 'a', 'zip' => '1']], ['address.zip']],
    'a mapped branch replaced with a string' => [fn (): array => [StateGroup::make()->statePath('address')->components([StateField::make('street')])], ['address' => ['street' => 'a']], ['address' => 'gone'], ['address']],
    'a branch an owner maps, replaced with null' => [fn (): array => [SubtreeOwner::make('meta')->components([StateField::make('seo')])], ['meta' => ['seo' => 'x']], ['meta' => null], []],
    'a key added beneath a branch an owner maps' => [fn (): array => [SubtreeOwner::make('meta')->components([StateField::make('seo')])], ['meta' => ['seo' => 'x']], ['meta' => ['seo' => 'x', 'extra' => true]], []],
    'a key a hidden component maps' => [fn (): array => [StateField::make('title'), StateField::make('secret')->hidden()], ['title' => 'a'], ['title' => 'a', 'secret' => 's'], []],
    'a key a component that does not dehydrate maps' => [fn (): array => [StateField::make('title'), StateField::make('secret')->dehydrated(false)], ['title' => 'a'], ['title' => 'a', 'secret' => 's'], []],
    'an empty array added' => [fn (): array => [StateField::make('title')], ['title' => 'a'], ['title' => 'a', 'tags' => []], ['tags']],
    'a list added under no component' => [fn (): array => [StateField::make('title')], [], ['items' => [['name' => 'x']]], ['items.0.name']],
    'a scalar replaced by an array' => [fn (): array => [StateField::make('title')], ['title' => 'a', 'note' => 'b'], ['title' => 'a', 'note' => ['body' => 'b']], ['note.body']],
    'several, in the order the hook left them' => [fn (): array => [StateField::make('title')], ['title' => 'a'], ['b' => 1, 'title' => 'A', 'a' => 2], ['b', 'a']],
]);

it('reports a mapped branch a hook replaced with null', function (): void {
    $schema = unmappedAdditionsSchema(StateGroup::make()->statePath('address')->components([StateField::make('street'), StateField::make('city')]));

    expect($schema->findUnmappedAdditions(['address' => ['street' => 'Main', 'city' => 'Brno']], ['address' => null]))->toBe(['address']);
});

it('leaves a removed key and a mapped branch emptied to an empty array unanswered', function (): void {
    $schema = unmappedAdditionsSchema(StateField::make('title'), StateGroup::make()->statePath('address')->components([StateField::make('street')]));

    expect($schema->findUnmappedAdditions(['title' => 'a', 'address' => ['street' => 'Main']], ['address' => []]))->toBe([]);
});
