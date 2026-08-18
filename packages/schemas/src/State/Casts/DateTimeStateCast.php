<?php

declare(strict_types=1);

namespace Sylament\Schemas\State\Casts;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use Sylament\Schemas\State\Casts\Contracts\StateCast;

use function Sylament\Support\blank;

class DateTimeStateCast implements StateCast
{
    /**
     * @param class-string<DateTimeInterface>|null $objectClass the write-side type the
     *                                                          record wants; null falls back to a string in $format
     */
    public function __construct(
        protected string $format,
        protected string $internalFormat,
        protected string $timezone,
        protected ?string $objectClass = null,
    ) {
    }

    public function get(mixed $state): DateTimeImmutable|DateTime|string|null
    {
        if (blank($state)) {
            return null;
        }

        if ($state instanceof DateTimeInterface) {
            $value = DateTimeImmutable::createFromInterface($state);
        } elseif (is_string($state)) {
            $value = $this->parse($state, new DateTimeZone($this->timezone), $this->internalFormat);

            // Security: a tampered raw value no strict or lenient parse accepts is
            // no selection at all; the ValidDate constraint reports it before any
            // record write reads this null.
            if (! $value instanceof DateTimeImmutable) {
                return null;
            }

            $value = $value->setTimezone(new DateTimeZone(date_default_timezone_get()));
        } else {
            return null;
        }

        if ($this->objectClass === DateTime::class) {
            return DateTime::createFromImmutable($value);
        }

        if ($this->objectClass !== null) {
            return $value;
        }

        return $value->format($this->format);
    }

    public function set(mixed $state): ?string
    {
        if (blank($state)) {
            return null;
        }

        if (is_string($state)) {
            $state = $this->parse($state, new DateTimeZone(date_default_timezone_get()), $this->format);
        }

        if (! $state instanceof DateTimeInterface) {
            return null;
        }

        return DateTimeImmutable::createFromInterface($state)
            ->setTimezone(new DateTimeZone($this->timezone))
            ->format($this->internalFormat);
    }

    private function parse(string $value, DateTimeZone $timezone, string $format): ?DateTimeImmutable
    {
        $parsed = DateTimeImmutable::createFromFormat('!' . $format, $value, $timezone);

        if ($parsed instanceof DateTimeImmutable) {
            return $parsed;
        }

        try {
            return new DateTimeImmutable($value, $timezone);
        } catch (Exception) {
            return null;
        }
    }
}
