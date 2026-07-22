<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Concerns;

use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Schema;

it('is root without a parent component', function (): void {
    expect(new Schema()->isRoot())->toBeTrue();
});

it('is not root once attached to a parent component', function (): void {
    $parent = new Component();
    $schema = new Schema()->parentComponent($parent);

    expect($schema->isRoot())->toBeFalse()
        ->and($schema->getParentComponent())->toBe($parent);
});

it('becomes root again when the parent component is cleared', function (): void {
    $schema = new Schema()->parentComponent(new Component())->parentComponent(null);

    expect($schema->isRoot())->toBeTrue()
        ->and($schema->getParentComponent())->toBeNull();
});
