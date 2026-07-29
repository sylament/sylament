<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Integration;

use Sylament\Support\Configuration\ComponentManager;
use Sylament\Support\DependencyInjection\StaticInstanceInterface;
use Sylament\Support\DependencyInjection\StaticInstanceRegistry;
use Sylament\Support\Icons\IconManager;
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

it('provides the component manager as the resolved singleton', function (): void {
    $manager = $this::getContainer()->get(ComponentManager::class);

    expect($manager)->toBeInstanceOf(ComponentManager::class)
        ->and(ComponentManager::resolve())->toBe($manager)
        ->and(ComponentManager::getInstance())->toBe($manager);
});

it('provides the icon manager as a booted static instance', function (): void {
    $manager = $this::getContainer()->get(IconManager::class);

    expect($manager)->toBeInstanceOf(IconManager::class)
        ->and(IconManager::getInstance())->toBe($manager);
});

it('renders icons from the fixture icon directory through the ux-icons renderer', function (): void {
    $manager = $this::getContainer()->get(IconManager::class);

    expect($manager->renderIcon('heroicons:x-mark'))->toContain('<svg');
});
