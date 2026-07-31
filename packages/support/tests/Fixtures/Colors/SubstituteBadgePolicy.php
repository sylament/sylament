<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Fixtures\Colors;

use Sylament\Support\Colors\Contracts\HasColor;
use Sylament\Support\Colors\Contracts\HasDefaultGrayColor;

/**
 * Stands in for `BadgeComponent` under its own service id, to pin that an application can replace
 * a shipped colour policy. The map is fixed rather than contrast-searched so the assertion names
 * shades no real policy would resolve, which is what makes the substitution visible.
 */
class SubstituteBadgePolicy implements HasColor, HasDefaultGrayColor
{
    /**
     * @param array<int, string> $color
     * @return array<string, int>
     */
    public function getColorMap(array $color): array
    {
        return [
            'text' => 950,
            'dark:text' => 50,
        ];
    }
}
