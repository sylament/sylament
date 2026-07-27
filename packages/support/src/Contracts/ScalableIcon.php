<?php

declare(strict_types=1);

namespace Sylament\Support\Contracts;

use Sylament\Support\Enums\IconSize;

interface ScalableIcon
{
    public function getIconForSize(IconSize $size): string;
}
