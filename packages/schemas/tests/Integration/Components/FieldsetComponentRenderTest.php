<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Integration\Components;

use Sylament\Schemas\Components\Fieldset;
use Sylament\Schemas\Components\Text;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Concerns\HasSchemasFixture;

use function Sylament\Schemas\Tests\renderToHtml;

/**
 * Covers the schemas-level `Fieldset` view component rendered through the support
 * `<twig:SylamentSupport:Fieldset>` chrome. The support component on its own is
 * covered in FieldsetRenderTest.
 */
function renderFieldset(Fieldset $fieldset): string
{
    return renderToHtml(Schema::make(new HasSchemasFixture())->components([$fieldset]));
}

it('renders a fieldset with a legend and the schema-component hook class', function (): void {
    $html = renderFieldset(Fieldset::make('Address'));

    expect($html)->toContain('<fieldset')
        ->toContain('sy-fieldset')
        ->toContain('sy-sc-fieldset')
        ->toContain('<legend>Address</legend>');
});

it('omits the legend without a label', function (): void {
    $html = renderFieldset(Fieldset::make());

    expect($html)->not->toContain('<legend');
});

it('keeps a hidden label in the DOM behind the modifier class', function (): void {
    $html = renderFieldset(Fieldset::make('Address')->hiddenLabel());

    expect($html)->toContain('sy-fieldset-label-hidden')
        ->toContain('<legend>Address</legend>');
});

it('flags an uncontained fieldset', function (): void {
    $html = renderFieldset(Fieldset::make('Address')->contained(false));

    expect($html)->toContain('sy-fieldset-not-contained');
});

it('renders the required mark only after markAsRequired()', function (): void {
    expect(renderFieldset(Fieldset::make('Address')->markAsRequired()))
        ->toContain('sy-fieldset-label-required-mark')
        ->and(renderFieldset(Fieldset::make('Address')))
        ->not->toContain('sy-fieldset-label-required-mark');
});

it('passes its default two columns down to the child schema', function (): void {
    $html = renderFieldset(Fieldset::make('Address')->components([Text::make('Street')]));

    expect($html)->toContain('--cols-lg: repeat(2, minmax(0, 1fr))');
});

it('keeps the hook class alongside a custom class and extra attributes', function (): void {
    $html = renderFieldset(
        Fieldset::make('Address')
            ->id('address')
            ->extraAttributes(['class' => 'custom', 'data-role' => 'fieldset']),
    );

    expect($html)->toContain('sy-fieldset')
        ->toContain('sy-sc-fieldset')
        ->toContain('custom')
        ->toContain('id="address"')
        ->toContain('data-role="fieldset"');
});

it('prefers the component id over an extra attributes id', function (): void {
    $html = renderFieldset(Fieldset::make('Address')->id('real')->extraAttributes(['id' => 'fake']));

    expect($html)->toContain('id="real"')
        ->not->toContain('fake');
});
