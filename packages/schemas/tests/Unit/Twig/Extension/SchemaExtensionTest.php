<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Twig\Extension;

use RuntimeException;
use stdClass;
use Sylament\Schemas\Tests\Fixtures\Concerns\HasSchemasFixture;
use Sylament\Schemas\Twig\Extension\SchemaExtension;
use Sylament\Support\Twig\Extension\ComponentExtension;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Markup;

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
        '@SylamentSchemas/partials/Schema/index.html.twig' => 'schema:{{ component.getComponents()|length }}',
    ]));
    $twig->addExtension(new ComponentExtension());

    $rendered = schemaExtension()->renderSchema($twig, ['this' => new HasSchemasFixture()], 'schema');

    expect($rendered)->toBeInstanceOf(Markup::class)
        ->and((string) $rendered)->toBe('schema:1');
});

it('defaults to the "schema" name', function (): void {
    $twig = new Environment(new ArrayLoader([
        '@SylamentSchemas/partials/Schema/index.html.twig' => 'schema:{{ component.getComponents()|length }}',
    ]));
    $twig->addExtension(new ComponentExtension());

    $rendered = schemaExtension()->renderSchema($twig, ['this' => new HasSchemasFixture()]);

    expect((string) $rendered)->toBe('schema:1');
});

it('throws when the context has no rendering component', function (): void {
    $twig = new Environment(new ArrayLoader());

    expect(fn (): ?Markup => schemaExtension()->renderSchema($twig, [], 'schema'))
        ->toThrow(RuntimeException::class);
});

it('throws when the rendering component does not interact with schemas', function (): void {
    $twig = new Environment(new ArrayLoader());

    expect(fn (): ?Markup => schemaExtension()->renderSchema($twig, ['this' => new stdClass()], 'schema'))
        ->toThrow(RuntimeException::class);
});
