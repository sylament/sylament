<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Components\Concerns;

use Sylament\Support\Contracts\HasColor;
use Sylament\Support\Contracts\HasIcon;

/**
 * The colour-and-icon enum whose icons are real Iconify names with fixture files
 * on disk, so a render test can drive the derived maps all the way to the
 * painted `<svg>`. Its siblings carry Filament-shaped `heroicon-o-*` names,
 * which the test kernel's icon set cannot resolve.
 */
enum OptionRating: string implements HasColor, HasIcon
{
    case Up = 'up';

    case Down = 'down';

    public function getColor(): string
    {
        return match ($this) {
            self::Up => 'success',
            self::Down => 'danger',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Up => 'heroicons:arrow-up-20-solid',
            self::Down => 'heroicons:arrow-down-20-solid',
        };
    }
}
