<?php

declare(strict_types=1);

namespace Sylament\Support\Colors\Components\DropdownComponent;

use Sylament\Support\Colors\Color;
use Sylament\Support\Colors\ColorManager;
use Sylament\Support\Colors\Contracts\HasColor;
use Sylament\Support\Colors\Contracts\HasDefaultGrayColor;
use Sylament\Support\Colors\Maps\ComponentColorMap;

class ItemComponent implements HasColor, HasDefaultGrayColor
{
    public function __construct(
        private readonly ColorManager $colors,
    ) {
    }

    /**
     * @param array<int, string> $color
     * @return array<string, int>
     */
    public function getColorMap(array $color): array
    {
        $gray = $this->colors->getColor('gray') ?? Color::Zinc;

        return ComponentColorMap::make($color)
            ->slot('text', surface: 'oklch(1 0 0)', minShade: 600, fallback: 950)
            ->slot('hover:text', surface: $color[50], minShade: 600, fallback: 950)
            ->slot('dark:text', surface: $gray[900], maxShade: 400, shouldStartFromDarkest: true, fallback: 200)
            ->slot('dark:hover:text', surface: $color[950], maxShade: 400, shouldStartFromDarkest: true, fallback: 100)
            ->get();
    }
}
