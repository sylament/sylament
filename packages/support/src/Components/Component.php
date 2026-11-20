<?php

declare(strict_types=1);

namespace Sylament\Support\Components;

use Sylament\Support\Components\Concerns\Configurable;
use Sylament\Support\Concerns\EvaluatesClosures;
use Sylament\Support\Concerns\HasTranslator;
use Sylament\Support\DependencyInjection\ComponentResolver;

abstract class Component
{
    use Configurable;
    use EvaluatesClosures;
    use HasTranslator;

    protected static function newInstance(): static
    {
        return ComponentResolver::resolve()->instantiate(static::class);
    }
}
