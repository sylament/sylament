<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Concerns;

use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Components\StateField;
use Sylament\Schemas\Tests\Fixtures\Concerns\StateHostFixture;
use Sylament\Schemas\Tests\Fixtures\State\Post;

/*
 * Schema::loadStateFromRelationships() — Filament's name, with its parameter
 * dropped: a host calls it once its flush has run, so every relationship-backed
 * component reads its state back off the bound record and nothing else in the
 * bag moves.
 */

it('reads a relationship-backed component back off the bound record and leaves every other path alone', function (): void {
    $post = new Post();
    $post->setTitle('Seeded');

    $host = new StateHostFixture();
    $schema = Schema::make($host)->statePath('data')->record($post)->components([
        StateField::make('title'),
        StateField::make('meta')->loadStateFromRelationshipsUsing(static fn (Post $record): array => ['read' => $record->getTitle()]),
    ]);
    $host->cacheSchema('schema', $schema);
    $schema->fill($post);

    expect($host->data)->toBe(['title' => 'Seeded', 'meta' => ['read' => 'Seeded']]);

    $post->setTitle('Saved');
    $host->data['title'] = 'Edited since';

    $schema->loadStateFromRelationships();

    expect($host->data)->toBe(['title' => 'Edited since', 'meta' => ['read' => 'Saved']]);
});

it('hydrates what it reads without the component default and runs its after-hydrated hook again', function (): void {
    $post = new Post();
    $hydrated = 0;

    $host = new StateHostFixture();
    $schema = Schema::make($host)->statePath('data')->record($post)->components([
        StateField::make('meta')
            ->default(['read' => 'default'])
            ->loadStateFromRelationshipsUsing(static fn (Post $record): ?array => $record->getTitle() === null ? null : ['read' => $record->getTitle()])
            ->afterStateHydrated(static function () use (&$hydrated): void {
                $hydrated++;
            }),
    ]);
    $host->cacheSchema('schema', $schema);
    $schema->fill($post);
    $hydrated = 0;

    $post->setTitle('Saved');
    $schema->loadStateFromRelationships();

    expect($host->data['meta'])->toBe(['read' => 'Saved'])
        ->and($hydrated)->toBe(1);
});

it('reads nothing for a schema bound to no record', function (?string $binding): void {
    $calls = 0;

    $host = new StateHostFixture();
    $schema = Schema::make($host)->statePath('data')->record($binding)->components([
        StateField::make('meta')->loadStateFromRelationshipsUsing(static function () use (&$calls): array {
            $calls++;

            return ['read' => 'loaded'];
        }),
    ]);
    $host->cacheSchema('schema', $schema);
    $host->data = ['meta' => ['read' => 'kept']];

    $schema->loadStateFromRelationships();

    expect($calls)->toBe(0)
        ->and($host->data)->toBe(['meta' => ['read' => 'kept']]);
})->with([
    'nothing bound' => [null],
    'a class declared, no instance' => [Post::class],
]);
