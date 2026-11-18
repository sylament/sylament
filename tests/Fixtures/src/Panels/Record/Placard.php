<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Record;

/**
 * A record whose caption may hold any value, or none yet.
 */
final class Placard
{
    public mixed $caption;

    public static function captioned(mixed $caption): self
    {
        $placard = new self();
        $placard->caption = $caption;

        return $placard;
    }
}
