<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Integration;

use Sylament\Schemas\Colors\Components\IconComponent;
use Sylament\Schemas\Colors\Components\TextComponent;
use Sylament\Schemas\State\PropertyAccessor;
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
