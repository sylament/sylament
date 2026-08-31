<?php

declare(strict_types=1);

use Sylament\Panels\Controllers\PageController;
use Sylament\Panels\DependencyInjection\Compiler\RelationManagerComponentPass;
use Sylament\Panels\Resources\RelationManagers\RelationManager;
use Sylament\Panels\Tests\Fixtures\Resources\RelationManagers\TasksRelationManager;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * The negative fixture lives in the test file rather than under
 * `tests/Fixtures/`, where the Kernel's PSR-4 glob would autoconfigure it onto
 * `sylament.panels.relation_manager` and the pass' throw would kill every
 * Integration boot.
 */
final class RelationManagerWithoutLiveComponentAttribute extends RelationManager
{
    protected static string $relationship = 'tasks';
}

function relationManagerComponentContainer(): ContainerBuilder
{
    $container = new ContainerBuilder();
    $container->setDefinition(PageController::class, new Definition(PageController::class));

    $definition = new Definition(TasksRelationManager::class);
    $definition->addTag('sylament.panels.relation_manager');

    $container->setDefinition(TasksRelationManager::class, $definition);

    return $container;
}

it('adds the live twig.component and controller tags with the exact payload', function (): void {
    $container = relationManagerComponentContainer();

    new RelationManagerComponentPass()->process($container);

    $definition = $container->getDefinition(TasksRelationManager::class);
    $tags = $definition->getTag('twig.component');

    expect($tags)->toHaveCount(1)
        ->and($tags[0])->toBe([
            'key' => 'Sylament:Panels:Tests:Fixtures:Resources:RelationManagers:TasksRelationManager',
            'live' => true,
            'route' => 'ux_live_component',
            'method' => 'post',
            'url_reference_type' => UrlGeneratorInterface::ABSOLUTE_PATH,
            'expose_public_props' => true,
            'attributes_var' => 'attributes',
            'template_from_method' => 'getTemplate',
        ])
        ->and($definition->hasTag('controller.service_arguments'))->toBeTrue();
});

it('throws when a relation manager lacks the AsLiveComponent attribute', function (): void {
    $container = new ContainerBuilder();

    $definition = new Definition(RelationManagerWithoutLiveComponentAttribute::class);
    $definition->addTag('sylament.panels.relation_manager');

    $container->setDefinition(RelationManagerWithoutLiveComponentAttribute::class, $definition);

    expect(static function () use ($container): void {
        new RelationManagerComponentPass()->process($container);
    })->toThrow(LogicException::class, RelationManagerWithoutLiveComponentAttribute::class);
});

it('replaces the attribute-derived twig.component tag rather than doubling it', function (): void {
    $container = relationManagerComponentContainer();
    $definition = $container->getDefinition(TasksRelationManager::class);
    // The keyless stand-in for the tag attribute autoconfiguration materializes
    // from the bare attribute before this pass runs.
    $definition->addTag('twig.component', ['live' => true]);

    new RelationManagerComponentPass()->process($container);

    $tags = $definition->getTag('twig.component');

    expect($tags)->toHaveCount(1)
        ->and($tags[0]['key'])->toBe(TasksRelationManager::getComponentName())
        ->and($tags[0]['template_from_method'])->toBe('getTemplate');
});

it('throws when two relation managers derive the same component name', function (): void {
    $container = relationManagerComponentContainer();
    $duplicate = new Definition(TasksRelationManager::class);
    $duplicate->addTag('sylament.panels.relation_manager');

    $container->setDefinition('duplicate.relation_manager', $duplicate);

    new RelationManagerComponentPass()->process($container);
})->throws(LogicException::class);

it('skips abstract definitions', function (): void {
    $container = relationManagerComponentContainer();
    $abstract = new Definition(RelationManager::class);
    $abstract->setAbstract(true);
    $abstract->addTag('sylament.panels.relation_manager');

    $container->setDefinition('abstract.relation_manager', $abstract);

    new RelationManagerComponentPass()->process($container);

    expect($container->getDefinition('abstract.relation_manager')->hasTag('twig.component'))->toBeFalse();
});

it('leaves the page controller locator alone', function (): void {
    $container = relationManagerComponentContainer();

    new RelationManagerComponentPass()->process($container);

    expect($container->getDefinition(PageController::class)->getArguments())->toBe([]);
});
