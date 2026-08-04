<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Components\Concerns;

use Sylament\Actions\Action;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Concerns\HasSchemasFixture;

it('gives the clone its own child component instances', function (): void {
    $host = new HasSchemasFixture();

    $child = new Component()->statePath('name');
    $section = Section::make('Profile')->components([$child]);
    Schema::make($host)->statePath('data')->components([$section])->getComponents();

    $clone = $section->getClone();
    Schema::make($host)->statePath('other')->components([$clone])->getComponents();

    $clonedChild = $clone->getChildSchema()?->getComponents()[0];

    expect($clonedChild)->toBeInstanceOf(Component::class)
        ->and($clonedChild)->not->toBe($child)
        ->and($section->getChildSchema()?->getComponents()[0])->toBe($child);

    $clonedChild->statePath('renamed');

    expect($child->getStatePath(isAbsolute: false))->toBe('name')
        ->and($clonedChild->getStatePath(isAbsolute: false))->toBe('renamed');
});

it('shallow-clones action children of a keyed slot', function (): void {
    $action = Action::make('save');
    $section = Section::make('Profile')->afterHeader([$action]);
    Schema::make()->components([$section])->getComponents();

    $clone = $section->getClone();
    Schema::make()->components([$clone])->getComponents();

    $clonedAction = $clone->getChildSchema(Section::AFTER_HEADER_SCHEMA_KEY)?->getComponents()[0];

    expect($clonedAction)->toBeInstanceOf(Action::class)
        ->and($clonedAction)->not->toBe($action)
        ->and($section->getChildSchema(Section::AFTER_HEADER_SCHEMA_KEY)?->getComponents()[0])->toBe($action);
});

it('flushes the cached absolute state path and keys on the clone', function (): void {
    $host = new HasSchemasFixture();

    $original = new Component()->statePath('name')->key('name-field');
    Schema::make($host)->statePath('data')->key('first')->components([$original])->getComponents();

    expect($original->getStatePath())->toBe('data.name')
        ->and($original->getKey())->toBe('first.name-field')
        ->and($original->getInheritanceKey())->toBe('first.name-field');

    $clone = $original->getClone();
    Schema::make($host)->statePath('other')->key('second')->components([$clone])->getComponents();

    expect($clone->getStatePath())->toBe('other.name')
        ->and($clone->getKey())->toBe('second.name-field')
        ->and($clone->getInheritanceKey())->toBe('second.name-field')
        ->and($original->getStatePath())->toBe('data.name')
        ->and($original->getKey())->toBe('first.name-field')
        ->and($original->getInheritanceKey())->toBe('first.name-field');
});

it('runs afterCloned callbacks bound to the clone with clone and original injections', function (): void {
    $received = [];
    $bound = null;

    $original = new Component()->afterCloned(function (Component $clone, Component $original) use (&$received, &$bound): void {
        $received = ['clone' => $clone, 'original' => $original];
        $bound = $this;
    });

    $clone = $original->getClone();

    expect($received['clone'] ?? null)->toBe($clone)
        ->and($received['original'] ?? null)->toBe($original)
        ->and($bound)->toBe($clone);
});
