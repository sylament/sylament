<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas\Record;

/**
 * A class `new` cannot build from outside: its constructor is private.
 */
final class ManuscriptSeal
{
    private function __construct()
    {
    }

    public static function affix(): self
    {
        return new self();
    }
}
