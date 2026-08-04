<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\State;

/**
 * An instantiable class the mapper still may not build: its constructor takes
 * required arguments, so there is no value it could invent for them.
 */
final readonly class Money
{
    public function __construct(
        private int $amount,
        private string $currency,
    ) {
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }
}
