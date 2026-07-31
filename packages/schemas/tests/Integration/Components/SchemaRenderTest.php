<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Integration\Components;

use Sylament\Actions\Action;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Components\Text;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Concerns\HasSchemasFixture;
use Sylament\Support\Enums\Alignment;
use Twig\Markup;

use function Sylament\Schemas\Tests\renderToHtml;

function schema(): Schema
{
    return Schema::make(new HasSchemasFixture());
}

/**
 * An empty schema renders nothing (Filament parity), so root-markup assertions
 * need at least one visible child for the root div to exist at all.
 */
function schemaWithChild(): Schema
{
    return schema()->components([new Component()]);
}

it('renders a single-column grid with the marker classes by default', function (): void {
    $html = renderToHtml(schemaWithChild());

    expect($html)->toContain('sy sy-sc')
        ->toContain('sy-grid-cols')
        ->toContain('--cols-default: repeat(1, minmax(0, 1fr))')
        ->not->toContain('lg:sy-grid-cols');
});

it('maps an integer column count onto the lg breakpoint', function (): void {
    $html = renderToHtml(schemaWithChild()->columns(2));

    expect($html)->toContain('lg:sy-grid-cols')
        ->toContain('--cols-lg: repeat(2, minmax(0, 1fr))');
});

it('renders a responsive breakpoint map', function (): void {
    $html = renderToHtml(schemaWithChild()->columns(['md' => 2, 'xl' => 4]));

    expect($html)->toContain('md:sy-grid-cols')
        ->toContain('xl:sy-grid-cols')
        ->toContain('--cols-md: repeat(2, minmax(0, 1fr))')
        ->toContain('--cols-xl: repeat(4, minmax(0, 1fr))');
});

it('merges successive columns() calls into one grid', function (): void {
    $html = renderToHtml(schemaWithChild()->columns(['md' => 2])->columns(['xl' => 4]));

    expect($html)->toContain('md:sy-grid-cols')
        ->toContain('xl:sy-grid-cols');
});

it('resolves columns from a closure through the real Invoker', function (): void {
    $html = renderToHtml(schemaWithChild()->columns(fn (): array => ['md' => 3]));

    expect($html)->toContain('md:sy-grid-cols')
        ->toContain('--cols-md: repeat(3, minmax(0, 1fr))');
});

it('wraps each child in a single-column grid wrapper by default', function (): void {
    $html = renderToHtml(schema()->components([new Component()]));

    expect($html)->toMatch('/<div class="sy-grid-col"[^>]*>\s*<div class="sy-sc-component">/')
        ->toContain('--col-span-default: span 1 / span 1');
});

it('maps an integer column span onto the lg breakpoint of the child wrapper', function (): void {
    $html = renderToHtml(schema()->components([new Component()->columnSpan(2)]));

    expect($html)->toContain('lg:sy-grid-col-span')
        ->toContain('--col-span-default: span 1 / span 1')
        ->toContain('--col-span-lg: span 2 / span 2');
});

it('spans a child across the full grid width with columnSpanFull()', function (): void {
    $html = renderToHtml(schema()->components([new Component()->columnSpanFull()]));

    expect($html)->toContain('--col-span-default: 1 / -1');
});

it('marks a child column start with its class and variable', function (): void {
    $html = renderToHtml(schema()->components([new Component()->columnStart(2)]));

    expect($html)->toContain('lg:sy-grid-col-start')
        ->toContain('--col-start-lg: 2');
});

it('marks a child column order with its class and variable', function (): void {
    $html = renderToHtml(schema()->components([new Component()->columnOrder(2)]));

    expect($html)->toContain('lg:sy-grid-col-order')
        ->toContain('--col-order-lg: 2');
});

it('resolves a column span closure through the real Invoker', function (): void {
    $html = renderToHtml(schema()->components([new Component()->columnSpan(fn (): array => ['md' => 2])]));

    expect($html)->toContain('md:sy-grid-col-span')
        ->toContain('--col-span-md: span 2 / span 2');
});

it('carries the gap class by default and drops it when gap is disabled', function (): void {
    expect(renderToHtml(schemaWithChild()))->toContain('sy-sc-has-gap');
    expect(renderToHtml(schemaWithChild()->gap(false)))->not->toContain('sy-sc-has-gap');
});

it('evaluates a gap closure when rendering', function (): void {
    expect(renderToHtml(schemaWithChild()->gap(fn (): bool => false)))->not->toContain('sy-sc-has-gap');
});

it('marks a dense schema with the dense class', function (): void {
    expect(renderToHtml(schemaWithChild()->dense()))->toContain('sy-sc-dense');
    expect(renderToHtml(schemaWithChild()))->not->toContain('sy-sc-dense');
});

it('renders extra attributes on the schema root', function (): void {
    $html = renderToHtml(schemaWithChild()->extraAttributes(['data-owner' => 'billing']));

    expect($html)->toContain('data-owner="billing"');
});

it('appends extra classes after the schema and grid classes', function (): void {
    $html = renderToHtml(schemaWithChild()->extraAttributes(['class' => 'custom-class']));

    expect($html)->toMatch('/class="sy sy-sc [^"]*custom-class"/');
});

it('merges an extra style declaration into the grid style attribute', function (): void {
    $html = renderToHtml(schemaWithChild()->extraAttributes(['style' => 'color: red']));

    expect($html)->toMatch('/style="[^"]*--cols-default[^"]*color: red[^"]*"/');
});

it('escapes extra attribute values', function (): void {
    $html = renderToHtml(schemaWithChild()->extraAttributes(['data-x' => '"><script>alert(1)</script>']));

    expect($html)->not->toContain('<script>')
        ->toContain('&quot;&gt;&lt;script&gt;');
});

