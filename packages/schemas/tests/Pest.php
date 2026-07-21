<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests;

use Sylament\Support\Component\ComponentManager;
use Sylament\Support\Component\ViewComponent;

pest()
    ->extend(TestCase::class)
    ->in('Integration');

pest()
    ->afterEach(function (): void {
        ComponentManager::unsetInstance();
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
