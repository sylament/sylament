<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Support\Icons;

use Sylament\Support\Contracts\ScalableIcon;
use Sylament\Support\Enums\IconSize;

/**
 * An icon drawn per size: `support:star-lg` at the large size, its value `support:star`
 * when flattened.
 */
enum SizedIcon: string implements ScalableIcon
{
    case Star = 'support:star';

    public function getIconForSize(IconSize $size): string
    {
        return "{$this->value}-{$size->value}";
    }
}
