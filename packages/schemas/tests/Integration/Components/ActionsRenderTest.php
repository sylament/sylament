<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Integration\Components;

use Sylament\Actions\Action;
use Sylament\Schemas\Components\Actions;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Concerns\HasSchemasFixture;

use function Sylament\Schemas\Tests\renderToHtml;

/**
 * Renders an Actions component as a child of a real Schema, mirroring real use:
 * the parent Schema assigns itself as the container, which supplies the live
 * component the default child schema holding the actions needs.
 */
function renderActions(Actions $actions): string
{
    return renderToHtml(Schema::make(new HasSchemasFixture())->components([$actions]));
}

it('renders the action buttons inside the actions row container', function (): void {
    $html = renderActions(Actions::make([Action::make('publish'), Action::make('archive')]));

    expect($html)->toContain('sy sy-sc-actions')
        ->toMatch('/sy-sc-actions-ctn"[^>]*>.*?<button[^>]*class="sy sy-btn sy-size-md sy-color sy-color-primary sy-ac".*?Publish.*?<button[^>]*class="sy sy-btn sy-size-md sy-color sy-color-primary sy-ac".*?Archive/s');
});

it('marks the row with the end alignment class that flips the flex order', function (): void {
    $html = renderActions(Actions::make([Action::make('publish')])->alignEnd());

    expect($html)->toContain('class="sy-sc-actions-ctn sy-align-end"');
});

it('renders a full-width row and suppresses the alignment class', function (): void {
    $html = renderActions(Actions::make([Action::make('publish')])->alignEnd()->fullWidth());

    expect($html)->toContain('class="sy-sc-actions-ctn sy-width-full"')
        ->not->toContain('sy-align-end');
});

it('omits hidden actions from the row while rendering the visible ones', function (): void {
    $html = renderActions(Actions::make([Action::make('publish'), Action::make('archive')->hidden()]));

    expect($html)->toContain('Publish')
        ->not->toContain('Archive');
});

it('renders the label heading above the actions row', function (): void {
    $html = renderActions(Actions::make([Action::make('publish')])->label('Steps'));

    expect($html)->toMatch('/sy-sc-actions-label-ctn">\s*<div class="sy-sc-actions-label">\s*Steps/');
});

it('omits the label container when no label is set', function (): void {
    $html = renderActions(Actions::make([Action::make('publish')]));

    expect($html)->not->toContain('sy-sc-actions-label');
});

it('renders nothing for an Actions component whose actions are all hidden', function (): void {
    $html = renderActions(Actions::make([Action::make('publish')->hidden()]));

    expect($html)->toBe('');
});
