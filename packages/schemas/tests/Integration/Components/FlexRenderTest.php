<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Integration\Components;

use Sylament\Actions\Action;
use Sylament\Schemas\Components\Flex;
use Sylament\Schemas\Components\Text;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Concerns\HasSchemasFixture;

use function Sylament\Schemas\Tests\renderToHtml;

/**
 * Renders the Flex row as a child of a real Schema, the way it is used: the parent
 * Schema assigns itself as the container, which supplies the live component the
 * Flex's own child schema needs, and the density it inherits.
 */
function renderFlex(Flex $flex): string
{
    return renderToHtml(Schema::make(new HasSchemasFixture())->components([$flex]));
}

it('renders the default breakpoint and no alignment classes', function (): void {
    $html = renderFlex(Flex::make([Text::make('one')]));

    expect($html)->toContain('class="sy sy-sc-flex sy-from-default"')
        ->not->toContain('sy-vertical-align-')
        ->not->toContain('sy-align-')
        ->not->toContain('sy-dense');
});

it('renders the from breakpoint', function (): void {
    $html = renderFlex(Flex::make([Text::make('one')])->from('md'));

    expect($html)->toContain('sy-from-md')
        ->not->toContain('sy-from-default');
});

it('renders the vertical alignment class', function (): void {
    $html = renderFlex(Flex::make([Text::make('one')])->verticallyAlignCenter());

    expect($html)->toContain('sy-vertical-align-center');
});

it('passes a custom vertical alignment string through as a class', function (): void {
    $html = renderFlex(Flex::make([Text::make('one')])->verticalAlignment('my-align'));

    expect($html)->toContain('sy-sc-flex sy-from-default my-align');
});

it('renders the alignment class', function (): void {
    $html = renderFlex(Flex::make([Text::make('one')])->alignBetween());

    expect($html)->toContain('sy-align-between');
});

it('renders the dense class', function (): void {
    $html = renderFlex(Flex::make([Text::make('one')])->dense());

    expect($html)->toContain('sy-dense');
});

it('wraps a growable child in a growable div', function (): void {
    $html = renderFlex(Flex::make([Text::make('one')]));

    expect($html)->toMatch('/<div class="sy-growable">\s*<span[^>]*sy-sc-text/');
});

it('wraps a non-growable child in a bare div', function (): void {
    $html = renderFlex(Flex::make([Text::make('one')->grow(false)]));

    expect($html)->not->toContain('sy-growable')
        ->toMatch('/<div\s*>\s*<span[^>]*sy-sc-text/');
});

it('wraps an action child in a bare div', function (): void {
    $html = renderFlex(Flex::make([Action::make('publish')]));

    expect($html)->not->toContain('sy-growable')
        ->toContain('Publish');
});

it('omits a hidden child', function (): void {
    $html = renderFlex(Flex::make([Text::make('shown'), Text::make('gone')->hidden()]));

    expect($html)->toContain('shown')
        ->not->toContain('gone');
});

it('merges extra attributes onto the root', function (): void {
    $html = renderFlex(Flex::make([Text::make('one')])->extraAttributes(['data-role' => 'row']));

    expect($html)->toContain('data-role="row"')
        ->toContain('sy-sc-flex');
});
