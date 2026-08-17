<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\Contracts;

use Twig\Markup;

interface CanDisableOptions
{
    public function isOptionDisabled(string|int $value, string|Markup $label): bool;

    /**
     * @return array<string|int, string|Markup>
     */
    public function getEnabledOptions(): array;
}
