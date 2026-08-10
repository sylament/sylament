<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures;

use Sylament\Support\Contracts\ScalableIcon;
use Sylament\Support\Enums\IconSize;

enum ScalableFixtureIcon: string implements ScalableIcon
{
    case Check = 'fixture:check';

    public function getIconForSize(IconSize $size): string
    {
        return "{$this->value}-{$size->value}";
    }
}
