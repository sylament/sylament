<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components;

use Sylament\Forms\Components\Textarea;
use Sylament\Forms\Tests\Fixtures\Concerns\HasSchemasFixture;
use Sylament\Schemas\Schema;
use Symfony\Component\DomCrawler\Crawler;

use function Sylament\Forms\Tests\renderToHtml;

/**
 * Render a field as the child of a real Schema, mirroring how a field is used: the
 * parent Schema is its container, supplying the id/state-path cascade and the live
 * binding context. A bare Schema (no state path) leaves the absolute path unprefixed,
 * so `excerpt` stays `excerpt`.
 *
 * Named distinctly from TextInputTest's `renderField()`: both files share the
 * `Sylament\Forms\Tests\Integration\Components` namespace, and PHP fatals on two
 * same-named function declarations in one namespace regardless of parameter types.
 */
function renderTextareaField(Textarea $textarea): string
{
    return renderToHtml(Schema::make(new HasSchemasFixture())->components([$textarea]));
}

it('renders the same field wrapper chrome as TextInput around the textarea', function (): void {
    $crawler = new Crawler(renderTextareaField(
        Textarea::make('excerpt')->label('Excerpt')->markAsRequired(),
    ));

    $wrapper = $crawler->filter('div.sy.sy-fo-field');
    expect($wrapper->count())->toBe(1);

    $label = $crawler->filter('label.sy-fo-field-label');
    expect($label->count())->toBe(1)
        ->and($label->attr('for'))->toBe('excerpt')
        ->and($label->filter('.sy-fo-field-label-content')->text())->toContain('Excerpt')
        ->and($label->filter('.sy-fo-field-label-required-mark')->text())->toBe('*');

    $textarea = $crawler->filter('textarea.sy-fo-input');
    expect($textarea->count())->toBe(1)
        ->and($textarea->attr('name'))->toBe('excerpt')
        ->and($textarea->attr('id'))->toBe('excerpt')
        ->and($textarea->text())->toBe('');
});

it('binds the textarea to its absolute state path via data-model', function (): void {
    // Standalone default: state syncs but suppresses the re-render.
    $default = new Crawler(renderTextareaField(Textarea::make('excerpt')));
    expect($default->filter('textarea.sy-fo-input')->attr('data-model'))->toBe('norender|excerpt');

    // live() drops the modifier to a bare path (re-render on input).
    $live = new Crawler(renderTextareaField(Textarea::make('excerpt')->live()));
    expect($live->filter('textarea.sy-fo-input')->attr('data-model'))->toBe('excerpt');
});

it('renders the rows and cols attributes', function (): void {
    $textarea = new Crawler(renderTextareaField(
        Textarea::make('excerpt')->rows(6)->cols(40),
    ))->filter('textarea.sy-fo-input');

    expect($textarea->attr('rows'))->toBe('6')
        ->and($textarea->attr('cols'))->toBe('40');
});

it('omits the rows and cols attributes when unset', function (): void {
    $textarea = new Crawler(renderTextareaField(
        Textarea::make('excerpt'),
    ))->filter('textarea.sy-fo-input');

    expect($textarea->attr('rows'))->toBeNull()
        ->and($textarea->attr('cols'))->toBeNull();
});

it('renders helper text in the helper slot', function (): void {
    $crawler = new Crawler(renderTextareaField(
        Textarea::make('excerpt')->helperText('Some help'),
    ));

    $helper = $crawler->filter('.sy-fo-field-helper');
    expect($helper->count())->toBe(1)
        ->and($helper->text())->toBe('Some help');
});

it('renders the hint text in the hint slot', function (): void {
    $crawler = new Crawler(renderTextareaField(
        Textarea::make('excerpt')->hint('Need a hand'),
    ));

    $hint = $crawler->filter('.sy-fo-field-hint');
    expect($hint->count())->toBe(1)
        ->and($hint->text())->toContain('Need a hand');
});

it('renders the remaining html attributes', function (): void {
    $textarea = new Crawler(renderTextareaField(
        Textarea::make('excerpt')
            ->placeholder('Type here')
            ->maxLength(500)
            ->minLength(3)
            ->autofocus(),
    ))->filter('textarea.sy-fo-input');

    expect($textarea->attr('placeholder'))->toBe('Type here')
        ->and($textarea->attr('maxlength'))->toBe('500')
        ->and($textarea->attr('minlength'))->toBe('3')
        ->and($textarea->attr('autofocus'))->not->toBeNull();
});
