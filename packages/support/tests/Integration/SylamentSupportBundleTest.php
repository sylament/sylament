<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Integration;

use Sylament\Support\DependencyInjection\StaticInstanceInterface;
use Sylament\Support\DependencyInjection\StaticInstanceRegistry;
use Sylament\Support\Tests\Fixtures\DependencyInjection\StaticInstanceFixture;

it('exposes the static instance registry as a public service', function (): void {
    expect($this::getContainer()->get(StaticInstanceRegistry::class))
        ->toBeInstanceOf(StaticInstanceRegistry::class);
});

it('resolves tagged services as StaticInstanceInterface implementations', function (): void {
    $fixture = $this::getContainer()->get(StaticInstanceFixture::class);

    expect($fixture)->toBeInstanceOf(StaticInstanceInterface::class);
});

it('wires the static instance after the kernel boots', function (): void {
    $fixture = $this::getContainer()->get(StaticInstanceFixture::class);

    expect(StaticInstanceFixture::getInstance())->toBe($fixture);
});
