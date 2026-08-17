<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Components\Concerns;

use Sylament\Support\Contracts\HasLabel;

/**
 * The string-backed enum whose cases carry their own labels — the shape
 * `HasOptions` expands into a labelled option map and the state casts
 * round-trip.
 */
enum OptionStatus: string implements HasLabel
{
    case Draft = 'draft';

    case Published = 'published';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Published => 'Published',
        };
    }
}
