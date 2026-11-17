<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Actions\Record;

/**
 * A record whose `id` holds whatever it is given, so a key that is blank, a list or a
 * `Stringable` reaches the reader as it is.
 */
final class LooseIdTicket
{
    public function __construct(
        public mixed $id,
    ) {
    }
}
