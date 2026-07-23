<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Concerns;

use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Html;
use Sylament\Schemas\Components\Text;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Components\StateField;
use Sylament\Schemas\Tests\Fixtures\Components\StateGroup;
use Sylament\Schemas\Tests\Fixtures\Concerns\HasComponentsFixture;
use Sylament\Schemas\Tests\Fixtures\Concerns\StateHostFixture;
use Twig\Markup;

it('evaluates the closure lazily when reading the components', function (): void {
    $first = new Component();
    $components = [$first];
    $fixture = new HasComponentsFixture()->components(function () use (&$components): array {
        return $components;
    });

    $second = new Component();
    $components = [$first, $second];

    expect($fixture->getComponents())->toBe([$first, $second]);
});

it('assigns itself as the container of each component', function (): void {
    $component = new Component();
    $fixture = new HasComponentsFixture()->components([$component]);

    $fixture->getComponents();

    expect($component->getContainer())->toBe($fixture);
});

it('caches the resolved components', function (): void {
    $fixture = new HasComponentsFixture()->components([new Component()]);

    expect($fixture->getComponents())->toBe($fixture->getComponents());
});

it('rebuilds the cache when components() is called again', function (): void {
    $fixture = new HasComponentsFixture()->components([new Component()]);
    $first = $fixture->getComponents();

    $replacement = [new Component()];
    $fixture->components($replacement);

    expect($fixture->getComponents())->toBe($replacement)
        ->and($fixture->getComponents())->not->toBe($first);
});

it('treats a components closure returning null as empty', function (): void {
    $fixture = new HasComponentsFixture()->components(fn (): ?array => null);

    expect($fixture->getComponents())->toBe([]);
});

it('coerces a string component into a Text component', function (): void {
    $fixture = new HasComponentsFixture()->components(['Hello']);

    $components = $fixture->getComponents();

    expect($components)->toHaveCount(1)
        ->and($components[0])->toBeInstanceOf(Text::class)
        ->and($components[0]->getContent())->toBe('Hello');
});

it('coerces a non-string, non-component value into an Html component', function (): void {
    $markup = new Markup('<b>raw</b>', 'UTF-8');
    $fixture = new HasComponentsFixture()->components([$markup]);

    $components = $fixture->getComponents();

    expect($components[0])->toBeInstanceOf(Html::class)
        ->and($components[0]->getContent())->toBe($markup);
});

it('leaves an existing component untouched while coercing its siblings', function (): void {
    $component = new Component();
    $fixture = new HasComponentsFixture()->components([$component, 'text', new Markup('m', 'UTF-8')]);

    $components = $fixture->getComponents();

    expect($components[0])->toBe($component)
        ->and($components[1])->toBeInstanceOf(Text::class)
        ->and($components[2])->toBeInstanceOf(Html::class);
});

it('wraps and coerces a single non-array component value', function (): void {
    $fixture = new HasComponentsFixture()->components('Just text');

    $components = $fixture->getComponents();

    expect($components)->toHaveCount(1)
        ->and($components[0])->toBeInstanceOf(Text::class);
});

it('assigns itself as the container of a coerced component', function (): void {
    $fixture = new HasComponentsFixture()->components(['text']);

    expect($fixture->getComponents()[0]->getContainer())->toBe($fixture);
});

it('resolves a top-level component by its absolute state path', function (): void {
    $schema = Schema::make(new StateHostFixture())->statePath('data')->components([
        StateField::make('title'),
        StateField::make('subtitle'),
    ]);

    $title = $schema->getComponents()[0];

    expect($schema->getComponentByStatePath('data.title'))->toBe($title);
});

it('resolves a nested component by descending into child schemas', function (): void {
    $schema = Schema::make(new StateHostFixture())->statePath('data')->components([
        StateGroup::make()->statePath('address')->components([StateField::make('city')]),
    ]);

    $found = $schema->getComponentByStatePath('data.address.city');

    expect($found?->getStatePath())->toBe('data.address.city')
        ->and($found?->getStatePath(isAbsolute: false))->toBe('city');
});

it('returns null for a path owned by no component', function (): void {
    $schema = Schema::make(new StateHostFixture())->statePath('data')->components([
        StateField::make('title'),
    ]);

    expect($schema->getComponentByStatePath('data.nope'))->toBeNull();
});

it('does not descend into a skipped component while still matching it exactly', function (): void {
    $schema = Schema::make(new StateHostFixture())->statePath('data')->components([
        StateGroup::make()->statePath('address')->components([StateField::make('city')]),
    ]);

    $group = $schema->getComponents()[0];

    expect($schema->getComponentByStatePath('data.address.city', [$group]))->toBeNull()
        ->and($schema->getComponentByStatePath('data.address', [$group]))->toBe($group);
});
