<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components;

use Sylament\Forms\Components\Checkbox;
use Sylament\Forms\Components\DateTimePicker;
use Sylament\Forms\Components\FusedGroup;
use Sylament\Forms\Components\Repeater;
use Sylament\Forms\Components\Select;
use Sylament\Forms\Components\Textarea;
use Sylament\Forms\Components\TextInput;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Components\Tabs;
use Sylament\Schemas\Components\Tabs\Tab;
use Sylament\Schemas\Schema;
use Symfony\Component\DomCrawler\Crawler;

use function Sylament\Forms\Tests\renderRepeater;
use function Sylament\Forms\Tests\renderToHtml;

/**
 * Render every gated control inside a collapsible Section — a concealer — beside
 * an identical control in a plain Section, so each assertion has its own control.
 */
function renderConcealmentFixture(): Crawler
{
    return new Crawler(renderToHtml(
        Schema::make(new FieldRenderHost())->statePath('data')->components([
            Section::make('S')->collapsible()->components([
                TextInput::make('t')->required()->minLength(2)->maxLength(5)->minValue(1)->maxValue(9),
                Textarea::make('a')->required()->minLength(1)->maxLength(3),
                Checkbox::make('c')->required(),
                DateTimePicker::make('d')->required(),
                Select::make('s')->required()->options(['a' => 'A']),
                Select::make('js')->required()->searchable()->options(['a' => 'A']),
            ]),
            Section::make('Open')->components([
                TextInput::make('o')->required()->minLength(2)->maxLength(5)->minValue(1)->maxValue(9),
                Textarea::make('oa')->required()->minLength(1)->maxLength(3),
                Checkbox::make('oc')->required(),
                DateTimePicker::make('od')->required(),
                Select::make('os')->required()->options(['a' => 'A']),
                FusedGroup::make([TextInput::make('fmin'), TextInput::make('fmax')])->label('Range'),
            ]),
        ]),
    ));
}

/**
 * The motivating concealer: a required field on a tab nobody has opened. The control is
 * in the DOM from the first render, so the native constraint has to come off the same way
 * the collapsible Section's does.
 */
function renderTabConcealmentFixture(): Crawler
{
    return new Crawler(renderToHtml(
        Schema::make(new FieldRenderHost())->statePath('data')->components([
            Tabs::make('T')->tabs([
                Tab::make('A')->components([
                    TextInput::make('tt')->label('Tabbed')->required()->minLength(2)->maxLength(5),
                ]),
            ]),
        ]),
    ));
}

it('drops the text input constraint attributes when concealed', function (): void {
    $input = renderConcealmentFixture()->filter('input#t');

    expect($input->count())->toBe(1)
        ->and($input->attr('required'))->toBeNull()
        ->and($input->attr('minlength'))->toBeNull()
        ->and($input->attr('maxlength'))->toBeNull()
        ->and($input->attr('min'))->toBeNull()
        ->and($input->attr('max'))->toBeNull();
});

it('drops the textarea constraint attributes when concealed', function (): void {
    $textarea = renderConcealmentFixture()->filter('textarea#a');

    expect($textarea->count())->toBe(1)
        ->and($textarea->attr('required'))->toBeNull()
        ->and($textarea->attr('minlength'))->toBeNull()
        ->and($textarea->attr('maxlength'))->toBeNull();
});

it('drops required from a concealed checkbox, date-time picker and native select', function (): void {
    $crawler = renderConcealmentFixture();

    expect($crawler->filter('input#c')->attr('required'))->toBeNull()
        ->and($crawler->filter('input#d')->attr('required'))->toBeNull()
        ->and($crawler->filter('select#s')->attr('required'))->toBeNull();
});

it('keeps aria-required on a concealed non-native select', function (): void {
    $select = renderConcealmentFixture()->filter('select#js');

    expect($select->count())->toBe(1)
        ->and($select->attr('required'))->toBeNull()
        ->and($select->attr('aria-required'))->toBe('true');
});

it('keeps every constraint attribute on an unconcealed control', function (): void {
    $crawler = renderConcealmentFixture();

    $input = $crawler->filter('input#o');
    expect($input->attr('required'))->not->toBeNull()
        ->and($input->attr('minlength'))->toBe('2')
        ->and($input->attr('maxlength'))->toBe('5')
        ->and($input->attr('min'))->toBe('1')
        ->and($input->attr('max'))->toBe('9');

    $textarea = $crawler->filter('textarea#oa');
    expect($textarea->attr('required'))->not->toBeNull()
        ->and($textarea->attr('minlength'))->toBe('1')
        ->and($textarea->attr('maxlength'))->toBe('3');

    expect($crawler->filter('input#oc')->attr('required'))->not->toBeNull()
        ->and($crawler->filter('input#od')->attr('required'))->not->toBeNull()
        ->and($crawler->filter('select#os')->attr('required'))->not->toBeNull();
});

it('marks every field wrapper for the schema controller', function (): void {
    $crawler = renderConcealmentFixture();

    expect($crawler->filter('div.sy-fo-field[data-field-wrapper]')->count())
        ->toBe($crawler->filter('div.sy-fo-field')->count())
        ->and($crawler->filter('div.sy-fo-field')->count())->toBeGreaterThan(0);
});

it('marks a fused group child wrapper, the plain branch, for the schema controller', function (): void {
    $children = renderConcealmentFixture()->filter('div.sy-fo-fused-group div.sy-fo-field');

    expect($children->count())->toBe(2)
        ->and($children->eq(0)->attr('data-field-wrapper'))->not->toBeNull()
        ->and($children->eq(1)->attr('data-field-wrapper'))->not->toBeNull();
});

it('drops the constraint attributes from a control inside a tab', function (): void {
    $crawler = renderTabConcealmentFixture();
    $input = $crawler->filter('input#tt');

    expect($input->count())->toBe(1)
        ->and($input->attr('required'))->toBeNull()
        ->and($input->attr('minlength'))->toBeNull()
        ->and($input->attr('maxlength'))->toBeNull()
        ->and($input->attr('aria-invalid'))->toBe('false')
        ->and($crawler->filter('label[for="tt"]')->text())->toBe('Tabbed*')
        ->and($crawler->filter('div.sy-fo-field[data-field-wrapper]')->count())->toBe(1);
});

it('drops native constraints inside a collapsible repeater and keeps them inside a plain one', function (): void {
    $collapsible = renderRepeater(Repeater::make('tags')->collapsible()->components([TextInput::make('name')->required()->maxLength(5)]), ['tags' => [['name' => 'a']]]);
    $plain = renderRepeater(Repeater::make('tags')->components([TextInput::make('name')->required()->maxLength(5)]), ['tags' => [['name' => 'a']]]);

    expect($collapsible->filter('input[required]'))->toHaveCount(0)
        ->and($collapsible->filter('input[maxlength]'))->toHaveCount(0)
        ->and($plain->filter('input[required]'))->toHaveCount(1)
        ->and($plain->filter('input[maxlength="5"]'))->toHaveCount(1);
});
