<?php

declare(strict_types=1);

use Sylament\Forms\Components\Checkbox;
use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Exceptions\ValidationException;
use Sylament\Tests\Fixtures\Forms\BoolFieldsForm;
use Sylament\Tests\Fixtures\Forms\Selectors;
use Sylament\Tests\Fixtures\Schemas\Record\ParcelLabel;
use Sylament\Tests\Fixtures\Schemas\Selectors as SchemasSelectors;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

describe('rendering', function (): void {
    it('renders a valueless native checkbox bound to the state, with autofocus and disabled when set', function (): void {
        expect($this->renderSchemaComponent(Checkbox::make('terms')->autofocus()))
            ->toContainElement('input#schema\.terms[type="checkbox"][name="terms"]' . SchemasSelectors::boundTo('data.terms') . '[autofocus]:not([value]):not([disabled])')
            ->and($this->renderSchemaComponent(Checkbox::make('terms')->disabled()))
            ->toContainElement('input[name="terms"][disabled]:not([autofocus])');
    });

    it('renders its input once, beside the label when inline and in the content column otherwise', function (): void {
        $inline = $this->renderSchemaComponent(Checkbox::make('terms'));
        $stacked = $this->renderSchemaComponent(Checkbox::make('terms')->inline(false));

        expect($inline->filter('input[name="terms"]'))->toHaveCount(1)
            ->and($inline)->toContainElement(Selectors::LABEL_ROW . ' label[for="schema.terms"] input[name="terms"]')
            ->and($stacked->filter('input[name="terms"]'))->toHaveCount(1)
            ->and($stacked)->toContainElement(Selectors::CONTENT_COLUMN . ' input[name="terms"]');
    });

    it('renders checked from the state before any script runs', function (): void {
        $tester = $this->createLiveComponent(BoolFieldsForm::class);

        expect($tester->render()->crawler())->toContainElement('input[name="terms"]:not([checked])')
            ->and($tester->fillForm(['terms' => true])->render()->crawler())->toContainElement('input[name="terms"][checked]:not([value])');
    });

    it('marks its input invalid once the form refuses it', function (): void {
        $tester = $this->createLiveComponent(BoolFieldsForm::class);

        expect($tester->render()->crawler())->toContainElement('input[name="terms"][aria-invalid="false"]')
            ->and($tester->call('save')->assertHasFormErrors(['terms'])->render()->crawler())->toContainElement('input[name="terms"][aria-invalid="true"]');
    });

    it('drops the native required attribute while a collapsible section may conceal it, and keeps it otherwise', function (): void {
        expect($this->renderSchemaComponent(Section::make('Consent')->collapsible()->components([Checkbox::make('terms')->required()])))
            ->toContainElement('input[name="terms"]:not([required])')
            ->and($this->renderSchemaComponent(Section::make('Consent')->components([Checkbox::make('terms')->required()])))
            ->toContainElement('input[name="terms"][required]');
    });

    it('lets its extra input attributes outrank the ones it computes, joining its classes', function (): void {
        expect($this->renderSchemaComponent(Checkbox::make('terms')->disabled()->extraInputAttributes(['disabled' => false, 'class' => 'consent'])))
            ->toContainElement('input.sy-fo-checkbox.consent[name="terms"]:not([disabled])');
    });
});

describe('state', function (): void {
    it('starts at false and reads a written value as a boolean', function (): void {
        $host = $this->validatingRecordMappingHost();
        $schema = $host->mapping(Checkbox::make('unset'), Checkbox::make('blank'), Checkbox::make('ticked'));
        $host->data = ['blank' => '', 'ticked' => '1'];

        expect($this->createLiveComponent(BoolFieldsForm::class)->component()->data['terms'])->toBeFalse()
            ->and($schema->getStateSnapshot())->toBe(['blank' => false, 'ticked' => true, 'unset' => false]);
    });
});

describe('validation', function (): void {
    beforeEach(function (): void {
        $this->host = $this->validatingRecordMappingHost();
    });

    it('validates the raw state rather than the cast one, so a word that reads as ticked fails, under its own message when given one', function (): void {
        $schema = $this->host->mapping(
            Checkbox::make('terms'),
            Checkbox::make('consent')->validationMessages([Type::class => 'Tick the box or leave it.']),
        );
        $this->host->data = ['terms' => 'yes', 'consent' => 'yes'];

        expect($schema->getStateSnapshot())->toBe(['terms' => true, 'consent' => true])
            ->and(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
                expect($exception->getErrors())->toBe([
                    'data.terms' => ['This value should be of type bool.'],
                    'data.consent' => ['Tick the box or leave it.'],
                ]);
            });
    });

    it('passes a real bool, a blank string and an unset key', function (): void {
        $schema = $this->host->mapping(Checkbox::make('ticked'), Checkbox::make('unticked'), Checkbox::make('blank'), Checkbox::make('unset'));
        $this->host->data = ['ticked' => true, 'unticked' => false, 'blank' => ''];

        expect($this->host->validateSchema($schema))->toBeNull();
    });
});

describe('required()', function (): void {
    beforeEach(function (): void {
        $this->host = $this->validatingRecordMappingHost();
    });

    it('rejects an unticked box and passes a ticked one, matching its native required attribute', function (): void {
        $schema = $this->host->mapping(
            Checkbox::make('terms')->required(),
            Checkbox::make('consent')->required()->validationMessages([NotBlank::class => 'Tick the box.']),
        );
        $this->host->data = ['terms' => false, 'consent' => false];

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe([
                'data.terms' => ['This value should not be blank.'],
                'data.consent' => ['Tick the box.'],
            ]);
        });

        $this->host->data = ['terms' => true, 'consent' => true];

        expect($this->host->validateSchema($schema))->toBeNull()
            ->and($this->renderSchemaComponent(Checkbox::make('terms')->required()))->toContainElement('input[name="terms"][required]')
            ->and($this->renderSchemaComponent(Checkbox::make('terms')))->toContainElement('input[name="terms"]:not([required])');
    });

    it('is not required by a non-nullable bool target, where a text field over a non-nullable target is', function (): void {
        $schema = $this->host->mapping(Checkbox::make('stack'), TextInput::make('weight'))->record(ParcelLabel::class);
        $this->host->data = ['stack' => false];

        $render = $this->renderSchemaComponent(Section::make()->record(ParcelLabel::class)->components([Checkbox::make('stack'), TextInput::make('weight')]));

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data.weight' => ['This value should not be blank.']]);
        })
            ->and($render)->toContainElement('label[for="schema.weight"] ' . Selectors::REQUIRED_MARK)
            ->and($render)->toContainElement('label[for="schema.stack"]:not(:has(' . Selectors::REQUIRED_MARK . '))')
            ->and($render)->toContainElement('input[name="stack"]:not([required])');
    });
});
