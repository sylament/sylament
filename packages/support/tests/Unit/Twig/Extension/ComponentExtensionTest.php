<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\Twig\Extension;

use Sylament\Support\Service\Invoker;
use Sylament\Support\Tests\Fixtures\Component\ViewComponentFixture;
use Sylament\Support\Twig\Extension\ComponentExtension;
use Symfony\Component\DependencyInjection\Container;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Markup;

beforeEach(function (): void {
    Invoker::setInstance(new Invoker(new Container()));
});

afterEach(function (): void {
    Invoker::unsetInstance();
});

it('registers the renderComponent function', function (): void {
    $functions = new ComponentExtension()->getFunctions();

    expect($functions)->toHaveCount(1)
        ->and($functions[0]->getName())->toBe('renderComponent');
});

it('returns null for a null component', function (): void {
    $twig = new Environment(new ArrayLoader());

    expect(new ComponentExtension()->renderComponent($twig, null))->toBeNull();
});

it('returns null for a component without a template', function (): void {
    $twig = new Environment(new ArrayLoader());

    expect(new ComponentExtension()->renderComponent($twig, new ViewComponentFixture()))->toBeNull();
});

it('wraps the rendered component in a Markup instance', function (): void {
    $twig = new Environment(new ArrayLoader([
        'component.html.twig' => '<b>Hello</b>',
    ]));

    $rendered = new ComponentExtension()->renderComponent($twig, new ViewComponentFixture()->template('component.html.twig'));

    expect($rendered)->toBeInstanceOf(Markup::class)
        ->and((string) $rendered)->toBe('<b>Hello</b>');
});

it('keeps component html unescaped through variable indirection while plain strings escape', function (): void {
    // The Markup return value carries safety itself: unlike the old is_safe flag,
    // it must survive {% set %} indirection, where compile-time safety analysis is lost.
    $twig = new Environment(new ArrayLoader([
        'page.html.twig' => '{% set html = renderComponent(component) %}{{ html }}|{{ plain }}',
        'component.html.twig' => '<b>{{ name }}</b>',
    ]));
    $twig->addExtension(new ComponentExtension());

    $rendered = $twig->render('page.html.twig', [
        'component' => new ViewComponentFixture()->template('component.html.twig', ['name' => 'world']),
        'plain' => '<b>x</b>',
    ]);

    expect($rendered)->toBe('<b>world</b>|&lt;b&gt;x&lt;/b&gt;');
});
