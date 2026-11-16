<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas\Record;

use Countable;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Sylament\Tests\Fixtures\Entity\Invoice;
use Sylament\Tests\Fixtures\Entity\RevisionedBase;
use Sylament\Tests\Fixtures\Entity\ShelfZone;

/**
 * One nullable property per kind of object type a segment can declare: a constructible class, a backed enum, an
 * abstract class, a class with a private constructor, an interface, the date interface and its two implementors, and
 * a class whose constructor requires an argument.
 */
final class ManuscriptObjectKinds
{
    public ?ManuscriptWriter $writer = null;

    public ?ShelfZone $zone = null;

    public ?RevisionedBase $revision = null;

    public ?ManuscriptSeal $seal = null;

    public ?Countable $countable = null;

    public ?DateTimeInterface $happenedAt = null;

    public ?DateTimeImmutable $immutableAt = null;

    public ?DateTime $mutableAt = null;

    public ?Invoice $invoice = null;
}
