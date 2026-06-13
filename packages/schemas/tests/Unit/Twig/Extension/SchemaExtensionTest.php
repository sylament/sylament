<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Twig\Extension;

use RuntimeException;
use stdClass;
use Sylament\Schemas\Tests\Fixtures\Concerns\HasSchemasFixture;
use Sylament\Schemas\Twig\Extension\SchemaExtension;
use Sylament\Support\Service\Invoker;
use Sylament\Support\Twig\Extension\ComponentExtension;
use Symfony\Component\DependencyInjection\Container;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

beforeEach(function (): void {
    Invoker::setInstance(new Invoker(new Container()));
});

afterEach(function (): void {
    Invoker::unsetInstance();
});

function schemaExtension(): SchemaExtension
{
    return new SchemaExtension(new ComponentExtension());
}

it('registers the renderSchema function', function (): void {
    $functions = schemaExtension()->getFunctions();

    expect($functions)->toHaveCount(1)
        ->and($functions[0]->getName())->toBe('renderSchema');
});

it('renders the named schema of the component in context', function (): void {
    $twig = new Environment(new ArrayLoader([
        '@SylamentSchemas/components/Schema.html.twig' => 'schema:{{ component.getComponents()|length }}',
    ]));
    $twig->addExtension(new ComponentExtension());

    $rendered = schemaExtension()->renderSchema($twig, ['this' => new HasSchemasFixture()], 'schema');

    expect($rendered)->toBe('schema:1');
});

it('throws when the context has no rendering component', function (): void {
    $twig = new Environment(new ArrayLoader());

    expect(fn (): ?string => schemaExtension()->renderSchema($twig, [], 'schema'))
        ->toThrow(RuntimeException::class);
});

it('throws when the rendering component does not interact with schemas', function (): void {
    $twig = new Environment(new ArrayLoader());

    expect(fn (): ?string => schemaExtension()->renderSchema($twig, ['this' => new stdClass()], 'schema'))
        ->toThrow(RuntimeException::class);
});
