<?php

declare(strict_types=1);

namespace Sylament\Support\Contracts;

use Symfony\Contracts\Translation\TranslatableInterface;
use Twig\Markup;

interface HasDescription
{
    public function getDescription(): string|Markup|TranslatableInterface|null;
}
