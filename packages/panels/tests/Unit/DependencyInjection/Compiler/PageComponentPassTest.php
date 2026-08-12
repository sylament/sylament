<?php

declare(strict_types=1);

use Sylament\Panels\DependencyInjection\Compiler\PageComponentPass;
use Sylament\Panels\Http\PageController;
use Sylament\Panels\Pages\Page;
use Sylament\Panels\Tests\Fixtures\Pages\SettingsPage;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

function pageComponentContainer(): ContainerBuilder
{
    $container = new ContainerBuilder();
    $container->setDefinition(PageController::class, new Definition(PageController::class));

    $definition = new Definition(SettingsPage::class);
    $definition->addTag('sylament.panels.page');

    $container->setDefinition(SettingsPage::class, $definition);

    return $container;
}

it('adds the live twig.component and controller tags with the exact payload', function (): void {
    $container = pageComponentContainer();

    new PageComponentPass()->process($container);

    $definition = $container->getDefinition(SettingsPage::class);
    $tags = $definition->getTag('twig.component');

    expect($tags)->toHaveCount(1)
        ->and($tags[0])->toBe([
            'key' => 'Sylament:Panels:Tests:Fixtures:Pages:SettingsPage',
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

it('throws when two pages derive the same component name', function (): void {
    $container = pageComponentContainer();
    $duplicate = new Definition(SettingsPage::class);
    $duplicate->addTag('sylament.panels.page');

    $container->setDefinition('duplicate.page', $duplicate);

    new PageComponentPass()->process($container);
})->throws(LogicException::class);

it('skips abstract definitions', function (): void {
    $container = pageComponentContainer();
    $abstract = new Definition(Page::class);
    $abstract->setAbstract(true);
    $abstract->addTag('sylament.panels.page');

    $container->setDefinition('abstract.page', $abstract);

    new PageComponentPass()->process($container);

    expect($container->getDefinition('abstract.page')->hasTag('twig.component'))->toBeFalse();
});
