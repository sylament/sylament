<?php

declare(strict_types=1);

use Sylament\Panels\Controllers\PageController;
use Sylament\Panels\DependencyInjection\Compiler\PageComponentPass;
use Sylament\Panels\Pages\Page;
use Sylament\Panels\Tests\Fixtures\Pages\SettingsPage;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * The negative fixture lives in the test file rather than under
 * `tests/Fixtures/`, where the Kernel's PSR-4 glob would autoconfigure it onto
 * `sylament.panels.page` and the pass' throw would kill every Integration boot.
 */
final class PageWithoutLiveComponentAttribute extends Page
{
}

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

it('throws when a page class lacks the AsLiveComponent attribute', function (): void {
    $container = new ContainerBuilder();
    $container->setDefinition(PageController::class, new Definition(PageController::class));

    $definition = new Definition(PageWithoutLiveComponentAttribute::class);
    $definition->addTag('sylament.panels.page');

    $container->setDefinition(PageWithoutLiveComponentAttribute::class, $definition);

    expect(static function () use ($container): void {
        new PageComponentPass()->process($container);
    })->toThrow(LogicException::class, PageWithoutLiveComponentAttribute::class);
});

it('replaces the attribute-derived twig.component tag rather than doubling it', function (): void {
    $container = pageComponentContainer();
    $definition = $container->getDefinition(SettingsPage::class);
    // What `AttributeAutoconfigurationPass` + `ResolveInstanceofConditionalsPass`
    // have already materialized from the bare attribute by the time this pass runs.
    $definition->addTag('twig.component', ['live' => true]);

    new PageComponentPass()->process($container);

    $tags = $definition->getTag('twig.component');

    expect($tags)->toHaveCount(1)
        ->and($tags[0]['key'])->toBe(SettingsPage::getComponentName())
        ->and($tags[0]['template_from_method'])->toBe('getTemplate');
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
