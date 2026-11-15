<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Support\Colors;

use Sylament\Support\Colors\Contracts\HasColor;

/**
 * Paints every slot with a fixed shade whatever the palette, so a test reads how the manager
 * writes a map out apart from any contrast search; it does not paint gray by default.
 */
final class FixedColorPolicy implements HasColor
{
    /**
     * @param array<int, string> $color
     * @return array<string, int>
     */
    public function getColorMap(array $color): array
    {
        return [
            'bg' => 600,
            'hover:bg' => 500,
            'dark:bg' => 400,
            'dark:hover:bg' => 300,
            'text' => 0,
            'hover:text' => 50,
            'dark:text' => 900,
            'dark:hover:text' => 950,
        ];
    }
}
