<?php

declare(strict_types=1);

use Sylament\Forms\Components\TimePicker;
use Sylament\Schemas\Exceptions\ValidationException;

it('renders a time input and reads its state as H:i:s, or H:i without seconds', function (): void {
    $host = $this->validatingRecordMappingHost();
    $schema = $host->mapping(TimePicker::make('opensAt'), TimePicker::make('closesAt')->seconds(false));
    $host->data = ['opensAt' => '08:30:15', 'closesAt' => '17:45:15'];

    expect($this->renderSchemaComponent(TimePicker::make('opensAt')))->toContainElement('input[name="opensAt"][type="time"]')
        ->and($schema->getState())->toBe(['opensAt' => '08:30:15', 'closesAt' => '17:45']);
});

it('refuses a value no parse accepts, where Filament leaves a time-only picker unvalidated', function (): void {
    $host = $this->validatingRecordMappingHost();
    $schema = $host->mapping(TimePicker::make('opensAt'));
    $host->data = ['opensAt' => 'tampered'];

    expect(fn () => $host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
        expect($exception->getErrors())->toBe(['data.opensAt' => ['This value is not a valid date.']]);
    });

    $host->data = ['opensAt' => '08:30'];

    expect($host->validateSchema($schema))->toBeNull();
});
