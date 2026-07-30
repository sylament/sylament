<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Fixtures\Colors;

use Sylament\Support\Colors\Contracts\HasColor;
use Sylament\Support\Colors\Maps\ComponentColorMap;

class TextPolicy implements HasColor
{
    /**
     * @param array<int, string> $color
     * @return array<string, int>
     */
    public function getColorMap(array $color): array
    {
        return ComponentColorMap::make($color)
            ->slot('text', surface: $color[50], fallback: 900)
            ->get();
    }
}
