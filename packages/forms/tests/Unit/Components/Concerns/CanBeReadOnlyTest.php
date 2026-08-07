<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Unit\Components\Concerns;

use Sylament\Forms\Components\Textarea;
use Sylament\Forms\Components\TextInput;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Schemas\Schema;
use Sylament\Support\Enums\Operation;

it('is not read-only by default', function (): void {
    expect(TextInput::make('title')->isReadOnly())->toBeFalse();
});

it('round-trips the read-only flag', function (): void {
    expect(TextInput::make('title')->readOnly()->isReadOnly())->toBeTrue()
        ->and(TextInput::make('title')->readOnly(false)->isReadOnly())->toBeFalse();
});

it('resolves a closure read-only condition through evaluate', function (): void {
    expect(TextInput::make('title')->readOnly(fn (): bool => true)->isReadOnly())->toBeTrue();
});

it('matches the container operation from readOnlyOn()', function (): void {
    $attach = fn (TextInput $field): TextInput => Schema::make(new FieldRenderHost())
        ->operation('edit')
        ->components([$field])
        ->getComponents()[0];

    expect($attach(TextInput::make('title')->readOnlyOn('edit'))->isReadOnly())->toBeTrue()
        ->and($attach(TextInput::make('title')->readOnlyOn(Operation::Edit))->isReadOnly())->toBeTrue()
        ->and($attach(TextInput::make('title')->readOnlyOn(['create', Operation::Edit]))->isReadOnly())->toBeTrue()
        ->and($attach(TextInput::make('title')->readOnlyOn(['create', 'view']))->isReadOnly())->toBeFalse();
});

it('matches the host class from readOnlyOn()', function (): void {
    $field = Schema::make(new FieldRenderHost())
        ->components([TextInput::make('title')->readOnlyOn(FieldRenderHost::class)])
        ->getComponents()[0];

    expect($field->isReadOnly())->toBeTrue();
});

it('is composed on the text fields', function (string $class): void {
    expect(method_exists($class, 'readOnly'))->toBeTrue()
        ->and(method_exists($class, 'readOnlyOn'))->toBeTrue();
})->with([TextInput::class, Textarea::class]);
