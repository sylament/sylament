<?php

declare(strict_types=1);

namespace Sylament\Support\Colors\Components;

use Sylament\Support\Colors\Color;
use Sylament\Support\Colors\Maps\ComponentColorMap;

class OutlinedButtonComponent extends ButtonComponent
{
    /**
     * @param array<int, string> $color
     * @return array<string, int>
     */
    public function getColorMap(array $color): array
    {
        $gray = $this->colors->getColor('gray') ?? Color::Zinc;

        return ComponentColorMap::make($color)
            ->slot('text', surface: $gray[50], minRatio: Color::WCAG_AA_TEXT, fallback: 900)
            ->slot('dark:text', surface: $gray[700], minRatio: Color::WCAG_AA_TEXT, maxShade: 500, shouldStartFromDarkest: true, fallback: 200)
            ->get();
    }
}
