<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Integration\Colors;

use Sylament\Support\Colors\ColorManager;
use Sylament\Support\Colors\Components\BadgeComponent;
use Sylament\Support\Tests\Fixtures\Colors\SubstituteBadgePolicy;

/**
 * Replacing a shipped colour policy is a supported extension point: `ColorManager` resolves a
 * policy from the container by class-string, so redefining that service id in an application
 * swaps the shade map everywhere the policy is used. Both halves are pinned here — the shipped
 * behaviour in the default environment, and the substitution in `color_policy` — because the
 * override is only meaningful if the two differ.
 */
it('resolves the shipped policy by default', function (): void {
    $colors = $this::getContainer()->get(ColorManager::class);

    expect($this::getContainer()->get(BadgeComponent::class))->toBeInstanceOf(BadgeComponent::class)
        ->and($colors->getComponentClasses(BadgeComponent::class, 'warning'))
        ->toBe(['sy-color', 'sy-color-warning', 'sy-text-color-700', 'dark:sy-text-color-400']);
});

it('uses an application policy registered under the shipped service id', function (): void {
    $this::bootKernel(['environment' => 'color_policy']);

    $colors = $this::getContainer()->get(ColorManager::class);

    expect($this::getContainer()->get(BadgeComponent::class))->toBeInstanceOf(SubstituteBadgePolicy::class)
        ->and($colors->getComponentClasses(BadgeComponent::class, 'warning'))
        ->toBe(['sy-color', 'sy-color-warning', 'sy-text-color-950', 'dark:sy-text-color-50']);
});

/**
 * The substituted policy still implements `HasDefaultGrayColor`, so the gray short-circuit is the
 * policy's to keep or drop — the override replaces the whole object, not just its map.
 */
it('keeps the contracts the substituted policy declares', function (): void {
    $this::bootKernel(['environment' => 'color_policy']);

    expect($this::getContainer()->get(ColorManager::class)->getComponentClasses(BadgeComponent::class, 'gray'))
        ->toBe([]);
});
