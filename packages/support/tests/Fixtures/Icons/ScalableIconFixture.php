<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Fixtures\Icons;

use Sylament\Support\Contracts\ScalableIcon;
use Sylament\Support\Enums\IconSize;

enum ScalableIconFixture: string implements ScalableIcon
{
    case Star = 'star';

    public function getIconForSize(IconSize $size): string
    {
        return "fixture:{$this->value}-{$size->value}";
    }
}
