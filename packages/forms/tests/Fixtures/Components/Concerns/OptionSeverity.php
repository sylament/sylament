<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Components\Concerns;

use Sylament\Support\Contracts\HasColor;
use Sylament\Support\Contracts\HasIcon;

/**
 * The string-backed enum carrying its own colour and icon — one case carrying
 * neither, so the derived maps prove they keep a null-answering case as a key
 * held against a null value rather than dropping it the way descriptions do.
 */
enum OptionSeverity: string implements HasColor, HasIcon
{
    case Critical = 'critical';

    case Routine = 'routine';

    /**
     * @return string|array<int|string, string|int>|null
     */
    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Critical => 'danger',
            self::Routine => null,
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Critical => 'heroicon-o-exclamation-triangle',
            self::Routine => null,
        };
    }
}
