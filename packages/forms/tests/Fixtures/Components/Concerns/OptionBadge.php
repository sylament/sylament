<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Components\Concerns;

use Sylament\Support\Contracts\HasColor;
use Sylament\Support\Contracts\HasIcon;

/**
 * The pure `UnitEnum` carrying a colour and an icon — no backing value, so the
 * derived maps key off the case name.
 */
enum OptionBadge implements HasColor, HasIcon
{
    case Fresh;

    case Archived;

    /**
     * @return string|array<int|string, string|int>|null
     */
    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Fresh => 'info',
            self::Archived => 'gray',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Fresh => 'heroicon-o-sparkles',
            self::Archived => 'heroicon-o-archive-box',
        };
    }
}
