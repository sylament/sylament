<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit\Concerns;

use Sylament\Actions\Tests\Fixtures\Concerns\HasDropdownFixture;

it('has an unconfigured dropdown by default', function (): void {
    $fixture = new HasDropdownFixture();

    expect($fixture->hasDropdown())->toBeTrue()
        ->and($fixture->hasDropdownFlip())->toBeTrue()
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
