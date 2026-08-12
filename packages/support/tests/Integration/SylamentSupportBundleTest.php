<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Integration;

use Sylament\Support\Colors\Color;
use Sylament\Support\Colors\ColorManager;
use Sylament\Support\Colors\Components\BadgeComponent;
use Sylament\Support\Configuration\ComponentManager;
use Sylament\Support\DependencyInjection\Contracts\StaticInstance;
use Sylament\Support\DependencyInjection\StaticInstanceRegistry;
use Sylament\Support\Icons\IconManager;
use Sylament\Support\SylamentSupportBundle;
use Sylament\Support\Tests\Fixtures\DependencyInjection\StaticInstanceFixture;
use Sylament\Support\Twig\Extension\IconExtension;
use Sylament\Support\Twig\SupportIconAlias;
use Symfony\Component\DependencyInjection\ContainerBuilder;

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

it('provides the colour manager carrying the default palettes', function (): void {
    $manager = $this::getContainer()->get(ColorManager::class);

    expect($manager)->toBeInstanceOf(ColorManager::class)
        ->and($manager->getColor('primary'))->toBe(Color::Amber);
});

it('exposes a colour policy on the public container', function (): void {
    // `ColorManager` asks the service container for a policy by class-string, so a private
    // definition would be removed before the first lookup even though tests can still see it.
    $container = $this::bootKernel()->getContainer();

    expect($container->get(BadgeComponent::class))->toBeInstanceOf(BadgeComponent::class);
});

it('keeps a dashed colour key that config normalization would otherwise rewrite', function (): void {
    $container = new ContainerBuilder();

    new SylamentSupportBundle()->getContainerExtension()?->load([['colors' => ['off-brand' => Color::Rose]]], $container);

    $registered = $container->getDefinition('sylament_support.color_manager')->getMethodCalls()[0][1][0];

    expect($registered)->toHaveKey('off-brand')
        ->and($registered)->not->toHaveKey('off_brand');
});

it('provides the icon manager as a service', function (): void {
    expect($this::getContainer()->get(IconManager::class))->toBeInstanceOf(IconManager::class);
});

it('exposes the icon manager on the public container', function (): void {
    // Shipped closures type-hint the manager and the `Invoker` resolves them from the service
    // container by class-string, so a private definition answers no lookup at render time.
    $container = $this::bootKernel()->getContainer();

    expect($container->get(IconManager::class))->toBeInstanceOf(IconManager::class);
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
