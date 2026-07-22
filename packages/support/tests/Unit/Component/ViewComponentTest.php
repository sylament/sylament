<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\Component;

use Sylament\Support\Tests\Fixtures\Component\ViewComponentFixture;
use Sylament\Support\Tests\Fixtures\Component\ViewComponentWithExtraTemplateDataFixture;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

it('has no template by default', function (): void {
    expect(new ViewComponentFixture()->getTemplate())->toBeNull();
});

it('returns a string template as-is', function (): void {
    $component = new ViewComponentFixture()->template('component.html.twig');

    expect($component->getTemplate())->toBe('component.html.twig');
});

it('evaluates a closure template', function (): void {
    $component = new ViewComponentFixture()->template(fn (): string => 'component.html.twig');

    expect($component->getTemplate())->toBe('component.html.twig');
});

it('clears the template with null', function (): void {
    $component = new ViewComponentFixture()
        ->template('component.html.twig')
        ->template(null);

    expect($component->getTemplate())->toBeNull();
});

it('has no template data by default', function (): void {
    expect(new ViewComponentFixture()->getTemplateData())->toBe([]);
});

it('registers template data passed to template()', function (): void {
    $component = new ViewComponentFixture()->template('component.html.twig', ['name' => 'world']);

    expect($component->getTemplateData())->toBe(['name' => 'world']);
});

it('merges template data from multiple calls with later keys winning', function (): void {
    $component = new ViewComponentFixture()
        ->templateData(['name' => 'first', 'color' => 'blue'])
        ->templateData(['name' => 'second']);

    expect($component->getTemplateData())->toBe(['name' => 'second', 'color' => 'blue']);
});

it('evaluates closure template data when read, not when registered', function (): void {
    $name = 'before';
    $component = new ViewComponentFixture()->templateData(function () use (&$name): array {
        return ['name' => $name];
    });
    $name = 'after';

    expect($component->getTemplateData())->toBe(['name' => 'after']);
});

it('ignores closure template data that evaluates to an empty array', function (): void {
    $component = new ViewComponentFixture()
        ->templateData(fn (): array => [])
        ->templateData(['name' => 'world']);

    expect($component->getTemplateData())->toBe(['name' => 'world']);
});

it('returns no extra template data by default', function (): void {
    expect(new ViewComponentFixture()->getExtraTemplateData())->toBe([]);
});

it('returns null from render() when no template is set', function (): void {
    $twig = new Environment(new ArrayLoader());

    expect(new ViewComponentFixture()->render($twig))->toBeNull();
});

it('renders the template through Twig with the template data', function (): void {
    $twig = new Environment(new ArrayLoader([
        'component.html.twig' => 'Hello {{ name }}!',
    ]));

    $component = new ViewComponentFixture()->template('component.html.twig', ['name' => 'world']);

    expect($component->render($twig))->toBe('Hello world!');
});

it('exposes the component instance to the template', function (): void {
    $twig = new Environment(new ArrayLoader([
        'component.html.twig' => '{{ component.getTemplate() }}',
    ]));

    $component = new ViewComponentFixture()->template('component.html.twig');

    expect($component->render($twig))->toBe('component.html.twig');
});

it('lets template data override the component key when rendering', function (): void {
    $twig = new Environment(new ArrayLoader([
        'component.html.twig' => '{{ component }}',
    ]));

    $component = new ViewComponentFixture()->template('component.html.twig', ['component' => 'overridden']);

    expect($component->render($twig))->toBe('overridden');
});

it('lets template data override extra template data when rendering', function (): void {
    $twig = new Environment(new ArrayLoader([
        'component.html.twig' => '{{ name }} from {{ source }}',
    ]));

    $component = new ViewComponentWithExtraTemplateDataFixture()
        ->template('component.html.twig')
        ->templateData(['name' => 'override']);

    expect($component->render($twig))->toBe('override from extra');
});
