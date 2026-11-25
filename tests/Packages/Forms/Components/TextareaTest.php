<?php

declare(strict_types=1);

use Sylament\Forms\Components\Textarea;
use Sylament\Schemas\Components\Section;
use Sylament\Tests\Fixtures\Forms\Selectors;
use Sylament\Tests\Fixtures\Forms\TextareaForm;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

describe('rendering', function (): void {
    it('renders its state as the text of its control', function (): void {
        expect($this->createLiveComponent(TextareaForm::class, ['data' => ['plain' => 'Hello']])->render()->crawler())
            ->toContainElement('.sy-fo-textarea textarea' . Selectors::INPUT . '[name="plain"]', 'Hello');
    });

    it('renders its placeholder, disabled and autofocus marks on its control, its wrapper greyed out while disabled', function (): void {
        expect($this->renderSchemaComponent(Textarea::make('notes')->placeholder('Your notes')->disabled()->autofocus()))
            ->toContainElement('.sy-fo-textarea' . SupportSelectors::DISABLED . ' textarea' . Selectors::INPUT . '[name="notes"][placeholder="Your notes"][disabled][autofocus]');
    });

    it('renders its length bounds on its control', function (): void {
        expect($this->renderSchemaComponent(Textarea::make('notes')->minLength(2)->maxLength(9)))
            ->toContainElement('textarea[name="notes"][minlength="2"][maxlength="9"]');
    });
});

describe('sizing', function (): void {
    it('renders the rows and columns it is given, and neither when unset', function (): void {
        expect($this->renderSchemaComponent(Textarea::make('notes')->rows(4)->cols(30)))
            ->toContainElement('textarea[name="notes"][rows="4"][cols="30"]')
            ->and($this->renderSchemaComponent(Textarea::make('notes')))
            ->toContainElement('textarea[name="notes"]:not([rows]):not([cols])');
    });
});

describe('autosize', function (): void {
    it('wraps its control in a sizer its controller grows, as tall as its rows to begin with', function (Closure $field, string $height): void {
        expect($this->renderSchemaComponent($field()))
            ->toContainElement(".sy-fo-textarea.sy-autosizable .sy-fo-textarea-sizer[style=\"height: {$height}rem\"][data-controller=\"sylament--forms--textarea\"][data-sylament--forms--textarea-initial-height-value=\"{$height}\"] textarea" . Selectors::INPUT . '[name="notes"][data-sylament--forms--textarea-target="textarea"][data-action="input->sylament--forms--textarea#resize"]');
    })->with([
        'four rows' => [fn (): Textarea => Textarea::make('notes')->rows(4)->autosize(), '6.75'],
        'no rows' => [fn (): Textarea => Textarea::make('notes')->autosize(), '3.75'],
    ]);

    it('renders its bare control with no sizer and no controller unless it autosizes', function (): void {
        $render = $this->renderSchemaComponent(Textarea::make('notes'));

        expect($render)
            ->toContainElement('.sy-fo-textarea:not(.sy-autosizable) textarea' . Selectors::INPUT . '[name="notes"]:not([data-action])')
            ->not->toContainElement('.sy-fo-textarea-sizer')
            ->not->toContainElement('[data-controller="sylament--forms--textarea"]');
    });
});

describe('errors', function (): void {
    it('marks its control invalid while it has an error, and valid otherwise', function (): void {
        $tester = $this->createLiveComponent(TextareaForm::class);

        expect($tester->render()->crawler())->toContainElement('textarea[name="notes"]:not([aria-invalid])')
            ->and($tester->call('save')->render()->crawler())
            ->toContainElement('textarea[name="notes"][aria-invalid="true"]')
            ->toContainElement('textarea[name="plain"]:not([aria-invalid])');
    });
});

describe('state hooks', function (): void {
    it('validates and saves its stripped or trimmed value and leaves what was typed, and saves an untouched one as typed', function (): void {
        $tester = $this->createLiveComponent(TextareaForm::class)
            ->fillForm(['masked' => 'a-b-c', 'trimmed' => '  abc  ', 'plain' => ' a-b '])
            ->call('save')
            ->assertHasNoFormErrors(['masked', 'trimmed']);

        expect($tester->component()->getSchema('form')->getStateSnapshot())
            ->toHaveKey('masked', 'abc')
            ->toHaveKey('trimmed', 'abc')
            ->toHaveKey('plain', ' a-b ')
            ->and($tester->component()->data)
            ->toHaveKey('masked', 'a-b-c')
            ->toHaveKey('trimmed', '  abc  ');
    });
});

describe('native constraints', function (): void {
    it('keeps its native required and length checks inside a collapsible section', function (): void {
        expect($this->renderSchemaComponent(Section::make('Details')->collapsible()->components([Textarea::make('notes')->required()->minLength(2)->maxLength(9)])))
            ->toContainElement('textarea[name="notes"][required][minlength="2"][maxlength="9"]');
    });
});
