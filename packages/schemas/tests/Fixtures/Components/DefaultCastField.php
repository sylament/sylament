<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Components;

use Sylament\Schemas\Components\StateCasts\Contracts\StateCast;
use Sylament\Schemas\Tests\Fixtures\Components\StateCasts\AppendStateCast;

/**
 * A state-bearing leaf that seeds a default cast via `getDefaultStateCasts()`, so the
 * "defaults compose before custom" ordering has a subject. `StateField::make()` uses
 * `new static()`, so it constructs a `DefaultCastField`.
 */
class DefaultCastField extends StateField
{
    /**
     * @return array<StateCast>
     */
    public function getDefaultStateCasts(): array
    {
        return [new AppendStateCast('default')];
    }
}
