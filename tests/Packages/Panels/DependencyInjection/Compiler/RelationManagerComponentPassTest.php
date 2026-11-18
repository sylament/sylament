<?php

declare(strict_types=1);

use Sylament\Panels\DependencyInjection\Compiler\RelationManagerComponentPass;
use Sylament\Panels\Resources\RelationManagers\RelationManager;
use Sylament\Tests\Fixtures\Panels\Resources\RelationManagers\SeedlingsRelationManager;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

it('registers a manager as a live component under the name it derives, replacing the tag its attribute left', function (): void {
    $container = new ContainerBuilder();
    $container->setDefinition('seedlings', new Definition(SeedlingsRelationManager::class))
        ->addTag('sylament.panels.relation_manager')
        ->addTag('twig.component');

    new RelationManagerComponentPass()->process($container);

    expect($container->getDefinition('seedlings')->getTag('twig.component'))->toBe([[
        'key' => SeedlingsRelationManager::getComponentName(),
        'live' => true,
        'route' => 'ux_live_component',
        'method' => 'post',
        'url_reference_type' => UrlGeneratorInterface::ABSOLUTE_PATH,
        'expose_public_props' => true,
        'attributes_var' => 'attributes',
        'template_from_method' => 'getTemplate',
    ]])
        ->and($container->getDefinition('seedlings')->hasTag('controller.service_arguments'))->toBeTrue();
});

it('refuses a manager that does not carry #[AsLiveComponent]', function (): void {
    $attributeless = new class extends RelationManager {
        protected static string $relationship = 'seedlings';
    };
    $container = new ContainerBuilder();
    $container->setDefinition('attributeless', new Definition($attributeless::class))->addTag('sylament.panels.relation_manager');

    expect(fn () => new RelationManagerComponentPass()->process($container))
        ->toThrow(LogicException::class, 'must carry a bare #[AsLiveComponent] attribute');
});

it('refuses two managers deriving one live component name', function (): void {
    $container = new ContainerBuilder();
    $container->setDefinition('seedlings', new Definition(SeedlingsRelationManager::class))->addTag('sylament.panels.relation_manager');
    $container->setDefinition('seedlings.again', new Definition(SeedlingsRelationManager::class))->addTag('sylament.panels.relation_manager');

    expect(fn () => new RelationManagerComponentPass()->process($container))
        ->toThrow(LogicException::class, 'Two relation managers derive the live component name "' . SeedlingsRelationManager::getComponentName() . '": seedlings and seedlings.again.');
});

it('leaves an abstract definition alone', function (): void {
    $attributeless = new class extends RelationManager {
        protected static string $relationship = 'seedlings';
    };
    $container = new ContainerBuilder();
    $container->setDefinition('base', new Definition($attributeless::class))
        ->setAbstract(true)
        ->addTag('sylament.panels.relation_manager');

    new RelationManagerComponentPass()->process($container);

    expect($container->getDefinition('base')->hasTag('twig.component'))->toBeFalse()
        ->and($container->getDefinition('base')->getTags())->toBe(['sylament.panels.relation_manager' => [[]]]);
});
