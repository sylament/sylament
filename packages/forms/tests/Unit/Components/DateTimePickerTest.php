<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Unit\Components;

use DateTimeImmutable;
use LogicException;
use ReflectionProperty;
use Sylament\Forms\Components\DatePicker;
use Sylament\Forms\Components\DateTimePicker;
use Sylament\Forms\Components\TimePicker;
use Sylament\Forms\Validation\DateComparison;
use Sylament\Forms\Validation\ValidDate;
use Sylament\Schemas\State\Casts\Contracts\StateCast;
use Sylament\Schemas\State\Casts\DateTimeStateCast;
use Symfony\Component\Validator\Constraint;

/**
 * @return list<DateComparison>
 */
function comparisons(DateTimePicker $picker): array
{
    return array_values(array_filter(
        $picker->getValidationConstraints(),
        static fn (Constraint $constraint): bool => $constraint instanceof DateComparison,
    ));
}

function lastStateCast(DateTimePicker $picker): StateCast
{
    $casts = $picker->getDefaultStateCasts();

    return array_last($casts);
}

it('renders the native input type of each variant', function (): void {
    expect(DateTimePicker::make('publishedAt')->getType())->toBe('datetime-local')
        ->and(DatePicker::make('day')->getType())->toBe('date')
        ->and(TimePicker::make('alarm')->getType())->toBe('time');
});

it('derives the type from the date and time flags', function (): void {
    expect(DateTimePicker::make('publishedAt')->time(false)->getType())->toBe('date')
        ->and(DateTimePicker::make('publishedAt')->date(false)->getType())->toBe('time');
});

it('refuses to type a non-native picker', function (): void {
    expect(fn (): string => DateTimePicker::make('publishedAt')->native(false)->getType())
        ->toThrow(LogicException::class, 'date-time-picker.md');
});

it('builds the internal format each variant speaks on the wire', function (): void {
    expect(DateTimePicker::make('publishedAt')->getInternalFormat())->toBe('Y-m-d H:i:s')
        ->and(DateTimePicker::make('publishedAt')->seconds(false)->getInternalFormat())->toBe('Y-m-d H:i')
        ->and(DatePicker::make('day')->getInternalFormat())->toBe('Y-m-d')
        ->and(TimePicker::make('alarm')->getInternalFormat())->toBe('H:i:s')
        ->and(TimePicker::make('alarm')->seconds(false)->getInternalFormat())->toBe('H:i');
});

it('builds the record-side format of each variant', function (): void {
    expect(DateTimePicker::make('publishedAt')->getFormat())->toBe('Y-m-d H:i:s')
        ->and(DateTimePicker::make('publishedAt')->seconds(false)->getFormat())->toBe('Y-m-d H:i')
        ->and(DatePicker::make('day')->getFormat())->toBe('Y-m-d')
        ->and(TimePicker::make('alarm')->getFormat())->toBe('H:i:s')
        ->and(TimePicker::make('alarm')->seconds(false)->getFormat())->toBe('H:i');
});

it('lets an explicit format win', function (): void {
    expect(DatePicker::make('day')->format('d.m.Y')->getFormat())->toBe('d.m.Y')
        ->and(DatePicker::make('day')->format(fn (): string => 'd.m.Y')->getFormat())->toBe('d.m.Y');
});

it('composes the step out of the time steps', function (): void {
    expect(DateTimePicker::make('publishedAt')->getStep())->toBe(1)
        ->and(DateTimePicker::make('publishedAt')->seconds(false)->getStep())->toBeNull()
        ->and(DatePicker::make('day')->getStep())->toBeNull()
        ->and(DateTimePicker::make('publishedAt')->secondsStep(30)->getStep())->toBe(30)
        ->and(DateTimePicker::make('publishedAt')->minutesStep(5)->getStep())->toBe(300)
        ->and(DateTimePicker::make('publishedAt')->hoursStep(2)->getStep())->toBe(7200);
});

it('lets an explicit step win', function (): void {
    expect(DateTimePicker::make('publishedAt')->step(90)->getStep())->toBe(90);
});

