<?php

declare(strict_types=1);

namespace Sylament\Support\Colors\Contracts;

interface HasColor
{
    /**
     * @param  array<int, string>  $color
     * @return array<string, int>
     */
    public function getColorMap(array $color): array;
}
