<?php

declare(strict_types=1);

namespace Sylament\Support\DependencyInjection;

use RuntimeException;

trait HasStaticInstance
{
    private static ?StaticInstanceInterface $instance = null;

    public static function setInstance(StaticInstanceInterface $instance): void
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
}
