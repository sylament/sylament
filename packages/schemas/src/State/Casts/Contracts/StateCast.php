<?php

declare(strict_types=1);

namespace Sylament\Schemas\State\Casts\Contracts;

interface StateCast
{
    public function get(mixed $state): mixed;

    public function set(mixed $state): mixed;
}
