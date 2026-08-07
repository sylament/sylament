<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Unit\Components\Concerns;

use Sylament\Forms\Components\Textarea;
use Sylament\Forms\Components\TextInput;

it('has no autocapitalize by default', function (): void {
    expect(TextInput::make('title')->getAutocapitalize())->toBeNull();
});

it('maps the autocapitalize booleans to on and off', function (): void {
    expect(TextInput::make('title')->autocapitalize()->getAutocapitalize())->toBe('on')
        ->and(TextInput::make('title')->autocapitalize(false)->getAutocapitalize())->toBe('off');
});

it('passes an autocapitalize token through unchanged', function (): void {
    expect(TextInput::make('title')->autocapitalize('words')->getAutocapitalize())->toBe('words')
        ->and(TextInput::make('title')->autocapitalize(fn (): string => 'characters')->getAutocapitalize())->toBe('characters');
});

it('is composed on TextInput and not on Textarea', function (): void {
    expect(method_exists(TextInput::class, 'autocapitalize'))->toBeTrue()
        ->and(method_exists(Textarea::class, 'autocapitalize'))->toBeFalse();
});
