<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Components\Concerns;

use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Schema;

it('is its own root when the container has no parent component', function (): void {
    $schema = new Schema();
    $component = new Component()->container($schema);

    expect($component->getRootContainer())->toBe($schema);
});

it('resolves the root container through the parent component chain', function (): void {
    $root = new Schema();
    $parent = new Component()->container($root);
    $child = new Schema()->parentComponent($parent);
    $component = new Component()->container($child);

    expect($component->getRootContainer())->toBe($root);
});

it('memoizes the resolved root container', function (): void {
    $root = new Schema();
    $parent = new Component()->container($root);
    $child = new Schema()->parentComponent($parent);
    $component = new Component()->container($child);

    $first = $component->getRootContainer();

    expect($component->getRootContainer())->toBe($first);
});
