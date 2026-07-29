<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Concerns;

use RuntimeException;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Components\StateField;
use Sylament\Schemas\Tests\Fixtures\Concerns\StateHostFixture;

it('returns the explicit operation', function (): void {
    expect(Schema::make(new StateHostFixture())->operation('edit')->getOperation())->toBe('edit');
});

it('falls back to the live component class when unset', function (): void {
    expect(Schema::make(new StateHostFixture())->getOperation())->toBe(StateHostFixture::class);
});

it('throws for a standalone schema with neither operation nor host', function (): void {
    expect(fn (): string => Schema::make()->getOperation())
        ->toThrow(RuntimeException::class, 'Schema is not part of LiveComponent');
});

it('inherits the operation through the child-schema chain', function (): void {
    $host = new Component()->components([new Component()]);

    $attachedHost = Schema::make(new StateHostFixture())
        ->operation('edit')
        ->components([$host])
        ->getComponents()[0];

    expect($attachedHost->getChildSchema()?->getOperation())->toBe('edit');
});

it('injects the operation into component closures', function (): void {
    $field = Schema::make(new StateHostFixture())
        ->operation('edit')
        ->components([StateField::make('title')])
        ->getComponents()[0];

    expect($field->evaluate(fn (string $operation): string => $operation))->toBe('edit')
        ->and($field->evaluate(fn (string $context): string => $context))->toBe('edit');
});
