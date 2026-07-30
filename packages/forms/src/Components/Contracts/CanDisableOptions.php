<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\Contracts;

interface CanDisableOptions
{
    public function isOptionDisabled(string|int $value, string $label): bool;

    /**
     * @return array<string|int, string>
     */
    public function getEnabledOptions(): array;
}
