<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Support\Colors;

use Sylament\Support\Colors\Contracts\HasColor;

/**
 * Answers with a slot outside the eight a policy map may name.
 */
final class OffSlotColorPolicy implements HasColor
{
    /**
     * @param array<int, string> $color
     * @return array<string, int>
     */
    public function getColorMap(array $color): array
    {
        return ['border' => 600];
    }
}
