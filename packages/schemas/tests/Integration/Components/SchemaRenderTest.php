<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Integration\Components;

use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Concerns\HasSchemasFixture;

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
