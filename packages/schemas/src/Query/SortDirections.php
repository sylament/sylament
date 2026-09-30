<?php

declare(strict_types=1);

namespace Sylament\Schemas\Query;

use InvalidArgumentException;
use SortDirection;

final class SortDirections
{
    /**
     * The `asc`/`desc` a table carries, in any case, as the direction Doctrine's
     * builder takes without a deprecation.
     *
     * @throws InvalidArgumentException for anything but asc or desc
     */
    public static function from(string $direction): SortDirection
    {
        return match (mb_strtolower($direction)) {
            'asc' => SortDirection::Ascending,
            'desc' => SortDirection::Descending,
            default => throw new InvalidArgumentException(sprintf('Invalid sort direction "%s": expected asc or desc.', $direction)),
        };
    }
}
