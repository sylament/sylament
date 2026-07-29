<?php

declare(strict_types=1);

namespace Sylament\Support\DependencyInjection\Concerns;

use RuntimeException;
use Sylament\Support\DependencyInjection\Contracts\StaticInstance;

trait HasStaticInstance
{
    private static ?StaticInstance $instance = null;

    public static function setInstance(StaticInstance $instance): void
    {
        self::$instance = $instance;
    }

    public static function getInstance(): static
    {
        $instance = self::$instance;

        if (!$instance instanceof static) {
            throw new RuntimeException(sprintf('%s has no instance set; ensure the kernel has booted.', static::class));
        }

        return $instance;
    }

    public static function unsetInstance(): void
    {
        self::$instance = null;
    }
}
