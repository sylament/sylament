<?php

declare(strict_types=1);

use Sylament\Support\Colors\Color;
use Sylament\Support\Colors\ColorManager;
use Sylament\Support\Colors\Components\InputComponent\WrapperComponent\IconComponent;
use Sylament\Support\Colors\Components\ModalComponent\IconComponent as ModalIconComponent;
use Sylament\Support\DependencyInjection\ComponentResolver;
use Sylament\Support\SylamentSupportBundle;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;

it('registers the palettes under sylament.colors, a dashed name kept as written', function (): void {
    $this::bootKernel(['environment' => 'registered_colors']);

    $colors = $this::getContainer()->get(ColorManager::class);

    expect($colors->getColor('brand'))->toBe(Color::generatePalette('#7c3aed'))
        ->and($colors->getColor('brand-alt'))->toBe(Color::Rose)
        ->and($colors->getColor('brand_alt'))->toBeNull();
});

it('refuses a temporary file URL expiry under one minute', function (): void {
    $extension = new SylamentSupportBundle()->getContainerExtension();

    expect(fn () => $extension?->load([['temporary_file_url_expiry_minutes' => 0]], new ContainerBuilder()))->toThrow(
        InvalidConfigurationException::class,
        'The value 0 is too small for path "sylament.temporary_file_url_expiry_minutes". Should be greater than or equal to 1',
    );
});

it('registers every colour policy public, at each depth its globs reach', function (): void {
    $root = dirname(__DIR__, 3) . '/packages/support/src/Colors/Components';
    $policies = [];

    foreach ([...glob("{$root}/*.php") ?: [], ...glob("{$root}/*/*.php") ?: [], ...glob("{$root}/*/*/*.php") ?: []] as $path) {
        $policies[] = 'Sylament\Support\Colors\Components\\' . str_replace('/', '\\', mb_substr($path, mb_strlen($root) + 1, -4));
    }

    $container = $this::getContainer()->get('service_container');

    expect($policies)->toContain(ModalIconComponent::class, IconComponent::class)
        ->and(array_values(array_filter($policies, fn (string $policy): bool => !$container->has($policy))))->toBe([]);
});

it('boots each static instance as its container service', function (): void {
    expect(ComponentResolver::resolve())->toBe($this::getContainer()->get(ComponentResolver::class));
});

it('maps the built assets into AssetMapper under @sylament/support', function (): void {
    $asset = $this::getContainer()->get(AssetMapperInterface::class)->getAssetFromSourcePath(
        dirname(__DIR__, 3) . '/packages/support/assets/dist/controllers/core_controller.js',
    );

    expect($asset?->logicalPath)->toBe('@sylament/support/controllers/core_controller.js');
});
