<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Integration;

use Sylament\Schemas\Colors\Components\IconComponent;
use Sylament\Schemas\Colors\Components\TextComponent;
use Sylament\Schemas\State\PropertyAccessor;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Twig\Environment;

it('exposes both colour policies on the public container', function (): void {
    // `ColorManager` asks the service container for a policy by class-string, so a private
    // definition would be removed before the first lookup even though tests can still see it.
    $container = $this::bootKernel()->getContainer();

    expect($container->get(TextComponent::class))->toBeInstanceOf(TextComponent::class)
        ->and($container->get(IconComponent::class))->toBeInstanceOf(IconComponent::class);
});

it('injects the record type reader into the property accessor', function (): void {
    expect($this::getContainer()->get(PropertyAccessor::class))
        ->toBeInstanceOf(PropertyAccessor::class);
});

it('registers the renderSchema twig function', function (): void {
    $twig = $this::getContainer()->get('twig');

    expect($twig)->toBeInstanceOf(Environment::class)
        ->and($twig->getFunction('renderSchema'))->not->toBeFalse();
});

it('maps the built assets directory into AssetMapper under @sylament/schemas', function (): void {
    // StimulusBundle's `ControllersMapGenerator` resolves a `symfony.controllers` entry by
    // calling `getAssetFromSourcePath()` on the package directory plus the declared `main`,
    // and throws when the path sits outside every registered one — so the prepend is what
    // stands between the built controller and a consuming app.
    $asset = $this::getContainer()->get(AssetMapperInterface::class)->getAssetFromSourcePath(
        dirname(__DIR__, 2) . '/assets/dist/controllers/tabs_controller.js',
    );

    expect($asset)->not->toBeNull()
        ->and($asset->logicalPath)->toBe('@sylament/schemas/controllers/tabs_controller.js');
});

it('resolves the schema-component-method helper the forms bundle imports', function (): void {
    // The helper moved here from forms, and forms' select controller inlines it at build time —
    // but it also ships standalone for the per-file build, so its logical path must resolve.
    $asset = $this::getContainer()->get(AssetMapperInterface::class)->getAssetFromSourcePath(
        dirname(__DIR__, 2) . '/assets/dist/utils/schema_component_method.js',
    );

    expect($asset)->not->toBeNull()
        ->and($asset->logicalPath)->toBe('@sylament/schemas/utils/schema_component_method.js');
});
