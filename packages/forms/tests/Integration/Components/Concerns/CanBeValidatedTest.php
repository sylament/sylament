<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components\Concerns;

use Sylament\Forms\Components\TextInput;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Forms\Tests\Fixtures\Records\Article;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\RecordTypeReader;

/*
 * Derived requirement as a whole schema resolves it: real components built by the
 * container, a record bound once on the root schema and inherited down the
 * container chain, and a `nullWhenBlank()` section whose current state decides
 * whether the non-nullable leaves beneath it are required.
 *
 * The constraint-map assertions are the payoff: getData() validates before it
 * maps, so a leaf still carrying NotBlank here could never reach the branch
 * nulling pass.
 */

beforeEach(function (): void {
    // Boot before the first make(): the resolver's static instance is what makes a
    // component container-built, and with it its reader the injected service rather
    // than the container-less fallback.
    $this::getContainer();
});

it('injects the container reader into a field built by make()', function (): void {
    expect(TextInput::make('title')->getRecordTypeReader())
        ->toBe($this::getContainer()->get(RecordTypeReader::class));
});

it('derives required from the record bound on the root schema', function (): void {
    $schema = Schema::make(new FieldRenderHost())
        ->statePath('data')
        ->record(Article::class)
        ->components([TextInput::make('title'), TextInput::make('subtitle')]);

    $schema->rawState(['title' => null, 'subtitle' => null]);

    expect($schema->getComponentByStatePath('data.title')->isRequired())->toBeTrue()
        ->and($schema->getComponentByStatePath('data.subtitle')->isRequired())->toBeFalse();
});

it('does not require a non-nullable leaf under a blank nullable branch', function (): void {
    $schema = Schema::make(new FieldRenderHost())
        ->statePath('data')
        ->record(Article::class)
        ->components([
            TextInput::make('title'),
            Section::make('author')
                ->statePath('author')
                ->nullWhenBlank()
                ->components([TextInput::make('name'), TextInput::make('handle')]),
        ]);

    $schema->rawState(['title' => 'Hello', 'author' => ['name' => null, 'handle' => null]]);

    expect($schema->getComponentByStatePath('data.author.name')->isRequired())->toBeFalse()
        ->and($schema->getComponentByStatePath('data.author.handle')->isRequired())->toBeFalse()
        ->and(array_keys($schema->getValidationConstraints()))->toBe(['data.title']);
});

it('requires a non-nullable leaf once the branch is filled', function (): void {
    $schema = Schema::make(new FieldRenderHost())
        ->statePath('data')
        ->record(Article::class)
        ->components([
            TextInput::make('title'),
            Section::make('author')
                ->statePath('author')
                ->nullWhenBlank()
                ->components([TextInput::make('name'), TextInput::make('handle')]),
        ]);

    $schema->rawState(['title' => 'Hello', 'author' => ['name' => 'Ada', 'handle' => null]]);

    expect($schema->getComponentByStatePath('data.author.handle')->isRequired())->toBeTrue()
        ->and(array_keys($schema->getValidationConstraints()))
        ->toBe(['data.title', 'data.author.name', 'data.author.handle']);
});

it('requires a blank leaf when the branch is not declared nullable', function (): void {
    $schema = Schema::make(new FieldRenderHost())
        ->statePath('data')
        ->record(Article::class)
        ->components([
            Section::make('author')
                ->statePath('author')
                ->components([TextInput::make('name')]),
        ]);

    $schema->rawState(['author' => ['name' => null]]);

    expect($schema->getComponentByStatePath('data.author.name')->isRequired())->toBeTrue();
});

it('ignores a path-less nullWhenBlank() container when suppressing requirement', function (): void {
    // The mapper refuses this declaration outright (it names no property to null),
    // so the only question here is that requirement stops reading the whole bag
    // through it: `title` sits outside the container and must not move `name`.
    $schema = fn (?string $title): Schema => Schema::make(new FieldRenderHost())
        ->statePath('data')
        ->record(Article::class)
        ->components([
            TextInput::make('title'),
            Section::make('author')
                ->nullWhenBlank()
                ->components([TextInput::make('author.name')]),
        ])
        ->rawState(['title' => $title, 'author' => ['name' => null]]);

    expect($schema(null)->getComponentByStatePath('data.author.name')->isRequired())->toBeTrue()
        ->and($schema('Hello')->getComponentByStatePath('data.author.name')->isRequired())->toBeTrue();
});

it('keeps an explicit required() strict inside a blank nullable branch', function (): void {
    $schema = Schema::make(new FieldRenderHost())
        ->statePath('data')
        ->record(Article::class)
        ->components([
            Section::make('author')
                ->statePath('author')
                ->nullWhenBlank()
                ->components([TextInput::make('name')->required(), TextInput::make('handle')]),
        ]);

    $schema->rawState(['author' => ['name' => null, 'handle' => null]]);

    expect($schema->getComponentByStatePath('data.author.name')->isRequired())->toBeTrue()
        ->and($schema->getComponentByStatePath('data.author.handle')->isRequired())->toBeFalse();
});
