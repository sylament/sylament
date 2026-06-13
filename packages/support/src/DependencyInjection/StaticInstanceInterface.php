<?php

declare(strict_types=1);

namespace Sylament\Support\DependencyInjection;

/**
 * @internal
 */
interface StaticInstanceInterface
{
    public static function setInstance(self $instance): void;

    public static function getInstance(): static;

    public static function unsetInstance(): void;
}
