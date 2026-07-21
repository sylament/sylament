<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Integration\Components;

use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Concerns\HasSchemasFixture;
use Sylament\Support\Enum\Alignment;

use function Sylament\Schemas\Tests\renderToHtml;

function schema(): Schema
{
    return Schema::make(new HasSchemasFixture());
}

it('renders a single-column grid with the marker classes by default', function (): void {
    $html = renderToHtml(schema());

    expect($html)->toContain('sy sy-sc')
        ->toContain('sy-grid-cols')
        ->toContain('--cols-default: repeat(1, minmax(0, 1fr))')
        ->not->toContain('lg:sy-grid-cols');
});

it('maps an integer column count onto the lg breakpoint', function (): void {
    $html = renderToHtml(schema()->columns(2));

    expect($html)->toContain('lg:sy-grid-cols')
        ->toContain('--cols-lg: repeat(2, minmax(0, 1fr))');
});

it('renders a responsive breakpoint map', function (): void {
    $html = renderToHtml(schema()->columns(['md' => 2, 'xl' => 4]));

    expect($html)->toContain('md:sy-grid-cols')
        ->toContain('xl:sy-grid-cols')
        ->toContain('--cols-md: repeat(2, minmax(0, 1fr))')
        ->toContain('--cols-xl: repeat(4, minmax(0, 1fr))');
});

it('merges successive columns() calls into one grid', function (): void {
    $html = renderToHtml(schema()->columns(['md' => 2])->columns(['xl' => 4]));

    expect($html)->toContain('md:sy-grid-cols')
        ->toContain('xl:sy-grid-cols');
});

it('resolves columns from a closure through the real Invoker', function (): void {
    $html = renderToHtml(schema()->columns(fn (): array => ['md' => 3]));

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
    expect(renderToHtml(schema()))->toContain('sy-sc-has-gap');
    expect(renderToHtml(schema()->gap(false)))->not->toContain('sy-sc-has-gap');
});

it('evaluates a gap closure when rendering', function (): void {
    expect(renderToHtml(schema()->gap(fn (): bool => false)))->not->toContain('sy-sc-has-gap');
});

it('marks a dense schema with the dense class', function (): void {
    expect(renderToHtml(schema()->dense()))->toContain('sy-sc-dense');
    expect(renderToHtml(schema()))->not->toContain('sy-sc-dense');
});

it('renders extra attributes on the schema root', function (): void {
    $html = renderToHtml(schema()->extraAttributes(['data-owner' => 'billing']));

    expect($html)->toContain('data-owner="billing"');
});

it('appends extra classes after the schema and grid classes', function (): void {
    $html = renderToHtml(schema()->extraAttributes(['class' => 'custom-class']));

    expect($html)->toMatch('/class="sy sy-sc [^"]*custom-class"/');
});

it('merges an extra style declaration into the grid style attribute', function (): void {
    $html = renderToHtml(schema()->extraAttributes(['style' => 'color: red']));

    expect($html)->toMatch('/style="[^"]*--cols-default[^"]*color: red[^"]*"/');
});

it('escapes extra attribute values', function (): void {
    $html = renderToHtml(schema()->extraAttributes(['data-x' => '"><script>alert(1)</script>']));

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
    expect(renderToHtml(schema()->alignEnd()))->toContain('sy-align-end');
    expect(renderToHtml(schema()->alignment(Alignment::Center)))->toContain('sy-align-center');
});

it('emits a custom alignment string as a class verbatim', function (): void {
    $html = renderToHtml(schema()->alignment('my-custom-alignment'));

    expect($html)->toMatch('/class="sy sy-sc my-custom-alignment[ "]/')
        ->not->toContain('sy-align-');
});

it('renders no alignment class by default', function (): void {
    expect(renderToHtml(schema()))->not->toContain('sy-align-');
});

it('keeps the grid on an aligned non-inline schema', function (): void {
    $html = renderToHtml(schema()->alignEnd());

    expect($html)->toContain('sy-align-end')
        ->toContain('--cols-default');
});
