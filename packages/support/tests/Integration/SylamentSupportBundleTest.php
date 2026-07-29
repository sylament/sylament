<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Integration;

use Sylament\Support\Configuration\ComponentManager;
use Sylament\Support\DependencyInjection\Contracts\StaticInstance;
use Sylament\Support\DependencyInjection\StaticInstanceRegistry;
use Sylament\Support\Icons\IconManager;
use Sylament\Support\SylamentSupportBundle;
use Sylament\Support\Tests\Fixtures\DependencyInjection\StaticInstanceFixture;
use Sylament\Support\Twig\Extension\IconExtension;
use Sylament\Support\Twig\SupportIconAlias;

it('exposes the static instance registry as a public service', function (): void {
    expect($this::getContainer()->get(StaticInstanceRegistry::class))
        ->toBeInstanceOf(StaticInstanceRegistry::class);
});

it('resolves tagged services as StaticInstance implementations', function (): void {
    $fixture = $this::getContainer()->get(StaticInstanceFixture::class);

    expect($fixture)->toBeInstanceOf(StaticInstance::class);
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

it('provides the icon manager as a service', function (): void {
    expect($this::getContainer()->get(IconManager::class))->toBeInstanceOf(IconManager::class);
});

it('injects the icon manager into the icon extension', function (): void {
    $extension = $this::getContainer()->get('sylament_support.icon_extension');

    expect($extension)->toBeInstanceOf(IconExtension::class)
        ->and($extension->generateIconHtml('heroicons:x-mark'))->not->toBeNull();
});

it('renders icons from the fixture icon directory through the ux-icons renderer', function (): void {
    $manager = $this::getContainer()->get(IconManager::class);

    expect($manager->renderIcon('heroicons:x-mark'))->toContain('<svg');
});

it('registers icon overrides declared in bundle configuration', function (): void {
    $this::bootKernel(['environment' => 'icons']);

    $manager = $this::getContainer()->get(IconManager::class);

    expect($manager)->toBeInstanceOf(IconManager::class)
        ->and($manager->resolve(SupportIconAlias::MODAL_CLOSE_BUTTON))->toBe('fixture:configured-close')
        ->and($manager->resolve(SupportIconAlias::SECTION_COLLAPSE_BUTTON))->toBe('fixture:configured-collapse');
});

it('keeps a dashed alias key that config normalization would otherwise rewrite', function (): void {
    $this::bootKernel(['environment' => 'icons']);

    $manager = $this::getContainer()->get(IconManager::class);

    expect($manager)->toBeInstanceOf(IconManager::class)
        ->and($manager->resolve('modal.close_button'))->toBeNull()
        ->and($manager->resolve('section.collapse_button'))->toBeNull();
});

it('claims the sylament extension alias rather than the name-derived one', function (): void {
    expect(new SylamentSupportBundle()->getContainerExtension()?->getAlias())->toBe('sylament');
});
