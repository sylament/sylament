<?php

declare(strict_types=1);

namespace Sylament\Support\Colors\Components;

use Sylament\Support\Colors\Color;
use Sylament\Support\Colors\ColorManager;
use Sylament\Support\Colors\Contracts\HasColor;
use Sylament\Support\Colors\Contracts\HasDefaultGrayColor;
use Sylament\Support\Colors\Maps\IconButtonComponentColorMap;

class IconButtonComponent implements HasColor, HasDefaultGrayColor
{
    public function __construct(
        private readonly ColorManager $colors,
    ) {
    }

    /**
     * Since the icon button doesn't contain text, the icon is imperative for the user to
     * understand the button's purpose. Therefore, the icon should have a color that contrasts
     * at least 3:1 with the surface to remain compliant with WCAG AA standards.
     *
     * @ref https://www.w3.org/WAI/WCAG21/Understanding/non-text-contrast.html
     *
     * @param array<int, string> $color
     * @return array<string, int>
     */
    public function getColorMap(array $color): array
    {
        $gray = $this->colors->getColor('gray') ?? Color::Zinc;

        return IconButtonComponentColorMap::make($color)
            ->minContrastRatio(Color::WCAG_AA_NON_TEXT)
            ->lightSurface($gray[50])
            ->darkSurface($gray[700])
            ->darkMaxShade(500)
            ->get();
    }
}
