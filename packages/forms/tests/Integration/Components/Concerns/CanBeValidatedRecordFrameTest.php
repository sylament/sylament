<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components\Concerns;

use Sylament\Forms\Components\TextInput;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Forms\Tests\Fixtures\Records\Article;
use Sylament\Forms\Tests\Fixtures\Records\Author;
use Sylament\Forms\Tests\Fixtures\Records\GroupItem;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Schema;

/*
 * Derived requirement across a record frame: the class and the path the type
 * reader is asked for are both taken from the nearest node binding a record of
 * its own, so a section that re-roots its subtree answers for the fields inside
 * it while everything outside keeps reading the root. The frames here are
 * hand-built, so none of this depends on the repeater or the entangled owner
 * that stamp the same binding in a real schema.
 */

beforeEach(function (): void {
    // Boot before the first make(): a container-built field carries the injected
    // type reader rather than the container-less fallback.
    $this::getContainer();
});

it('derives required from the class the nearest frame binds', function (): void {
    $schema = Schema::make(new FieldRenderHost())
        ->statePath('data')
        ->record(Article::class)
        ->components([
            Section::make('writer')
                ->statePath('writer')
                ->record(Author::class)
                ->components([TextInput::make('name')]),
        ]);

    $schema->rawState(['writer' => ['name' => null]]);

    expect($schema->getComponentByStatePath('data.writer.name')->isRequired())->toBeTrue();
});

it('stops reading the root once a frame re-roots the subtree', function (): void {
    $schema = Schema::make(new FieldRenderHost())
        ->statePath('data')
        ->record(Article::class)
        ->components([
            Section::make('author')
                ->statePath('author')
                ->record(GroupItem::class)
                ->components([TextInput::make('name')]),
        ]);

    $schema->rawState(['author' => ['name' => null]]);

    // The root would answer `author.name` with Author::$name, which is non-nullable;
    // the frame answers `name` with GroupItem::$name, which is not.
    expect($schema->getComponentByStatePath('data.author.name')->isRequired())->toBeFalse();
});

it('keeps deriving from the root for a field outside the frame', function (): void {
    $schema = Schema::make(new FieldRenderHost())
        ->statePath('data')
        ->record(Article::class)
        ->components([
            TextInput::make('title'),
            TextInput::make('subtitle'),
            Section::make('writer')
                ->statePath('writer')
                ->record(Author::class)
                ->components([TextInput::make('name')]),
        ]);

    $schema->rawState(['title' => null, 'subtitle' => null, 'writer' => ['name' => null]]);

    expect($schema->getComponentByStatePath('data.title')->isRequired())->toBeTrue()
        ->and($schema->getComponentByStatePath('data.subtitle')->isRequired())->toBeFalse();
});

it('derives nothing for a field whose path escapes the frame', function (): void {
    $schema = Schema::make(new FieldRenderHost())
        ->record(Article::class)
        ->components([TextInput::make('title')]);

    $field = $schema->getComponents()[0];

    // Memoise the field's absolute path before the frame has one of its own, so
    // the two no longer nest — the shape the frame path's null arm exists for.
    $field->getStatePath();

    $schema->statePath('data');

    expect($field->getStatePath())->toBe('title')
        ->and($schema->getStatePath())->toBe('data')
        ->and($field->isRequired())->toBeFalse();
});
