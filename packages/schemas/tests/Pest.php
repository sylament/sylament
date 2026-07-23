<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests;

use Sylament\Schemas\State\PropertyAccessor;
use Sylament\Support\Component\ComponentManager;
use Sylament\Support\Component\ViewComponent;
use Sylament\Support\Icons\IconManager;
use Sylament\Support\Service\Invoker;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\PropertyAccess\PropertyAccess;

pest()
    ->extend(TestCase::class)
    ->in('Integration');

pest()
    ->beforeEach(function (): void {
        Invoker::setInstance(new Invoker(new Container()));
        PropertyAccessor::setInstance(new PropertyAccessor(PropertyAccess::createPropertyAccessor()));
    })
    ->afterEach(function (): void {
        Invoker::unsetInstance();
        PropertyAccessor::unsetInstance();
        ComponentManager::unsetInstance();
        IconManager::unsetInstance();
    })
    ->in('Unit');

/**
 * Render a real component through the booted kernel's Twig and return the HTML.
 * A single render exercises the fluent API, state resolution, closure evaluation
 * through the real Invoker, the Twig templates, and the registered extensions at once.
 */
function renderToHtml(ViewComponent $component): string
{
    return $component->render(test()->getContainer()->get('twig')) ?? '';
}
