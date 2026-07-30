<?php

declare(strict_types=1);

namespace Sylament\Support\Colors\Components;

use Sylament\Support\Colors\Color;
use Sylament\Support\Colors\ColorManager;
use Sylament\Support\Colors\Contracts\HasColor;
use Sylament\Support\Colors\Contracts\HasDefaultGrayColor;
use Sylament\Support\Colors\Maps\ComponentColorMap;

class ToggleComponent implements HasColor, HasDefaultGrayColor
{
    public function __construct(
        private readonly ColorManager $colors,
    ) {
    }

    /**
     * Since the toggle doesn't contain text, the color may be imperative for the user to
     * understand the toggle's state. Therefore, it should contrast at least 3:1 with the
     * surface to remain compliant with WCAG AA standards.
     *
     * @ref https://www.w3.org/WAI/WCAG21/Understanding/non-text-contrast.html
     *
     * @param array<int, string> $color
     * @return array<string, int>
     */
    public function getColorMap(array $color): array
    {
        $gray = $this->colors->getColor('gray') ?? Color::Zinc;

        return ComponentColorMap::make($color)
            ->slot('bg', surface: $gray[50], minRatio: Color::WCAG_AA_NON_TEXT, fallback: 900)
            ->slot('text', surface: 'oklch(1 0 0)', minRatio: Color::WCAG_AA_NON_TEXT, fallback: 900)
            ->slot('dark:bg', surface: $gray[700], minRatio: Color::WCAG_AA_NON_TEXT, shouldStartFromDarkest: true, fallback: 200)
            ->get();
    }
}
