<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components;

use Sylament\Forms\Components\Textarea;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Schemas\Schema;
use Symfony\Component\DomCrawler\Crawler;

use function Sylament\Forms\Tests\renderField;

it('renders the same field wrapper chrome as TextInput around the textarea', function (): void {
    $crawler = new Crawler(renderField(
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
    $default = new Crawler(renderField(Textarea::make('excerpt')));
    expect($default->filter('textarea.sy-fo-input')->attr('data-model'))->toBe('norender|data.excerpt');

    // live() drops the modifier to a bare path (re-render on input).
    $live = new Crawler(renderField(Textarea::make('excerpt')->live()));
    expect($live->filter('textarea.sy-fo-input')->attr('data-model'))->toBe('data.excerpt');
});

it('renders the rows and cols attributes', function (): void {
    $textarea = new Crawler(renderField(
        Textarea::make('excerpt')->rows(6)->cols(40),
    ))->filter('textarea.sy-fo-input');

    expect($textarea->attr('rows'))->toBe('6')
        ->and($textarea->attr('cols'))->toBe('40');
});

it('omits the rows and cols attributes when unset', function (): void {
    $textarea = new Crawler(renderField(
        Textarea::make('excerpt'),
    ))->filter('textarea.sy-fo-input');

    expect($textarea->attr('rows'))->toBeNull()
        ->and($textarea->attr('cols'))->toBeNull();
});

it('renders helper text in the below-content slot', function (): void {
    $crawler = new Crawler(renderField(
        Textarea::make('excerpt')->helperText('Some help'),
    ));

    $helper = $crawler->filter('.sy-fo-field-content-col .sy-sc-text');
    expect($helper->count())->toBe(1)
        ->and($helper->text())->toBe('Some help');
});

it('renders the hint text in the after-label slot', function (): void {
    $crawler = new Crawler(renderField(
        Textarea::make('excerpt')->hint('Need a hand'),
    ));

    $hint = $crawler->filter('.sy-fo-field-label-ctn .sy-sc-text');
    expect($hint->count())->toBe(1)
        ->and($hint->text())->toContain('Need a hand');
});

it('renders the remaining html attributes', function (): void {
    $textarea = new Crawler(renderField(
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

it('renders the disabled attribute when disabled', function (): void {
    $enabled = new Crawler(renderField(Textarea::make('excerpt')));
    expect($enabled->filter('textarea.sy-fo-input')->attr('disabled'))->toBeNull();

    $disabled = new Crawler(renderField(Textarea::make('excerpt')->disabled()));
    expect($disabled->filter('textarea.sy-fo-input')->attr('disabled'))->not->toBeNull();
});

it('renders the readonly attribute only when read-only', function (): void {
    $writable = new Crawler(renderField(Textarea::make('excerpt')));
    expect($writable->filter('textarea.sy-fo-input')->attr('readonly'))->toBeNull();

    $readOnly = new Crawler(renderField(Textarea::make('excerpt')->readOnly()));
    expect($readOnly->filter('textarea.sy-fo-input')->attr('readonly'))->not->toBeNull();
});

it('renders the autocomplete attribute only when set', function (): void {
    $unset = new Crawler(renderField(Textarea::make('excerpt')));
    expect($unset->filter('textarea.sy-fo-input')->attr('autocomplete'))->toBeNull();

    $off = new Crawler(renderField(Textarea::make('excerpt')->autocomplete(false)));
    expect($off->filter('textarea.sy-fo-input')->attr('autocomplete'))->toBe('off');
});

it('merges extra input attributes onto the textarea and lets them win', function (): void {
    $crawler = new Crawler(renderField(
        Textarea::make('excerpt')->rows(3)->extraInputAttributes([
            'data-extra' => 'yes',
            'rows' => 8,
        ]),
    ));

    $textarea = $crawler->filter('textarea.sy-fo-input');

    expect($textarea->attr('data-extra'))->toBe('yes')
        ->and($textarea->attr('rows'))->toBe('8');
});

it('trims and strips exactly as TextInput does', function (): void {
    $host = new FieldRenderHost();
    $host->data = ['n' => ' 1,234 '];

    $field = Textarea::make('n')->trim()->stripCharacters(',');
    $schema = Schema::make($host)->statePath('data')->components([$field]);
    $schema->getComponents();

    expect($schema->mutateStateForValidation(['data.n' => ' 1,234 ']))->toBe(['data.n' => '1234'])
        ->and($schema->getStateSnapshot())->toBe(['n' => '1234'])
        ->and($host->data['n'])->toBe(' 1,234 ')
        ->and($field->mutatesStateForValidation())->toBeTrue();
});

it('renders the three Grammarly opt-out attributes only when disabled', function (): void {
    $default = new Crawler(renderField(Textarea::make('excerpt')))->filter('textarea.sy-fo-input');

    expect($default->attr('data-gramm'))->toBeNull()
        ->and($default->attr('data-gramm_editor'))->toBeNull()
        ->and($default->attr('data-enable-grammarly'))->toBeNull();

    $disabled = new Crawler(renderField(
        Textarea::make('excerpt')->disableGrammarly(),
    ))->filter('textarea.sy-fo-input');

    expect($disabled->attr('data-gramm'))->toBe('false')
        ->and($disabled->attr('data-gramm_editor'))->toBe('false')
        ->and($disabled->attr('data-enable-grammarly'))->toBe('false');
});

it('renders no sizer, no controller and no autosizable class without autosize()', function (): void {
    $crawler = new Crawler(renderField(Textarea::make('excerpt')));

    // The off arm is byte-identical to the template that shipped before autosize:
    // the control is the wrapper's whole content, unwrapped. Vendor's other branch —
    // a ResizeObserver mirroring the control's height onto the sizer — is not ported.
    expect($crawler->filter('.sy-fo-textarea-sizer')->count())->toBe(0)
        ->and($crawler->filter('div.sy-input-wrp [data-controller]')->count())->toBe(0)
        ->and($crawler->filter('div.sy-input-wrp')->attr('class'))->toBe('sy sy-input-wrp sy-fo-textarea')
        ->and($crawler->filter('.sy-input-wrp-content-ctn')->html())->toBe(
            '<textarea class="sy-fo-input" id="excerpt" name="excerpt" data-model="norender|data.excerpt" aria-invalid="false"></textarea>' . "\n    ",
        );
});

it('wraps the control in a controller-bound sizer under autosize()', function (): void {
    $crawler = new Crawler(renderField(Textarea::make('excerpt')->autosize()));

    $sizer = $crawler->filter('div.sy-fo-textarea-sizer');

    expect($crawler->filter('div.sy-input-wrp')->attr('class'))->toBe('sy sy-input-wrp sy-fo-textarea sy-autosizable')
        ->and($sizer->count())->toBe(1)
        ->and($sizer->attr('data-controller'))->toBe('sylament--forms--textarea')
        ->and($sizer->filter('textarea.sy-fo-input')->attr('data-sylament--forms--textarea-target'))->toBe('textarea');
});

it('derives the initial sizer height from the row count', function (): void {
    $default = new Crawler(renderField(Textarea::make('excerpt')->autosize()))->filter('div.sy-fo-textarea-sizer');

    expect($default->attr('style'))->toBe('height: 3.75rem')
        ->and($default->attr('data-sylament--forms--textarea-initial-height-value'))->toBe('3.75')
        // The height and the `rows` attribute are independent: an unset `rows` still
        // renders a height, and never a `rows="2"` the user did not ask for.
        ->and($default->filter('textarea.sy-fo-input')->attr('rows'))->toBeNull();

    $eight = new Crawler(renderField(Textarea::make('excerpt')->rows(8)->autosize()))->filter('div.sy-fo-textarea-sizer');

    expect($eight->attr('style'))->toBe('height: 12.75rem')
        ->and($eight->attr('data-sylament--forms--textarea-initial-height-value'))->toBe('12.75')
        ->and($eight->filter('textarea.sy-fo-input')->attr('rows'))->toBe('8');
});

it('resolves a closure autosize condition through evaluate', function (): void {
    $off = new Crawler(renderField(Textarea::make('excerpt')->autosize(fn (): bool => false)));

    expect($off->filter('div.sy-fo-textarea-sizer')->count())->toBe(0)
        ->and($off->filter('div.sy-input-wrp [data-controller]')->count())->toBe(0);

    $on = new Crawler(renderField(Textarea::make('excerpt')->autosize(fn (): bool => true)));

    expect($on->filter('div.sy-fo-textarea-sizer')->count())->toBe(1);
});
