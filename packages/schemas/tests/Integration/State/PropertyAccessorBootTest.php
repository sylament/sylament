<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Integration\State;

use Sylament\Schemas\Components\Section;
use Sylament\Schemas\State\PropertyAccessor;

it('injects the container accessor into a component built by make()', function (): void {
    $this::getContainer();

    expect(Section::make('x')->getPropertyAccessor())
        ->toBe($this::getContainer()->get(PropertyAccessor::class));
});

it('falls back to a locally built accessor for a component built with new', function (): void {
    $this::getContainer();

    expect(new Section()->getPropertyAccessor())
        ->toBeInstanceOf(PropertyAccessor::class)
        ->not->toBe($this::getContainer()->get(PropertyAccessor::class));
});
