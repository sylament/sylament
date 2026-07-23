<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Components\StateCasts;

use Sylament\Schemas\Components\StateCasts\Contracts\StateCast;

/**
 * Observable, non-idempotent fixture cast: each direction appends its token so
 * composition order (`get` runs defaults-then-custom) and the `appliedStateCastPaths`
 * dedup (a shared path is `set` exactly once) are visible in the resulting string.
 */
final readonly class AppendStateCast implements StateCast
{
    public function __construct(private string $token)
    {
    }

    public function get(mixed $state): string
    {
        return $state . '>' . $this->token;
    }

    public function set(mixed $state): string
    {
        return $state . '<' . $this->token;
    }
}
