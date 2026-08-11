<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit\Concerns;

use Sylament\Actions\Tests\Fixtures\Concerns\HasDropdownFixture;

it('has an unconfigured dropdown by default', function (): void {
    $fixture = new HasDropdownFixture();

    expect($fixture->hasDropdown())->toBeTrue()
        ->and($fixture->hasDropdownFlip())->toBeTrue()
        ->and($fixture->hasDropdownTeleport())->toBeFalse()
        ->and($fixture->getDropdownPlacement())->toBeNull()
        ->and($fixture->getDropdownOffset())->toBeNull()
        ->and($fixture->getDropdownWidth())->toBeNull()
        ->and($fixture->getDropdownMaxHeight())->toBeNull();
});

it('stores and evaluates dropdown configuration', function (): void {
    $fixture = new HasDropdownFixture()
        ->dropdown(fn (): bool => false)
        ->dropdownPlacement('top-end')
        ->dropdownOffset(fn (): int => 4)
        ->dropdownWidth('16rem')
        ->dropdownMaxHeight('20rem')
        ->dropdownFlip(false);

    expect($fixture->hasDropdown())->toBeFalse()
        ->and($fixture->getDropdownPlacement())->toBe('top-end')
        ->and($fixture->getDropdownOffset())->toBe(4)
        ->and($fixture->getDropdownWidth())->toBe('16rem')
        ->and($fixture->getDropdownMaxHeight())->toBe('20rem')
        ->and($fixture->hasDropdownFlip())->toBeFalse();
});

it('falls back from explicit placement to the default placement', function (): void {
    $fixture = new HasDropdownFixture()->defaultDropdownPlacement('right-start');

    expect($fixture->getDropdownPlacement())->toBe('right-start')
        ->and($fixture->dropdownPlacement('left-start')->getDropdownPlacement())->toBe('left-start');
});

it('turns teleport on explicitly and from a closure', function (): void {
    expect(new HasDropdownFixture()->dropdownTeleport()->hasDropdownTeleport())->toBeTrue()
        ->and(new HasDropdownFixture()->dropdownTeleport(fn (): bool => true)->hasDropdownTeleport())->toBeTrue()
        ->and(new HasDropdownFixture()->dropdownTeleport(fn (): bool => false)->hasDropdownTeleport())->toBeFalse();
});

/**
 * The `??` resolution, not a `?:` chain: an explicit `false` is a decision the
 * default must not overturn, and only a null property falls through to it.
 */
it('lets an explicit teleport decision beat the default in both directions', function (): void {
    expect(new HasDropdownFixture()->defaultDropdownTeleport()->hasDropdownTeleport())->toBeTrue()
        ->and(new HasDropdownFixture()->defaultDropdownTeleport()->dropdownTeleport(false)->hasDropdownTeleport())
        ->toBeFalse()
        ->and(new HasDropdownFixture()->defaultDropdownTeleport(false)->dropdownTeleport()->hasDropdownTeleport())
        ->toBeTrue()
        ->and(new HasDropdownFixture()->defaultDropdownTeleport(fn (): bool => true)->hasDropdownTeleport())
        ->toBeTrue();
});
