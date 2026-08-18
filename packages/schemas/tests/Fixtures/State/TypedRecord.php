<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\State;

use ArrayIterator;
use DateTimeInterface;
use Sylament\Schemas\Tests\Fixtures\State\Casts\OptionStateCastFixtureEnum;

/**
 * The record whose declared types are the subject: a non-nullable scalar, a
 * `Traversable` object standing in for a Doctrine collection, an enum that
 * `class_exists()` accepts but `new` cannot build, a {@see Money} that is
 * instantiable yet needs constructor arguments, `$happenedAt`, typed to the
 * `DateTimeInterface` interface the reader carves out, and `$vivifying`, whose
 * getter is non-nullable while its mutator is not — the asymmetry that pins
 * which side {@see \Sylament\Schemas\State\RecordTypeReader} reads.
 */
final class TypedRecord
{
    private string $slug = '';

    private ?DateTimeInterface $happenedAt = null;

    /** @var ArrayIterator<int, string>|null */
    private ?ArrayIterator $tags = null;

    private ?OptionStateCastFixtureEnum $status = null;

    private ?Author $vivifying = null;

    private ?Money $money = null;

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    public function getHappenedAt(): ?DateTimeInterface
    {
        return $this->happenedAt;
    }

    public function setHappenedAt(?DateTimeInterface $happenedAt): void
    {
        $this->happenedAt = $happenedAt;
    }

    /** @return ArrayIterator<int, string>|null */
    public function getTags(): ?ArrayIterator
    {
        return $this->tags;
    }

    /** @param ArrayIterator<int, string>|null $tags */
    public function setTags(?ArrayIterator $tags): void
    {
        $this->tags = $tags;
    }

    public function getStatus(): ?OptionStateCastFixtureEnum
    {
        return $this->status;
    }

    public function setStatus(?OptionStateCastFixtureEnum $status): void
    {
        $this->status = $status;
    }

    public function getVivifying(): Author
    {
        return $this->vivifying ??= new Author();
    }

    public function setVivifying(?Author $vivifying): void
    {
        $this->vivifying = $vivifying;
    }

    public function getMoney(): ?Money
    {
        return $this->money;
    }

    public function setMoney(?Money $money): void
    {
        $this->money = $money;
    }
}
