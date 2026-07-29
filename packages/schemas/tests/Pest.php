<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests;

use Sylament\Support\Components\ComponentManager;
use Sylament\Support\Components\ViewComponent;
use Sylament\Support\DependencyInjection\ComponentResolver;
use Sylament\Support\Icons\IconManager;
use Sylament\Support\Evaluation\Invoker;
use Symfony\Component\DependencyInjection\Container;

pest()
    ->extend(TestCase::class)
    ->in('Integration');

pest()
    ->beforeEach(function (): void {
        Invoker::setInstance(new Invoker(new Container()));
    })
    ->afterEach(function (): void {
        Invoker::unsetInstance();
        ComponentManager::unsetInstance();
        IconManager::unsetInstance();
        ComponentResolver::unsetInstance();
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
