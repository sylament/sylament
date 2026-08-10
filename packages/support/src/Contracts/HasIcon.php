<?php

declare(strict_types=1);

namespace Sylament\Support\Contracts;

use BackedEnum;
use Twig\Markup;

interface HasIcon
{
    public function getIcon(): string|BackedEnum|Markup|null;
}