it('defaults the timezone to the running one', function (): void {
    expect(DateTimePicker::make('publishedAt')->getTimezone())->toBe(date_default_timezone_get())
        ->and(DateTimePicker::make('publishedAt')->timezone('Europe/Bratislava')->getTimezone())
        ->toBe('Europe/Bratislava');
});

it('formats the min and max bounds as strings', function (): void {
    expect(DateTimePicker::make('publishedAt')->minDate('2026-01-01')->getMinDate())->toBe('2026-01-01')
        ->and(DateTimePicker::make('publishedAt')->maxDate(new DateTimeImmutable('2026-08-18 14:30:00'))->getMaxDate())
        ->toBe('2026-08-18 14:30:00')
        ->and(DateTimePicker::make('publishedAt')->getMinDate())->toBeNull()
        ->and(DateTimePicker::make('publishedAt')->getMaxDate())->toBeNull();
});

it('appends a date state cast built from its own formats', function (): void {
    $cast = lastStateCast(DateTimePicker::make('publishedAt')->timezone('Europe/Bratislava'));

    expect($cast)->toBeInstanceOf(DateTimeStateCast::class)
        ->and(new ReflectionProperty($cast, 'format')->getValue($cast))->toBe('Y-m-d H:i:s')
        ->and(new ReflectionProperty($cast, 'internalFormat')->getValue($cast))->toBe('Y-m-d H:i:s')
        ->and(new ReflectionProperty($cast, 'timezone')->getValue($cast))->toBe('Europe/Bratislava')
        ->and(new ReflectionProperty($cast, 'objectClass')->getValue($cast))->toBeNull();
});

it('casts the date-only variant with the date format', function (): void {
    $cast = lastStateCast(DatePicker::make('day'));

    expect(new ReflectionProperty($cast, 'internalFormat')->getValue($cast))->toBe('Y-m-d');
});

it('validates every variant as a date', function (): void {
    $isValidDate = static fn (Constraint $constraint): bool => $constraint instanceof ValidDate;

    expect(array_filter(DateTimePicker::make('publishedAt')->getValidationConstraints(), $isValidDate))->toHaveCount(1)
        ->and(array_filter(DatePicker::make('day')->getValidationConstraints(), $isValidDate))->toHaveCount(1)
        ->and(array_filter(TimePicker::make('alarm')->getValidationConstraints(), $isValidDate))->toHaveCount(1);
});

it('turns the bounds into date comparison constraints', function (): void {
    $constraints = comparisons(DatePicker::make('day')->minDate('2026-01-01')->maxDate('2026-12-31'));

    expect($constraints)->toHaveCount(2)
        ->and($constraints[0]->operator)->toBe(DateComparison::AFTER_OR_EQUAL)
        ->and($constraints[0]->boundary->format('Y-m-d H:i:s'))->toBe('2026-01-01 00:00:00')
        ->and($constraints[1]->operator)->toBe(DateComparison::BEFORE_OR_EQUAL)
        ->and($constraints[1]->boundary->format('Y-m-d H:i:s'))->toBe('2026-12-31 00:00:00');
});

it('builds no comparison without a bound', function (): void {
    expect(comparisons(DatePicker::make('day')))->toBe([]);
});

it('truncates a date-only bound for the native attribute', function (): void {
    $data = DatePicker::make('day')
        ->minDate(new DateTimeImmutable('2026-01-01 08:00:00'))
        ->maxDate('2026-12-31 20:00:00')
        ->getExtraTemplateData();

    expect($data['minAttribute'])->toBe('2026-01-01')
        ->and($data['maxAttribute'])->toBe('2026-12-31');
});

it('passes a bound through whole when the control carries time', function (): void {
    $data = DateTimePicker::make('publishedAt')
        ->minDate(new DateTimeImmutable('2026-01-01 08:00:00'))
        ->getExtraTemplateData();

    expect($data['minAttribute'])->toBe('2026-01-01 08:00:00')
        ->and($data['maxAttribute'])->toBeNull();
});
