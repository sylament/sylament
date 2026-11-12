<?php

declare(strict_types=1);

use Sylament\Forms\Components\TextInput;

it('applies a configureUsing() closure within its test', function (): void {
    TextInput::configureUsing(fn (TextInput $input): TextInput => $input->prefix('leak'));

    expect(TextInput::make('a')->getPrefixLabel())->toBe('leak');
});

it('never carries a configureUsing() closure into the next test', function (): void {
    expect(TextInput::make('a')->getPrefixLabel())->toBeNull();
});
