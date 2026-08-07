<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Unit\Components\Concerns;

use Sylament\Forms\Components\Textarea;
use Sylament\Forms\Components\TextInput;

it('has no autocomplete by default', function (): void {
    expect(TextInput::make('title')->getAutocomplete())->toBeNull();
});

it('maps the autocomplete booleans to on and off', function (): void {
    expect(TextInput::make('title')->autocomplete()->getAutocomplete())->toBe('on')
        ->and(TextInput::make('title')->autocomplete(false)->getAutocomplete())->toBe('off');
});

it('passes an autocomplete token through unchanged', function (): void {
    expect(TextInput::make('title')->autocomplete('given-name')->getAutocomplete())->toBe('given-name')
        ->and(TextInput::make('title')->autocomplete(fn (): string => 'email')->getAutocomplete())->toBe('email');
});

it('is composed on the text fields', function (string $class): void {
    expect(method_exists($class, 'autocomplete'))->toBeTrue();
})->with([TextInput::class, Textarea::class]);
