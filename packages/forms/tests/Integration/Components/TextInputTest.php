<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components;

use Sylament\Forms\Components\TextInput;
use Sylament\Forms\Tests\Fixtures\Concerns\HasSchemasFixture;
use Sylament\Schemas\Schema;
use Symfony\Component\DomCrawler\Crawler;

use function Sylament\Forms\Tests\renderToHtml;

/**
 * Render a field as the child of a real Schema, mirroring how a field is used: the
 * parent Schema is its container, supplying the id/state-path cascade and the live
 * binding context. A bare Schema (no state path) leaves the absolute path unprefixed,
 * so `title` stays `title`.
 */
function renderField(TextInput $input): string
{
    return renderToHtml(Schema::make(new HasSchemasFixture())->components([$input]));
}

it('renders the field wrapper chrome around the input', function (): void {
    $crawler = new Crawler(renderField(
        TextInput::make('title')->label('Title')->maxLength(120)->markAsRequired(),
    ));

    $wrapper = $crawler->filter('div.sy.sy-fo-field');
    expect($wrapper->count())->toBe(1);

    $label = $crawler->filter('label.sy-fo-field-label');
    expect($label->count())->toBe(1)
        ->and($label->attr('for'))->toBe('title')
        ->and($label->filter('.sy-fo-field-label-content')->text())->toContain('Title')
        ->and($label->filter('.sy-fo-field-label-required-mark')->text())->toBe('*');

    $input = $crawler->filter('input.sy-fo-input');
    expect($input->count())->toBe(1)
        ->and($input->attr('type'))->toBe('text')
        ->and($input->attr('name'))->toBe('title')
        ->and($input->attr('id'))->toBe('title')
        ->and($input->attr('maxlength'))->toBe('120')
        ->and($input->attr('required'))->not->toBeNull()
        ->and($input->attr('data-model'))->toBe('norender|title');
});

it('binds the input to its absolute state path via data-model', function (): void {
    // Standalone default: state syncs but suppresses the re-render.
    $default = new Crawler(renderField(TextInput::make('title')));
    expect($default->filter('input.sy-fo-input')->attr('data-model'))->toBe('norender|title');

    // live() drops the modifier to a bare path (re-render on input).
    $live = new Crawler(renderField(TextInput::make('title')->live()));
    expect($live->filter('input.sy-fo-input')->attr('data-model'))->toBe('title');
});

it('renders helper text in the helper slot', function (): void {
    $crawler = new Crawler(renderField(
        TextInput::make('title')->helperText('Some help'),
    ));

    $helper = $crawler->filter('.sy-fo-field-helper');
    expect($helper->count())->toBe(1)
        ->and($helper->text())->toBe('Some help');
});

it('renders the hint text in the hint slot', function (): void {
    $crawler = new Crawler(renderField(
        TextInput::make('title')->hint('Need a hand'),
    ));

    $hint = $crawler->filter('.sy-fo-field-hint');
    expect($hint->count())->toBe(1)
        ->and($hint->text())->toContain('Need a hand');
});

it('renders the hint icon through the support icon mechanism', function (): void {
    $crawler = new Crawler(renderField(
        TextInput::make('title')->hint('Need a hand')->hintIcon('heroicons:star'),
    ));

    $hint = $crawler->filter('.sy-fo-field-hint');
    expect($hint->count())->toBe(1)
        ->and($hint->filter('svg')->count())->toBe(1);
});

it('makes the label screen-reader-only with hiddenLabel', function (): void {
    $crawler = new Crawler(renderField(
        TextInput::make('title')->hiddenLabel(),
    ));

    $label = $crawler->filter('label.sy-fo-field-label');
    expect($label->count())->toBe(1)
        ->and($label->attr('class'))->toContain('sy-fo-sr-only')
        ->and($label->attr('for'))->toBe('title')
        ->and($label->text())->toBe('Title');

    // The sr-only branch is a bare label with no visible content span.
    expect($crawler->filter('.sy-fo-field-label-content')->count())->toBe(0);
});

it('derives the input type from the type sugar', function (string $method, string $type): void {
    $input = new Crawler(renderField(
        TextInput::make('title')->{$method}(),
    ))->filter('input.sy-fo-input');

    expect($input->attr('type'))->toBe($type);
})->with([
    'email' => ['email', 'email'],
    'numeric' => ['numeric', 'number'],
    'password' => ['password', 'password'],
]);

it('emits inputmode=decimal for numeric inputs only', function (): void {
    $numeric = new Crawler(renderField(TextInput::make('amount')->numeric()));
    expect($numeric->filter('input.sy-fo-input')->attr('inputmode'))->toBe('decimal');

    $text = new Crawler(renderField(TextInput::make('title')));
    expect($text->filter('input.sy-fo-input')->attr('inputmode'))->toBeNull();
});

it('renders the remaining html attributes', function (): void {
    $input = new Crawler(renderField(
        TextInput::make('title')
            ->placeholder('Type here')
            ->minLength(3)
            ->autofocus(),
    ))->filter('input.sy-fo-input');

    expect($input->attr('placeholder'))->toBe('Type here')
        ->and($input->attr('minlength'))->toBe('3')
        ->and($input->attr('autofocus'))->not->toBeNull();
});

it('honors an explicit type over the sugar', function (): void {
    $input = new Crawler(renderField(
        TextInput::make('title')->type('search')->numeric(),
    ))->filter('input.sy-fo-input');

    expect($input->attr('type'))->toBe('search');
});
