<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas\Record;

use Stringable;

/**
 * A plain record, no entity, that a closure may ask for by its class or by the
 * interface it implements.
 */
final readonly class Memo implements Stringable
{
    public function __construct(
        public string $subject,
    ) {
    }

    public function __toString(): string
    {
        return $this->subject;
    }
}
