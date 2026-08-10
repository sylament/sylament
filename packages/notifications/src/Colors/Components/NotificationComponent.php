<?php

declare(strict_types=1);

namespace Sylament\Notifications\Colors\Components;

use Sylament\Support\Colors\Contracts\HasColor;
use Sylament\Support\Colors\Contracts\HasDefaultGrayColor;

class NotificationComponent implements HasColor, HasDefaultGrayColor
{
    /**
     * @param array<int, string> $color
     * @return array<string, int>
     */
    public function getColorMap(array $color): array
    {
        return [];
    }
}
