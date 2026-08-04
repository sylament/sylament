<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Concerns;

use Sylament\Actions\Action;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Concerns\HasSchemasFixture;

it('deep-clones components and resets the component cache', function (): void {
    $component = new Component();
    $action = Action::make('save');

    $schema = Schema::make(new HasSchemasFixture())->components([$component, $action]);
    $schema->getComponents();

    $clone = $schema->getClone();
    $clonedComponents = $clone->getComponents();

    expect($clonedComponents[0])->toBeInstanceOf(Component::class)
        ->and($clonedComponents[0])->not->toBe($component)
        ->and($clonedComponents[1])->toBeInstanceOf(Action::class)
        ->and($clonedComponents[1])->not->toBe($action)
        ->and($schema->getComponents()[0])->toBe($component)
        ->and($schema->getComponents()[1])->toBe($action);
});

it('leaves the original stamped as the container of its own components', function (): void {
    $component = new Component();

    $schema = Schema::make(new HasSchemasFixture())->components([$component]);
    $schema->getComponents();

    $clone = $schema->getClone();
    $clonedComponent = $clone->getComponents()[0];

    expect($component->getContainer())->toBe($schema)
        ->and($clonedComponent->getContainer())->toBe($clone);
});

it('leaves a closure component definition alone', function (): void {
    $schema = Schema::make(new HasSchemasFixture())->components(fn (): array => [new Component()]);
    $schema->getComponents();

    $clone = $schema->getClone();

    expect($clone->getComponents())->toHaveCount(1)
        ->and($clone->getComponents()[0]::class)->toBe(Component::class)
        ->and($clone->getComponents()[0])->not->toBe($schema->getComponents()[0]);
});

it('deep-clones the child components of a cloned component', function (): void {
    $child = new Component();
    $parent = new Component()->components([$child]);

    $schema = Schema::make(new HasSchemasFixture())->components([$parent]);
    $schema->getComponents();

    $clone = $schema->getClone();
    $clonedParent = $clone->getComponents()[0];

    expect($clonedParent->getChildSchema()?->getComponents()[0])->not->toBe($child)
        ->and($parent->getChildSchema()?->getComponents()[0])->toBe($child);
});

it('flushes the cached absolute key, inheritance key and state path on the clone', function (): void {
    $host = new HasSchemasFixture();

    $first = new Component()->key('first')->statePath('first');
    $second = new Component()->key('second')->statePath('second');
    Schema::make($host)->components([$first, $second])->getComponents();

    $schema = Schema::make($host)->key('items')->statePath('items')->parentComponent($first);

    expect($schema->getKey())->toBe('first.items')
        ->and($schema->getInheritanceKey())->toBe('first.items')
        ->and($schema->getStatePath())->toBe('first.items');

    $clone = $schema->getClone()->parentComponent($second);

    expect($clone->getKey())->toBe('second.items')
        ->and($clone->getInheritanceKey())->toBe('second.items')
        ->and($clone->getStatePath())->toBe('second.items')
        ->and($schema->getKey())->toBe('first.items')
        ->and($schema->getInheritanceKey())->toBe('first.items')
        ->and($schema->getStatePath())->toBe('first.items');
});

it('runs afterCloned callbacks bound to the clone with clone and original injections', function (): void {
    $received = [];
    $bound = null;

    $original = Schema::make(new HasSchemasFixture())
        ->afterCloned(function (Schema $clone, Schema $original) use (&$received, &$bound): void {
            $received = ['clone' => $clone, 'original' => $original];
            $bound = $this;
        });

    $clone = $original->getClone();

    expect($received['clone'] ?? null)->toBe($clone)
        ->and($received['original'] ?? null)->toBe($original)
        ->and($bound)->toBe($clone);
});
