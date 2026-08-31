<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Concerns;

use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Components\StateField;
use Sylament\Schemas\Tests\Fixtures\Components\StateGroup;
use Sylament\Schemas\Tests\Fixtures\Components\SubtreeOwner;
use Sylament\Schemas\Tests\Fixtures\Concerns\StateHostFixture;

/*
 * The discovery seam a subtree owner routes its nested relationship hooks from,
 * and answers the mapper's routing vouch from — one walk, so the two cannot
 * drift apart.
 */

it('keys every hook it finds by the path relative to this schema', function (): void {
    $schema = Schema::make(new StateHostFixture())
        ->statePath('data')
        ->components([
            StateField::make('author')->loadStateFromRelationshipsUsing(fn (): null => null),
            StateGroup::make()
                ->statePath('nested')
                ->components([
                    StateField::make('author')->saveRelationshipsUsing(fn (): null => null),
                ]),
        ]);

    expect(array_keys($schema->getNestedRelationshipHooks()))->toBe(['author', 'nested.author']);
});

it('takes a hooked nested owner as one entry and none of the hooks beneath it', function (): void {
    $owner = SubtreeOwner::make('meta')
        ->loadStateFromRelationshipsUsing(fn (): null => null)
        ->components([
            StateField::make('author')->loadStateFromRelationshipsUsing(fn (): null => null),
        ]);

    $schema = Schema::make(new StateHostFixture())
        ->statePath('data')
        ->components([$owner]);

    expect($schema->getNestedRelationshipHooks())->toBe(['meta' => $owner]);
});

it('skips a component declaring no hook and one carrying no state path', function (): void {
    $schema = Schema::make(new StateHostFixture())
        ->statePath('data')
        ->components([
            StateField::make('title'),
            StateGroup::make()->loadStateFromRelationshipsUsing(fn (): null => null),
        ]);

    expect($schema->getNestedRelationshipHooks())->toBe([]);
});

it('lets the last declaration win a shared key', function (): void {
    $first = StateField::make('author')->loadStateFromRelationshipsUsing(fn (): null => null);
    $last = StateField::make('author')->loadStateFromRelationshipsUsing(fn (): null => null);

    $schema = Schema::make(new StateHostFixture())
        ->statePath('data')
        ->components([$first, $last]);

    expect($schema->getNestedRelationshipHooks())->toBe(['author' => $last]);
});

it('finds nothing in a schema declaring no hook at all', function (): void {
    $schema = Schema::make(new StateHostFixture())
        ->statePath('data')
        ->components([
            StateGroup::make()
                ->statePath('nested')
                ->components([StateField::make('author')]),
        ]);

    expect($schema->getNestedRelationshipHooks())->toBe([]);
});