it('renders an inline schema as a dense flex row without the grid', function (): void {
    $html = renderToHtml(schema()->inline()->components([new Component()]));

    expect($html)->toContain('sy-inline')
        ->toContain('sy-sc-dense')
        ->toContain('sy-sc-component')
        ->not->toContain('sy-grid')
        ->not->toContain('--cols-default')
        ->not->toContain('--col-span');
});

it('marks a growing child of an inline schema', function (): void {
    $html = renderToHtml(schema()->inline()->components([new Component()->grow()]));

    expect($html)->toMatch('/<div class="sy-growable">\s*<div class="sy-sc-component">/');
});

it('marks no child as growable outside an inline schema or without grow()', function (): void {
    expect(renderToHtml(schema()->components([new Component()->grow()])))->not->toContain('sy-growable');
    expect(renderToHtml(schema()->inline()->components([new Component()])))->not->toContain('sy-growable');
});

it('renders the alignment class from the enum', function (): void {
    expect(renderToHtml(schemaWithChild()->alignEnd()))->toContain('sy-align-end');
    expect(renderToHtml(schemaWithChild()->alignment(Alignment::Center)))->toContain('sy-align-center');
});

it('emits a custom alignment string as a class verbatim', function (): void {
    $html = renderToHtml(schemaWithChild()->alignment('my-custom-alignment'));

    expect($html)->toMatch('/class="sy sy-sc my-custom-alignment[ "]/')
        ->not->toContain('sy-align-');
});

it('renders no alignment class by default', function (): void {
    expect(renderToHtml(schemaWithChild()))->not->toContain('sy-align-');
});

it('drops a blank alignment instead of emitting an empty class token', function (): void {
    $html = renderToHtml(schemaWithChild()->alignment(''));

    preg_match('/<div\s+class="([^"]*)"/', $html, $matches);

    expect($matches[1] ?? 'no-class-attribute')->not->toContain('  ')
        ->and($html)->not->toContain('sy-align-');
});

it('keeps the grid on an aligned non-inline schema', function (): void {
    $html = renderToHtml(schemaWithChild()->alignEnd());

    expect($html)->toContain('sy-align-end')
        ->toContain('--cols-default');
});

it('renders a string child as an escaped text span', function (): void {
    $html = renderToHtml(schema()->components(['<b>x</b>']));

    expect($html)->toContain('sy-sc-text')
        ->toContain('&lt;b&gt;x&lt;/b&gt;')
        ->not->toContain('<b>x</b>');
});

it('renders a Markup child raw through an Html component', function (): void {
    $html = renderToHtml(schema()->components([new Markup('<hr class="rule">', 'UTF-8')]));

    expect($html)->toContain('<hr class="rule">');
});

it('does not render a hidden child component', function (): void {
    $html = renderToHtml(schema()->components([Text::make('VISIBLE'), Text::make('HIDDEN')->hidden()]));

    expect($html)->toContain('VISIBLE')
        ->not->toContain('HIDDEN')
        ->and(mb_substr_count($html, 'sy-sc-component'))->toBe(1);
});

it('does not render a child hidden by a false visible() condition', function (): void {
    $html = renderToHtml(schema()->components([Text::make('SHOWN'), Text::make('GONE')->visible(false)]));

    expect($html)->toContain('SHOWN')
        ->not->toContain('GONE');
});

it('does not render a hidden section', function (): void {
    $html = renderToHtml(schema()->components([Section::make('Secret')->components(['body'])->hidden()]));

    expect($html)->not->toContain('sy-sc-section')
        ->not->toContain('Secret');
});

it('renders a visible section while omitting its hidden child', function (): void {
    $html = renderToHtml(schema()->components([
        Section::make('Shown')->components([Text::make('INNER'), Text::make('DROPPED')->hidden()]),
    ]));

    expect($html)->toContain('sy-sc-section')
        ->toContain('INNER')
        ->not->toContain('DROPPED');
});

it('hides a section whose children are all hidden with hiddenWhenAllChildComponentsHidden', function (): void {
    $html = renderToHtml(schema()->components([
        Section::make('Empty')->components([Text::make('x')->hidden()])->hiddenWhenAllChildComponentsHidden(),
    ]));

    expect($html)->not->toContain('sy-sc-section')
        ->not->toContain('Empty');
});

it('renders an action child inside an action wrapper without a grid cell', function (): void {
    $html = renderToHtml(schema()->components([Action::make('publish')]));

    expect($html)->toContain('<div class="sy-sc-action">')
        ->toContain('sy sy-btn sy-size-md sy-color sy-color-primary sy-bg-color-400 hover:sy-bg-color-300 dark:sy-bg-color-600 dark:hover:sy-bg-color-500 sy-text-color-900 hover:sy-text-color-800 dark:sy-text-color-950 dark:hover:sy-text-color-950 sy-ac')
        ->toContain('Publish')
        ->not->toContain('sy-sc-component')
        ->not->toContain('--col-span');
});

it('does not render a hidden action child or a placeholder for it', function (): void {
    $html = renderToHtml(schema()->components([Text::make('KEPT'), Action::make('publish')->hidden()]));

    expect($html)->toContain('KEPT')
        ->not->toContain('sy-sc-action')
        ->not->toContain('Publish');
});

it('renders nothing for an empty schema', function (): void {
    expect(renderToHtml(schema()))->toBe('');
});

it('renders nothing for a schema whose children are all hidden', function (): void {
    $html = renderToHtml(schema()->components([Text::make('GONE')->hidden(), Action::make('gone')->hidden()]));

    expect($html)->toBe('');
});
