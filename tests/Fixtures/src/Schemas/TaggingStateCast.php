<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas;

use Sylament\Schemas\State\Casts\Contracts\StateCast;

/**
 * A cast that appends its tag and the direction it ran in to the state it is given, so a
 * field with several shows the order they ran in.
 */
enum TaggingStateCast: string implements StateCast
{
    case First = 'first';
    case Second = 'second';

    public function get(mixed $state): string
    {
        return $this->tag($state, 'get');
    }

    public function set(mixed $state): string
    {
        return $this->tag($state, 'set');
    }

    private function tag(mixed $state, string $direction): string
    {
        return (is_string($state) ? $state : '') . " {$this->value}:{$direction}";
    }
}
