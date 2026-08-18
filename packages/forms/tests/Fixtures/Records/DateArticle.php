<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Records;

use DateTime;
use DateTimeImmutable;

/**
 * The record whose properties are typed as the three write-side shapes a date
 * field can produce: `publishedAt` is the immutable object a Doctrine
 * `datetime_immutable` column declares, `reviewedAt` its mutable twin, and
 * `archivedAt` the string arm the cast falls back to when the record wants no
 * object at all. All three are nullable, so requirement derivation stays out of
 * the round-trip.
 */
final class DateArticle
{
    public ?DateTimeImmutable $publishedAt = null;

    public ?DateTime $reviewedAt = null;

    public ?string $archivedAt = null;
}
