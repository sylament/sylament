<?php

declare(strict_types=1);

namespace Sylament\Support\DependencyInjection;

interface StaticInstanceInterface
{
    public static function setInstance(StaticInstanceInterface $instance): void;

    public static function getInstance(): static;
}
