<?php

declare(strict_types=1);

namespace Sylament\Schemas\Colors\Components;

use Sylament\Support\Colors\Color;
use Sylament\Support\Colors\ColorManager;
use Sylament\Support\Colors\Contracts\HasColor;
use Sylament\Support\Colors\Contracts\HasDefaultGrayColor;
use Sylament\Support\Colors\Maps\ComponentColorMap;

class TextComponent implements HasColor, HasDefaultGrayColor
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
            ->slot('text', surface: $gray[50], fallback: 900)
            ->slot('dark:text', surface: $gray[700], maxShade: 400, shouldStartFromDarkest: true, fallback: 200)
            ->get();
    }
}
