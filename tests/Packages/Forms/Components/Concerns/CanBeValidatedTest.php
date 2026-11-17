<?php

declare(strict_types=1);

use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Exceptions\ValidationException;

describe('date comparison', function (): void {
    beforeEach(function (): void {
        $this->host = $this->validatingRecordMappingHost();
    });

    it("builds the comparison Filament's rule spelling names", function (string $rule, array $errors): void {
        $schema = $this->host->mapping(TextInput::make('endsAt')->dateComparisonRule($rule, '2026-01-01'));
        $this->host->data = ['endsAt' => '2026-01-01'];

        if ($errors === []) {
            expect($this->host->validateSchema($schema))->toBeNull();

            return;
        }

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception) use ($errors): void {
            expect($exception->getErrors())->toBe(['data.endsAt' => $errors]);
        });
    })->with([
        'after' => ['after', ['This value must be after 2026-01-01 00:00:00.']],
        'after_or_equal' => ['after_or_equal', []],
        'before' => ['before', ['This value must be before 2026-01-01 00:00:00.']],
        'before_or_equal' => ['before_or_equal', []],
    ]);

    it('compares against a literal date, or against a sibling field read again on each run', function (): void {
        $schema = $this->host->mapping(
            TextInput::make('startsAt'),
            TextInput::make('endsAt')->after('startsAt'),
            TextInput::make('reviewedAt')->before('2026-01-01'),
        );
        $this->host->data = ['startsAt' => '2026-06-01', 'endsAt' => '2026-03-01', 'reviewedAt' => '2027-01-01'];

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe([
                'data.endsAt' => ['This value must be after 2026-06-01 00:00:00.'],
                'data.reviewedAt' => ['This value must be before 2026-01-01 00:00:00.'],
            ]);
        });

        $this->host->data = ['startsAt' => '2026-01-01', 'endsAt' => '2026-03-01', 'reviewedAt' => '2025-01-01'];

        expect($this->host->validateSchema($schema))->toBeNull();
    });

    it("reads a sibling relative to its own container, a slash-led name from the root, and a field named like a date as a date", function (): void {
        $schema = $this->host->mapping(
            Section::make('Period')->statePath('period')->components([
                TextInput::make('startsAt'),
                TextInput::make('endsAt')->after('startsAt'),
            ]),
            TextInput::make('closedAt')->after('/data.period.startsAt'),
            TextInput::make('may'),
            TextInput::make('noted')->after('may'),
            TextInput::make('flagged')->after('2999-01-01', isStatePathAbsolute: true),
        );
        $this->host->data = [
            'period' => ['startsAt' => '2026-06-01', 'endsAt' => '2026-03-01'],
            'closedAt' => '2026-03-01',
            'may' => '1900-01-01',
            'noted' => '1950-01-01',
            'flagged' => '2000-01-01',
        ];

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toHaveKeys(['data.period.endsAt', 'data.closedAt', 'data.noted', 'data.flagged'])
                ->and($exception->getErrors()['data.period.endsAt'])->toBe(['This value must be after 2026-06-01 00:00:00.'])
                ->and($exception->getErrors()['data.closedAt'])->toBe(['This value must be after 2026-06-01 00:00:00.'])
                ->and($exception->getErrors()['data.noted'][0])->toStartWith('This value must be after ')
                ->and($exception->getErrors()['data.flagged'])->toBe(['This value must be after 2999-01-01 00:00:00.']);
        });
    });

    it('validates clean against a blank or unparseable sibling whatever the operator, leaving presence to the sibling', function (string $method, string $startsAt): void {
        $schema = $this->host->mapping(TextInput::make('startsAt'), TextInput::make('endsAt')->{$method}('startsAt'));
        $this->host->data = ['startsAt' => $startsAt, 'endsAt' => '2026-03-01'];

        expect($this->host->validateSchema($schema))->toBeNull();
    })->with([
        'after, a blank sibling' => ['after', ''],
        'after or equal, a blank sibling' => ['afterOrEqual', ''],
        'before, a blank sibling' => ['before', ''],
        'before or equal, a blank sibling' => ['beforeOrEqual', ''],
        'after, an unparseable sibling' => ['after', 'soon'],
    ]);

    it('files only the required error of a blank sibling that is required, none on the field comparing against it', function (): void {
        $schema = $this->host->mapping(TextInput::make('startsAt')->required(), TextInput::make('endsAt')->before('startsAt'));
        $this->host->data = ['startsAt' => '', 'endsAt' => '2026-03-01'];

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data.startsAt' => ['This value should not be blank.']]);
        });
    });
});
