<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Integration\Components;

use Sylament\Schemas\Components\Grid;
use Sylament\Schemas\Components\Text;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Concerns\HasSchemasFixture;

use function Sylament\Schemas\Tests\renderToHtml;

/**
 * Renders the Grid as a child of a real Schema, the way it is used: the parent
 * Schema assigns itself as the Grid's container, which supplies the live component
 * the Grid's own child schema needs.
 */
function renderGrid(Grid $grid): string
{
    return renderToHtml(Schema::make(new HasSchemasFixture())->components([$grid]));
}

it('renders a host div carrying the schema-component classes', function (): void {
    $html = renderGrid(Grid::make());

    expect($html)->toContain('class="sy sy-sc-grid"');
});

it('passes its columns down to the child schema as css variables', function (): void {
    $html = renderGrid(Grid::make(3)->components([Text::make('Title')]));

    expect($html)->toContain('--cols-lg: repeat(3, minmax(0, 1fr))')
        ->toContain('lg:sy-grid-cols');
});

it('renders its children', function (): void {
    $html = renderGrid(Grid::make(2)->components([Text::make('Title')]));

    expect($html)->toContain('sy-sc-text')
        ->toContain('Title');
});

it('renders no id attribute without a custom id', function (): void {
    $html = renderGrid(Grid::make());

    expect($html)->not->toContain(' id=');
});

it('renders a custom id and extra attributes on the host div', function (): void {
    $html = renderGrid(Grid::make()->id('layout')->extraAttributes(['data-role' => 'grid']));

    expect($html)->toContain('id="layout"')
        ->toContain('data-role="grid"');
});

it('prefers the component id over an extra attributes id', function (): void {
    $html = renderGrid(Grid::make()->id('real')->extraAttributes(['id' => 'fake']));

    expect($html)->toContain('id="real"')
        ->not->toContain('fake');
});
