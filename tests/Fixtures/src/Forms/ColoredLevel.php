<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Forms;

use Sylament\Support\Contracts\HasColor;
use Sylament\Support\Contracts\HasIcon;

/**
 * A level whose cases give an option its colour and icon, save `Unrated`, which answers neither.
 */
enum ColoredLevel: string implements HasColor, HasIcon
{
    case Low = 'low';
    case High = 'high';
    case Unrated = 'unrated';

    public function getColor(): ?string
    {
        return match ($this) {
            self::Low => 'success',
            self::High => 'danger',
            self::Unrated => null,
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Low => 'support:heart',
            self::High => 'support:star',
            self::Unrated => null,
        };
    }
}
