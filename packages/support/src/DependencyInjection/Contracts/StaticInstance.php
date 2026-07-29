<?php

declare(strict_types=1);

namespace Sylament\Support\DependencyInjection\Contracts;

/**
 * @internal
 */
interface StaticInstance
{
    public static function setInstance(self $instance): void;

    public static function getInstance(): static;

    public static function unsetInstance(): void;
}
