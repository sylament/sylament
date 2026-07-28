<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit\Concerns;

use Sylament\Actions\Tests\Fixtures\Concerns\HasMountableArgumentsFixture;

it('has no invoked arguments by default', function (): void {
    expect(new HasMountableArgumentsFixture()->getInvokedArguments())->toBeNull();
});

it('binds arguments onto a clone when invoked', function (): void {
    $fixture = new HasMountableArgumentsFixture();

    $bound = $fixture(['id' => 5]);

    expect($bound)->not->toBe($fixture)
        ->and($bound->getArguments())->toBe(['id' => 5])
        ->and($bound->getInvokedArguments())->toBe(['id' => 5]);
});

it('leaves the original instance untouched when invoked', function (): void {
    $fixture = new HasMountableArgumentsFixture();

    $fixture(['id' => 5]);

    expect($fixture->getArguments())->toBe([])
        ->and($fixture->getInvokedArguments())->toBeNull();
});
