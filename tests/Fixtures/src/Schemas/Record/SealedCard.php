<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas\Record;

/**
 * A record class nothing outside it can construct.
 */
final class SealedCard
{
    private function __construct()
    {
    }
}
