<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\State\Casts;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Sylament\Schemas\State\Casts\DateTimeStateCast;

/**
 * @param class-string<DateTime|DateTimeImmutable>|null $objectClass
 */
function dateTimeCast(
    string $format = 'Y-m-d H:i:s',
    string $internalFormat = 'Y-m-d\TH:i',
    ?string $timezone = null,
    ?string $objectClass = null,
): DateTimeStateCast {
    return new DateTimeStateCast(
        format: $format,
        internalFormat: $internalFormat,
        timezone: $timezone ?? date_default_timezone_get(),
        objectClass: $objectClass,
    );
}

it('gets and sets null for blank state', function (): void {
    expect(dateTimeCast()->set(null))->toBeNull()
        ->and(dateTimeCast()->set(''))->toBeNull()
        ->and(dateTimeCast()->get(null))->toBeNull()
        ->and(dateTimeCast()->get(''))->toBeNull();
});

it('sets a date-time object as the internal-format string', function (): void {
    expect(dateTimeCast()->set(new DateTimeImmutable('2026-08-18 14:30:00')))->toBe('2026-08-18T14:30');
});

it('sets a date-time object into the field timezone', function (): void {
    $state = dateTimeCast(timezone: 'Europe/Bratislava')
        ->set(new DateTimeImmutable('2026-08-18 12:00:00', new DateTimeZone('UTC')));

    expect($state)->toBe('2026-08-18T14:00');
});

it('sets a record string parsed in the record format', function (): void {
    expect(dateTimeCast()->set('2026-08-18 14:30:00'))->toBe('2026-08-18T14:30');
});

it('sets null for a value no parse accepts', function (): void {
    expect(dateTimeCast()->set('banana'))->toBeNull()
        ->and(dateTimeCast()->set(42))->toBeNull()
        ->and(dateTimeCast()->set([]))->toBeNull();
});

it('gets an immutable instance from the internal-format string', function (): void {
    $state = dateTimeCast(objectClass: DateTimeImmutable::class)->get('2026-08-18T14:30');

    expect($state)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($state->format('Y-m-d H:i:s'))->toBe('2026-08-18 14:30:00');
});

it('gets an instance from the space-separated variant of the internal format', function (): void {
    $state = dateTimeCast(objectClass: DateTimeImmutable::class)->get('2026-08-18 14:30');

    expect($state)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($state->format('Y-m-d H:i:s'))->toBe('2026-08-18 14:30:00');
});

it('gets an instance from a value carrying seconds the internal format omits', function (): void {
    $state = dateTimeCast(objectClass: DateTimeImmutable::class)->get('2026-08-18T14:30:07');

    expect($state)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($state->format('Y-m-d H:i:s'))->toBe('2026-08-18 14:30:07');
});

it('gets a date-only value at midnight rather than at the current time', function (): void {
    $state = dateTimeCast(internalFormat: 'Y-m-d', objectClass: DateTimeImmutable::class)->get('2026-08-18');

    expect($state)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($state->format('Y-m-d H:i:s'))->toBe('2026-08-18 00:00:00');
});

it('gets a time-only value carrying that wall time', function (): void {
    $state = dateTimeCast(internalFormat: 'H:i', objectClass: DateTimeImmutable::class)->get('14:30');

    expect($state)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($state->format('H:i'))->toBe('14:30');
});

it('gets a mutable instance when the record wants one', function (): void {
    $state = dateTimeCast(objectClass: DateTime::class)->get('2026-08-18T14:30');

    expect($state)->toBeInstanceOf(DateTime::class)
        ->and($state->format('Y-m-d H:i:s'))->toBe('2026-08-18 14:30:00');
});

it('gets the record-format string when no object class is named', function (): void {
    expect(dateTimeCast()->get('2026-08-18T14:30'))->toBe('2026-08-18 14:30:00');
});

it('gets null for a value no parse accepts', function (): void {
    expect(dateTimeCast(objectClass: DateTimeImmutable::class)->get('garbage'))->toBeNull()
        ->and(dateTimeCast(objectClass: DateTimeImmutable::class)->get(42))->toBeNull();
});

it('normalizes an already-typed value without reinterpreting its wall time', function (): void {
    $state = dateTimeCast(timezone: 'Europe/Bratislava', objectClass: DateTime::class)
        ->get(new DateTimeImmutable('2026-08-18 14:30:00'));

    expect($state)->toBeInstanceOf(DateTime::class)
        ->and($state->format('Y-m-d H:i:s'))->toBe('2026-08-18 14:30:00');
});

it('gets a string state as the field timezone wall time', function (): void {
    $state = dateTimeCast(timezone: 'Europe/Bratislava', objectClass: DateTimeImmutable::class)
        ->get('2026-08-18T14:00');

    expect($state)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($state->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i'))->toBe('2026-08-18 12:00');
});
