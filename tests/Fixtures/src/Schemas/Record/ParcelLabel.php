<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas\Record;

/**
 * One member per source of `ObjectStateCast`'s name set (constructor parameters,
 * public properties, `get`/`is`/`has`/`can` accessors), plus state outside it.
 */
class ParcelLabel
{
    public ?string $colour = null;

    public int $weight = 0;

    public ParcelAddress $address;

    private bool $fragile = false;

    private bool $stack = false;

    private ?string $trackingCode = null;

    public function __construct(
        public string $name,
        public ?string $note = 'draft',
        public int $copies = 1,
    ) {
        $this->address = new ParcelAddress();
    }

    public function getSummary(): string
    {
        return "{$this->name} x{$this->copies}";
    }

    public function isFragile(): bool
    {
        return $this->fragile;
    }

    public function setFragile(bool $fragile): void
    {
        $this->fragile = $fragile;
    }

    public function hasTracking(): bool
    {
        return $this->trackingCode !== null;
    }

    public function canStack(): bool
    {
        return $this->stack;
    }

    public function setStack(bool $stack): void
    {
        $this->stack = $stack;
    }

    public function assignTrackingCode(string $trackingCode): void
    {
        $this->trackingCode = $trackingCode;
    }

    public function trackingCode(): ?string
    {
        return $this->trackingCode;
    }
}
