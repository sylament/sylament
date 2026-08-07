<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components;

use Sylament\Forms\Components\TextInput;
use Sylament\Forms\Tests\Fixtures\Components\FixturePrefixedField;
use Symfony\Component\DomCrawler\Crawler;

use function Sylament\Forms\Tests\renderField;

it('renders a label prefix inside the label element', function (): void {
    $crawler = new Crawler(renderField(
        FixturePrefixedField::make('agree')->label('Agree'),
    ));

    $label = $crawler->filter('label.sy-fo-field-label');
    expect($label->count())->toBe(1)
        ->and($label->filter('.fixture-prefix')->count())->toBe(1)
        ->and($label->filter('.sy-fo-field-label-content')->text())->toContain('Agree');
});

it('renders the label element for a prefix even with no visible label', function (): void {
    $crawler = new Crawler(renderField(
        FixturePrefixedField::make('agree')->hiddenLabel(),
    ));

    $label = $crawler->filter('label.sy-fo-field-label:not(.sy-fo-sr-only)');
    expect($label->count())->toBe(1)
        ->and($label->filter('.fixture-prefix')->count())->toBe(1)
        ->and($label->filter('.sy-fo-field-label-content')->count())->toBe(0);
});

it('renders a label suffix after the label content', function (): void {
    $crawler = new Crawler(renderField(
        FixturePrefixedField::make('agree')->label('Agree')->rendersPrefix(false)->rendersSuffix(true),
    ));

    $label = $crawler->filter('label.sy-fo-field-label');
    expect($label->count())->toBe(1)
        ->and($label->filter('.fixture-prefix')->count())->toBe(0)
        ->and($label->filter('.fixture-suffix')->count())->toBe(1);
});

it('renders the prefix, the label content and the suffix in that order', function (): void {
    $crawler = new Crawler(renderField(
        FixturePrefixedField::make('agree')->label('Agree')->rendersPrefix(true)->rendersSuffix(true),
    ));

    $classes = $crawler->filter('label.sy-fo-field-label')->children()->extract(['class']);

    expect($classes)->toBe(['fixture-prefix', 'sy-fo-field-label-content', 'fixture-suffix']);
});

it('marks the label as carrying an inline control', function (): void {
    $withPrefix = new Crawler(renderField(FixturePrefixedField::make('agree')->label('Agree')));
    expect($withPrefix->filter('label.sy-fo-field-label-inline-control')->count())->toBe(1);

    $withoutPrefix = new Crawler(renderField(
        FixturePrefixedField::make('agree')->label('Agree')->rendersPrefix(false),
    ));
    expect($withoutPrefix->filter('label.sy-fo-field-label-inline-control')->count())->toBe(0);
});

it('omits the label column entirely when nothing fills it', function (): void {
    $crawler = new Crawler(renderField(
        FixturePrefixedField::make('agree')->hiddenLabel()->rendersPrefix(false),
    ));

    expect($crawler->filter('.sy-fo-field-label-col')->count())->toBe(0);
});

it('merges extra field wrapper attributes onto the wrapper div', function (): void {
    $crawler = new Crawler(renderField(
        TextInput::make('title')->extraFieldWrapperAttributes(['data-wrapper' => 'yes']),
    ));

    expect($crawler->filter('div.sy.sy-fo-field')->attr('data-wrapper'))->toBe('yes');
});

it('lets the schemas extraAttributes win over extraFieldWrapperAttributes', function (): void {
    $crawler = new Crawler(renderField(
        TextInput::make('title')
            ->extraFieldWrapperAttributes(['data-who' => 'wrapper'])
            ->extraAttributes(['data-who' => 'component']),
    ));

    expect($crawler->filter('div.sy.sy-fo-field')->attr('data-who'))->toBe('component');
});
