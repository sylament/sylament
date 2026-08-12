<?php

declare(strict_types=1);

use Sylament\Panels\Pages\Dashboard;
use Sylament\Panels\Tests\Fixtures\Pages\SettingsPage;
use Symfony\UX\TwigComponent\ComponentFactory;

it('registers page subclasses as live components with the runtime template getter', function (): void {
    $factory = $this::getContainer()->get('ux.twig_component.component_factory');
    assert($factory instanceof ComponentFactory);

    $metadata = $factory->metadataFor(SettingsPage::getComponentName());

    expect($metadata->get('live'))->toBeTrue()
        ->and($metadata->getTemplateFromMethod())->toBe('getTemplate')
        ->and($factory->metadataFor(Dashboard::getComponentName())->get('live'))->toBeTrue();
});
