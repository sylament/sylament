<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Actions\Record;

/**
 * A record with no `id` to read, keyed by a code instead.
 */
final class IdlessTicket
{
    public function __construct(
        public string $code,
    ) {
    }
}
