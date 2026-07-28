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

    /**
     * The single construction seam for every `make()` factory. Goes through the
     * container when the app registered this class (autowired, fresh per call),
     * and falls back to direct construction otherwise.
     */
    protected static function newInstance(): static
    {
        return ComponentResolver::resolve()->instantiate(static::class);
    }
}
