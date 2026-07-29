<?php

declare(strict_types=1);

namespace Sylament\Support\Components;

use Sylament\Support\Concerns\Configurable;
use Sylament\Support\Concerns\EvaluatesClosures;
use Sylament\Support\DependencyInjection\ComponentResolver;

abstract class Component
{
    use Configurable;
    use EvaluatesClosures;

    protected static function newInstance(): static
    {
        return ComponentResolver::resolve()->instantiate(static::class);
    }
}
