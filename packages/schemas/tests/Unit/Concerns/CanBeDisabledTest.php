<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Concerns;

use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Schema;

it('is enabled by default', function (): void {
    expect(new Schema()->isDisabled())->toBeFalse()
        ->and(new Schema()->isEnabled())->toBeTrue();
});

it('disables on a literal condition', function (): void {
    expect(new Schema()->disabled()->isDisabled())->toBeTrue()
        ->and(new Schema()->disabled()->isEnabled())->toBeFalse();
});

it('accepts a literal false condition', function (): void {
    expect(new Schema()->disabled(false)->isDisabled())->toBeFalse();
});

it('evaluates a closure condition', function (): void {
    expect(new Schema()->disabled(fn (): bool => true)->isDisabled())->toBeTrue();
});

it('inherits disabled from its parent component', function (): void {
    $parent = new Component()->disabled();
    $schema = new Schema()->parentComponent($parent);

    expect($schema->isDisabled())->toBeTrue();
});

it('lets its own disabled(false) be overridden by a disabled parent component', function (): void {
    $parent = new Component()->disabled();
    $schema = new Schema()->disabled(false)->parentComponent($parent);

    expect($schema->isDisabled())->toBeTrue();
});
