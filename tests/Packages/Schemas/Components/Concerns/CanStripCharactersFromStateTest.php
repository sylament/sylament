<?php

declare(strict_types=1);

use Sylament\Forms\Components\TextInput;

it('strips each string it is given from a string state before validation', function (string|array $characters, string $stripped): void {
    expect(TextInput::make('phone')->stripCharacters($characters)->mutateStateForValidation('12 34-5'))->toBe($stripped);
})->with([
    'one string' => ['-', '12 345'],
    'a list' => [[' ', '-'], '12345'],
]);

it('leaves a state that is not a string as it came', function (): void {
    expect(TextInput::make('phone')->stripCharacters('-')->mutateStateForValidation(12))->toBe(12);
});

it('reads its characters once per field', function (): void {
    $reads = 0;
    $field = TextInput::make('phone')->stripCharacters(static function () use (&$reads): string {
        $reads++;

        return '-';
    });

    expect($field->mutateStateForValidation('1-2'))->toBe('12')
        ->and($field->mutateStateForValidation('3-4'))->toBe('34')
        ->and($reads)->toBe(1);
});
