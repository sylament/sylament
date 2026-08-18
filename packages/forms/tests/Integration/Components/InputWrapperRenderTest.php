<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components;

use Sylament\Actions\Action;
use Sylament\Forms\Components\Select;
use Sylament\Forms\Components\TextInput;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;
use Twig\Markup;

use function Sylament\Forms\Tests\renderField;

uses(InteractsWithLiveComponents::class);

/*
 * Support's `InputWrapper` has no render coverage of its own — support's fixture
 * kernel registers no TwigComponentBundle — so the five forms controls that
 * compose it are where its markup is pinned, the way actions pins Button and
 * Modal.
 */

it('renders a bare control inside the wrapper content container', function (): void {
    $crawler = new Crawler(renderField(TextInput::make('title')));

    $wrapper = $crawler->filter('div.sy.sy-input-wrp');
    expect($wrapper->count())->toBe(1)
        ->and($wrapper->attr('class'))->not->toContain('sy-disabled')
        ->and($wrapper->attr('class'))->not->toContain('sy-invalid')
        // A control with no affixes opens neither side, so the wrapper is content only.
        ->and($crawler->filter('.sy-input-wrp-prefix')->count())->toBe(0)
        ->and($crawler->filter('.sy-input-wrp-suffix')->count())->toBe(0)
        ->and($crawler->filter('.sy-input-wrp > .sy-input-wrp-content-ctn > input.sy-fo-input')->count())->toBe(1);
});

it('renders the prefix affixes in action, icon, label order', function (): void {
    $crawler = new Crawler(renderField(
        TextInput::make('title')
            ->prefixAction(Action::make('reset'))
            ->prefixIcon('heroicons:star')
            ->prefix('kg', isInline: true),
    ));

    $prefix = $crawler->filter('.sy-input-wrp-prefix');
    expect($prefix->count())->toBe(1)
        ->and($prefix->attr('class'))->toContain('sy-inline')
        ->and($prefix->attr('class'))->toContain('sy-input-wrp-prefix-has-label');

    $children = $prefix->children();
    expect($children->count())->toBe(3)
        ->and($children->eq(0)->attr('class'))->toBe('sy-input-wrp-actions')
        ->and($children->eq(0)->filter('button.sy-ac')->count())->toBe(1)
        ->and($children->eq(1)->nodeName())->toBe('svg')
        ->and($children->eq(2)->attr('class'))->toBe('sy-input-wrp-label')
        ->and($children->eq(2)->text())->toBe('kg');
});

it('renders the suffix affixes in label, icon, action order', function (): void {
    $crawler = new Crawler(renderField(
        TextInput::make('title')
            ->suffixAction(Action::make('clear'))
            ->suffixIcon('heroicons:check-20-solid')
            ->suffix('EUR'),
    ));

    $suffix = $crawler->filter('.sy-input-wrp-suffix');
    expect($suffix->count())->toBe(1)
        // The last affix setter decided the side is not inline, so only the label modifier lands.
        ->and($suffix->attr('class'))->not->toContain('sy-inline')
        ->and($suffix->attr('class'))->toContain('sy-input-wrp-suffix-has-label');

    $children = $suffix->children();
    expect($children->count())->toBe(3)
        ->and($children->eq(0)->attr('class'))->toBe('sy-input-wrp-label')
        ->and($children->eq(0)->text())->toBe('EUR')
        ->and($children->eq(1)->nodeName())->toBe('svg')
        ->and($children->eq(2)->attr('class'))->toBe('sy-input-wrp-actions')
        ->and($children->eq(2)->filter('button.sy-ac')->count())->toBe(1);
});

it('strips the control padding on the side an inline affix occupies', function (): void {
    $inline = new Crawler(renderField(
        TextInput::make('title')->prefix('kg', isInline: true)->suffix('EUR', isInline: true),
    ))->filter('input.sy-fo-input');

    expect($inline->attr('class'))->toContain('sy-fo-input-has-inline-prefix')
        ->and($inline->attr('class'))->toContain('sy-fo-input-has-inline-suffix');

    // A non-inline affix sits outside the control's box, so its padding stays.
    $separate = new Crawler(renderField(
        TextInput::make('title')->prefix('kg')->suffix('EUR'),
    ))->filter('input.sy-fo-input');

    expect($separate->attr('class'))->toBe('sy-fo-input');
});

it('marks the wrapper disabled', function (): void {
    $crawler = new Crawler(renderField(TextInput::make('title')->disabled()));

    expect($crawler->filter('div.sy-input-wrp')->attr('class'))->toContain('sy-disabled');
});

it('marks the wrapper invalid from the host error bag', function (): void {
    $crawler = $this->createLiveComponent('ValidatedArticleForm', ['validatedSchemas' => ['schema']])
        ->render()
        ->crawler();

    $wrappers = $crawler->filter('div.sy-input-wrp');
    // The text input is required and empty; the textarea is unconstrained.
    expect($wrappers->count())->toBe(2)
        ->and($wrappers->eq(0)->filter('input[name="title"]')->count())->toBe(1)
        ->and($wrappers->eq(0)->attr('class'))->toContain('sy-invalid')
        ->and($wrappers->eq(1)->filter('textarea[name="excerpt"]')->count())->toBe(1)
        ->and($wrappers->eq(1)->attr('class'))->not->toContain('sy-invalid');
});

it('renders the visible affix actions only', function (): void {
    $crawler = new Crawler(renderField(
        TextInput::make('title')
            ->suffixActions([
                Action::make('shown'),
                Action::make('gone')->hidden(),
            ]),
    ));

    $actions = $crawler->filter('.sy-input-wrp-suffix .sy-input-wrp-actions button.sy-ac');
    expect($actions->count())->toBe(1)
        ->and($actions->attr('aria-label'))->toBe('Shown');
});

it('opens no prefix for an affix that renders nothing', function (): void {
    $crawler = new Crawler(renderField(
        TextInput::make('title')
            ->prefix(new Markup('', 'UTF-8'))
            ->prefixAction(Action::make('gone')->hidden()),
    ));

    expect($crawler->filter('.sy-input-wrp-prefix')->count())->toBe(0);
});

it('keeps the text input inline class for a hidden prefix action and drops the select one', function (): void {
    // Filament-exact: the text input computes its inline class against the
    // unfiltered actions, the select against the visible ones.
    $input = new Crawler(renderField(
        TextInput::make('title')->prefixAction(Action::make('gone')->hidden(), isInline: true),
    ))->filter('input.sy-fo-input');

    expect($input->attr('class'))->toContain('sy-fo-input-has-inline-prefix');

    $select = new Crawler(renderField(
        Select::make('author')->options(['a' => 'A'])->prefixAction(Action::make('gone')->hidden(), isInline: true),
    ))->filter('select.sy-fo-select');

    expect($select->attr('class'))->toBe('sy-fo-select');
});
