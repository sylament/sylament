<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Integration\Components;

use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Concerns\HasSchemasFixture;
use Twig\Markup;

use function Sylament\Schemas\Tests\renderToHtml;

/**
 * Renders a Section as a child of a real Schema, mirroring how it is used: the parent
 * Schema assigns itself as the Section's container, which supplies the live component
 * the Section's child schema needs. Asserting on the parent render exercises the whole
 * chain — Section + heading/description + nested child schema + the support Twig component.
 */
function renderSection(Section $section): string
{
    return renderToHtml(Schema::make(new HasSchemasFixture())->components([$section]));
}

it('renders the heading and description in the section header', function (): void {
    $html = renderSection(Section::make('Profile')->description('Your account details'));

    expect($html)->toContain('sy-section-header')
        ->toContain('Profile')
        ->toContain('Your account details');
});

it('resolves a heading closure through the real Invoker', function (): void {
    $html = renderSection(Section::make(fn (): string => 'Computed heading'));

    expect($html)->toContain('Computed heading');
});

it('omits the header when the section has no heading or description', function (): void {
    $html = renderSection(Section::make());

    expect($html)->not->toContain('sy-section-header');
});

it('renders the section child components inside the content area', function (): void {
    $html = renderSection(Section::make('Details')->components([new Component()]));

    // The child Schema renders its own grid inside the section content container.
    expect($html)->toContain('sy-section-content')
        ->toContain('sy-grid');
});

it('renders an empty child schema for a section with no components', function (): void {
    // Regression: an empty default slot must still resolve to a schema (not null),
    // so the content container renders rather than collapsing.
    $html = renderSection(Section::make('Empty'));

    expect($html)->toContain('sy-section-content');
});

it('escapes a plain string heading and description', function (): void {
    $html = renderSection(Section::make('<b>Heading</b>')->description('<i>Description</i>'));

    expect($html)->toContain('&lt;b&gt;Heading&lt;/b&gt;')
        ->toContain('&lt;i&gt;Description&lt;/i&gt;')
        ->not->toContain('<b>Heading</b>')
        ->not->toContain('<i>Description</i>');
});

it('renders a Markup heading and description unescaped', function (): void {
    $html = renderSection(
        Section::make(new Markup('<em>Rich heading</em>', 'UTF-8'))
            ->description(new Markup('<em>Rich description</em>', 'UTF-8')),
    );

    expect($html)->toContain('<em>Rich heading</em>')
        ->toContain('<em>Rich description</em>');
});

it('renders the heading in an h2 by default', function (): void {
    $html = renderSection(Section::make('Profile'));

    expect($html)->toContain('<h2 class="sy-section-header-heading">');
});

it('omits the header for a blank heading', function (string $heading): void {
    $html = renderSection(Section::make($heading));

    expect($html)->not->toContain('sy-section-header');
})->with([
    'empty string' => [''],
    'whitespace only' => ['   '],
]);
