<?php

declare(strict_types=1);

namespace Sylament\Support\Tests;

use Sylament\Support\Colors\ColorManager;
use Sylament\Support\Colors\Contracts\HasColor;
use Sylament\Support\Configuration\ComponentManager;
use Sylament\Support\DependencyInjection\ComponentResolver;
use Symfony\Component\DependencyInjection\ContainerBuilder;

pest()
    ->extend(TestCase::class)
    ->in('Integration');

pest()
    ->afterEach(function (): void {
        ComponentManager::unsetInstance();
        ComponentResolver::unsetInstance();
    })
    ->in('Unit');

/**
 * The container is left uncompiled because `ColorManager` only reaches it to resolve a policy
 * given as a class-string, and this hands the policy over already built.
 *
 * @template T of HasColor
 * @param class-string<T> $policy
 * @return T
 */
function policy(string $policy): HasColor
{
    return new $policy(new ColorManager(new ContainerBuilder()));
}

/**
 * A `ColorManager` carrying one extra palette, for driving a policy through the manager against
 * a colour the shipped defaults do not cover.
 *
 * @param array<int, string> $palette
 */
function colorManagerWith(string $color, array $palette): ColorManager
{
    $colors = new ColorManager(new ContainerBuilder());
    $colors->register([$color => $palette]);

    return $colors;
}
