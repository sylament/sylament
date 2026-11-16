<?php

declare(strict_types=1);

use Sylament\Forms\Components\TextInput;

it('trims the string state of a trimmed field before validation and saving, Unicode spaces included', function (): void {
    $field = TextInput::make('title')->trim();

    expect($field->mutateStateForValidation("\t a\u{00A0}\u{3000}\n"))->toBe('a')
        ->and($field->mutateDehydratedState(' a '))->toBe('a')
        ->and($field->mutateDehydratedState("\u{200B}a "))->toBe("\u{200B}a");
});

it('leaves the state of an untrimmed field, and a state that is not a string, as they came', function (): void {
    expect(TextInput::make('title')->mutateDehydratedState(' a '))->toBe(' a ')
        ->and(TextInput::make('title')->trim()->mutateDehydratedState(5))->toBe(5);
});
