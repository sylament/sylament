<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Components\Concerns;

use Sylament\Support\Contracts\HasDescription;

/**
 * The string-backed enum describing its own cases — one of them not at all, so
 * the derived description map is keyed by the backing value and skips the case
 * that describes itself with null.
 */
enum OptionPriority: string implements HasDescription
{
    case Low = 'low';

    case High = 'high';

    public function getDescription(): ?string
    {
        return match ($this) {
            self::Low => 'Handled when there is time',
            self::High => null,
        };
    }
}
