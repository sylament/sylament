<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests;

use Sylament\Forms\Components\Field;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\PropertyAccessor;
use Sylament\Support\Components\ComponentManager;
use Sylament\Support\Components\ViewComponent;
use Sylament\Support\Icons\IconManager;
use Sylament\Support\Evaluation\Invoker;
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

/**
 * Render a form field as the child of a real Schema bound to a `data`-bag host,
 * mirroring how a field is used: the parent Schema is its container, supplying the
 * id/state-path cascade and the live binding context. `->statePath('data')` prefixes
 * the field's absolute path (so `title` becomes `data.title`), and the host's empty
 * `$data` bag lets `getState()` read back null — an unset field renders an empty value.
 */
function renderField(Field $field): string
{
    return renderToHtml(Schema::make(new FieldRenderHost())->statePath('data')->components([$field]));
}

/**
 * Wire a field into a real parent Schema so its child slot schemas can reach the
 * live component through the container chain, as happens in a real render. Returns
 * the field for chaining slot assertions (mirrors schemas' `wireSection()`).
 */
function wireField(Field $field): Field
{
    Schema::make(new FieldRenderHost())->components([$field])->getComponents();

    return $field;
}
