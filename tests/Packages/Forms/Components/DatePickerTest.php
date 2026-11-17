<?php

declare(strict_types=1);

use Sylament\Forms\Components\DatePicker;
use Sylament\Schemas\Exceptions\ValidationException;

it('renders a date input with no step and reads its state as Y-m-d', function (): void {
    $host = $this->validatingRecordMappingHost();
    $schema = $host->mapping(DatePicker::make('publishedOn'));
    $host->data = ['publishedOn' => '2026-08-18'];

    expect($this->renderSchemaComponent(DatePicker::make('publishedOn')))->toContainElement('input[name="publishedOn"][type="date"]:not([step])')
        ->and($schema->getState())->toBe(['publishedOn' => '2026-08-18']);
});

it('cuts a bound carrying a time to its date for the min attribute, while the constraint keeps the time', function (): void {
    $host = $this->validatingRecordMappingHost();
    $schema = $host->mapping(DatePicker::make('publishedOn')->minDate(new DateTimeImmutable('2026-01-01 08:00:00')));
    $host->data = ['publishedOn' => '2026-01-01'];

    expect($this->renderSchemaComponent(DatePicker::make('publishedOn')->minDate(new DateTimeImmutable('2026-01-01 08:00:00'))))
        ->toContainElement('input[name="publishedOn"][min="2026-01-01"]')
        ->and(fn () => $host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data.publishedOn' => ['This value must be on or after 2026-01-01 08:00:00.']]);
        });
});
