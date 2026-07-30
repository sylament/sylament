<?php

declare(strict_types=1);

namespace Sylament\Support\Colors\Components;

use Sylament\Support\Colors\Color;
use Sylament\Support\Colors\ColorManager;
use Sylament\Support\Colors\Contracts\HasColor;
use Sylament\Support\Colors\Contracts\HasDefaultGrayColor;
use Sylament\Support\Colors\Maps\ButtonComponentColorMap;

class ButtonComponent implements HasColor, HasDefaultGrayColor
{
    public function __construct(
        protected readonly ColorManager $colors,
    ) {
    }

    /**
     * @param array<int, string> $color
     * @return array<string, int>
     */
    public function getColorMap(array $color): array
    {
        return ButtonComponentColorMap::make($color)
            ->minContrastRatio(Color::WCAG_AA_TEXT)
            ->lightBackground(bg: 600, hover: 500)
            ->lightBackground(bg: 400, hover: 300, alternateHover: 500)
            ->darkBackground(bg: 600, hover: 500, alternateHover: 700)
            ->get();
    }
}
