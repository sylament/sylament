<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Notifications;

use Sylament\Support\Contracts\ScalableIcon;
use Sylament\Support\Enums\IconSize;

/**
 * An icon drawn per size: `notifications:bell-md` at the medium size, its value
 * `notifications:bell` when flattened.
 */
enum SizedIcon: string implements ScalableIcon
{
    case Bell = 'notifications:bell';

    public function getIconForSize(IconSize $size): string
    {
        return "{$this->value}-{$size->value}";
    }
}
