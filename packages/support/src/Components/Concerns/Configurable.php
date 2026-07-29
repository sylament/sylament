<?php

declare(strict_types=1);

namespace Sylament\Support\Components\Concerns;

use Closure;
use Sylament\Support\Configuration\ComponentManager;

trait Configurable
{
    public static function configureUsing(Closure $modifyUsing, ?Closure $during = null, bool $isImportant = false): mixed
    {
        return ComponentManager::resolve()->configureUsing(
            static::class,
            $modifyUsing,
            $during,
            $isImportant,
        );
    }

    public function configure(): static
    {
        ComponentManager::resolve()->configure(
            $this,
            $this->setUp(...),
        );

        return $this;
    }

    protected function setUp(): void
    {
    }
}
