<?php

declare(strict_types=1);

use Sylament\Schemas\State\Casts\DateTimeStateCast;

beforeEach(function (): void {
    $this->defaultTimezone = date_default_timezone_get();
    date_default_timezone_set('Asia/Tokyo');
});

afterEach(function (): void {
    date_default_timezone_set($this->defaultTimezone);
});

it('writes the wire string in its internal format and reads the record value back in its format', function (string $format, string $internalFormat, string $value, string $wire): void {
    $cast = new DateTimeStateCast($format, $internalFormat, 'Asia/Tokyo');

    expect($cast->set($value))->toBe($wire)
        ->and($cast->get($wire))->toBe($value);
})->with([
    'a date' => ['d/m/Y', 'Y-m-d', '04/07/2026', '2026-07-04'],
    'a time' => ['H:i:s', 'H:i', '14:30:00', '14:30'],
    'a date and time' => ['Y-m-d H:i:s', 'Y-m-d H:i', '2026-07-04 14:30:00', '2026-07-04 14:30'],
]);

it('reads the record value as the object class it is given, or as a string without one', function (?string $objectClass, string $expected): void {
    $value = new DateTimeStateCast('d/m/Y H:i', 'Y-m-d H:i', 'Asia/Tokyo', $objectClass)->get('2026-03-04 14:30');

    expect(is_object($value) ? $value::class : $value)->toBe($expected);
})->with([
    'immutable' => [DateTimeImmutable::class, DateTimeImmutable::class],
    'mutable' => [DateTime::class, DateTime::class],
    'none' => [null, '04/03/2026 14:30'],
]);

it('reads and writes blank state as no value', function (): void {
    $cast = new DateTimeStateCast('Y-m-d', 'Y-m-d', 'Asia/Tokyo');

    foreach ([null, '', '  '] as $state) {
        expect($cast->get($state))->toBeNull()
            ->and($cast->set($state))->toBeNull();
    }
});

it('reads a wire string in its internal format, or one spelled with a T or with seconds', function (string $wire, string $expected): void {
    $value = new DateTimeStateCast('Y-m-d H:i:s', 'Y-m-d H:i', 'Asia/Tokyo', DateTimeImmutable::class)->get($wire);

    expect($value?->format('c'))->toBe($expected);
})->with([
    'the internal format' => ['2026-03-04 14:30', '2026-03-04T14:30:00+09:00'],
    'a T separator' => ['2026-03-04T14:30', '2026-03-04T14:30:00+09:00'],
    'seconds the format does not name' => ['2026-03-04T14:30:15', '2026-03-04T14:30:15+09:00'],
]);

it('writes a record string in its format, or one only a lenient parse reads', function (): void {
    $cast = new DateTimeStateCast('Y-m-d H:i:s', 'Y-m-d H:i', 'Asia/Tokyo');

    expect($cast->set('2026-03-04 14:30:00'))->toBe('2026-03-04 14:30')
        ->and($cast->set('2026-03-04T14:30'))->toBe('2026-03-04 14:30');
});

it('reads a wire string from the field timezone into the default one, in summer and in winter', function (): void {
    $cast = new DateTimeStateCast('Y-m-d H:i:s', 'Y-m-d H:i', 'Europe/Bratislava');

    expect($cast->get('2026-07-04 14:30'))->toBe('2026-07-04 21:30:00')
        ->and($cast->get('2026-01-04 14:30'))->toBe('2026-01-04 22:30:00');
});

it('writes a record value from the default timezone into the field one', function (mixed $value): void {
    expect(new DateTimeStateCast('Y-m-d H:i:s', 'Y-m-d H:i', 'Europe/Bratislava')->set($value))->toBe('2026-07-04 14:30');
})->with([
    'a string' => ['2026-07-04 21:30:00'],
    'an immutable object' => [new DateTimeImmutable('2026-07-04 12:30', new DateTimeZone('UTC'))],
    'a mutable object' => [new DateTime('2026-07-04 12:30', new DateTimeZone('UTC'))],
]);

it('normalizes an already-typed value without reinterpreting its wall time', function (DateTimeInterface $value): void {
    $read = new DateTimeStateCast('Y-m-d H:i:s', 'Y-m-d H:i', 'Europe/Bratislava', DateTimeImmutable::class)->get($value);

    expect($read)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($read?->format('c'))->toBe('2026-07-04T14:30:00+00:00');
})->with([
    'an immutable object' => [new DateTimeImmutable('2026-07-04 14:30', new DateTimeZone('UTC'))],
    'a mutable object' => [new DateTime('2026-07-04 14:30', new DateTimeZone('UTC'))],
]);

it('pins the fields a format leaves out to midnight, and a format with no date to today', function (): void {
    $date = new DateTimeStateCast('Y-m-d', 'Y-m-d', 'Asia/Tokyo', DateTimeImmutable::class)->get('2026-07-04');
    $time = new DateTimeStateCast('H:i', 'H:i', 'America/New_York', DateTimeImmutable::class)->get('14:30');
    $today = new DateTimeImmutable('today', new DateTimeZone('America/New_York'))->format('Y-m-d');

    expect($date?->format('c'))->toBe('2026-07-04T00:00:00+09:00')
        ->and($time?->getTimezone()->getName())->toBe('Asia/Tokyo')
        ->and($time?->setTimezone(new DateTimeZone('America/New_York'))->format('Y-m-d H:i'))->toBe("{$today} 14:30");
});

it('reads and writes a value no parse accepts as no value', function (mixed $state): void {
    $cast = new DateTimeStateCast('Y-m-d H:i:s', 'Y-m-d H:i', 'Asia/Tokyo', DateTimeImmutable::class);

    expect($cast->get($state))->toBeNull()
        ->and($cast->set($state))->toBeNull();
})->with([
    'a garbage string' => ['garbage'],
    'a list' => [['2026-07-04 14:30']],
    'an object that is not a date' => [new stdClass()],
]);
