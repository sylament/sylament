<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Components\Text;
use Sylament\Tests\Fixtures\Forms\DefaultNamedField;
use Sylament\Tests\Fixtures\Forms\MessageListForm;
use Sylament\Tests\Fixtures\Forms\Selectors;
use Sylament\Tests\Fixtures\Schemas\Selectors as SchemasSelectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

describe('make()', function (): void {
    it('names the field and binds it to its name before configure() runs, so a configureUsing() closure reads both', function (): void {
        TextInput::configureUsing(static fn (TextInput $field): TextInput => $field->label("{$field->getName()} at {$field->getStatePath(isAbsolute: false)}"));

        expect($this->renderSchemaComponent(TextInput::make('email')))
            ->toContainElement('label', 'email at email')
            ->toContainElement('input[name="email"]' . SchemasSelectors::boundTo('data.email'));
    });

    it('takes the name its class declares when given none', function (): void {
        expect($this->renderSchemaComponent(DefaultNamedField::make()))
            ->toContainElement('input[name="nickname"]' . SchemasSelectors::boundTo('data.nickname'));
    });

    it('refuses a blank name, naming its class', function (?string $name): void {
        expect(fn (): TextInput => TextInput::make($name))
            ->toThrow(InvalidArgumentException::class, 'Field of class [Sylament\Forms\Components\TextInput] must have a unique name, passed to the [make()] method.');
    })->with([
        'no name' => [null],
        'an empty name' => [''],
    ]);
});

describe('label', function (): void {
    it('labels itself by the last segment of its name, humanized, unless given a label', function (): void {
        expect($this->renderSchemaComponent(TextInput::make('first_name')))->toContainElement('label', 'First name')
            ->and($this->renderSchemaComponent(TextInput::make('address.zip_code')))->toContainElement('label', 'Zip code')
            ->and($this->renderSchemaComponent(TextInput::make('first_name')->label('Given name')))->toContainElement('label', 'Given name');
    });
});

describe('wrapper slots', function (): void {
    it('renders the label slots around its label, the after-label one at the end, an action as a small link', function (): void {
        $render = $this->renderSchemaComponent(TextInput::make('name')
            ->aboveLabel('Above label')
            ->belowLabel(Text::make('Below label'))
            ->beforeLabel('Before label')
            ->afterLabel(Action::make('explain')));

        expect($render->filter(Selectors::LABEL_COLUMN)->text())->toBe('Above label Before label Name Explain Below label')
            ->and($render->filter(Selectors::LABEL_ROW)->text())->toBe('Before label Name Explain')
            ->and($render)
            ->toContainElement(SchemasSelectors::SCHEMA . SchemasSelectors::INLINE . SupportSelectors::align('end'), 'Explain')
            ->toContainButtons(SupportSelectors::SMALL_LINK, ['Explain']);
    });

    it('renders the content slots around its control, the after-content one at the end', function (): void {
        $render = $this->renderSchemaComponent(TextInput::make('name')
            ->aboveContent('Above content')
            ->belowContent('Below content')
            ->beforeContent('Before content')
            ->afterContent('After content'));

        expect($render->filter(Selectors::CONTENT_COLUMN)->text())->toBe('Above content Before content After content Below content')
            ->and($render)
            ->toContainElement('.sy-fo-field-content-ctn input[name="name"]')
            ->toContainElement(SchemasSelectors::SCHEMA . SchemasSelectors::INLINE . SupportSelectors::align('end'), 'After content');
    });

    it('renders the error-message slots around its error list, only while it has errors', function (): void {
        $tester = $this->createLiveComponent(MessageListForm::class);

        expect($tester->render()->crawler())->toContainElement(Selectors::CONTENT_COLUMN . ' input[name="code"]')
            ->and($tester->render()->crawler()->filter(Selectors::CONTENT_COLUMN)->first()->text())->toBe('')
            ->and($tester->fillForm(['code' => ''])->call('save')->render()->crawler()->filter(Selectors::CONTENT_COLUMN)->first()->text())
            ->toBe('Above the errors This value should not be blank. Below the errors');
    });
});

describe('errors', function (): void {
    it('lists every message a value fails with, in order, and marks its control invalid', function (): void {
        $tester = $this->createLiveComponent(MessageListForm::class);

        expect($tester->render()->crawler())
            ->toContainElement('input[name="code"][aria-invalid="false"]')
            ->not->toContainElement(Selectors::ERROR_MESSAGE);

        $render = $tester->fillForm(['code' => 'ab'])->call('save')->render()->crawler();

        expect($render->filter(Selectors::ERROR_MESSAGE)->each(static fn ($message): string => $message->text()))
            ->toBe(['This value is too short. It should have 3 characters or more.', 'This value should be of type numeric.'])
            ->and($render)
            ->toContainElement(SupportSelectors::INPUT_WRAPPER . SupportSelectors::INVALID . ' input[name="code"][aria-invalid="true"]')
            ->toContainElement(SupportSelectors::INPUT_WRAPPER . ':not(' . SupportSelectors::INVALID . ') input[name="note"][aria-invalid="false"]');
    });
});

describe('required marker', function (): void {
    it('marks a required field and its control as required, and an unrequired one as neither', function (): void {
        expect($this->renderSchemaComponent(TextInput::make('name')->required()))
            ->toContainElement('label ' . Selectors::REQUIRED_MARK, '*')
            ->toContainElement('input[name="name"][required]')
            ->and($this->renderSchemaComponent(TextInput::make('name')))
            ->toContainElement('label', 'Name')
            ->toContainElement('input[name="name"]:not([required])')
            ->not->toContainElement(Selectors::REQUIRED_MARK);
    });

    it('marks a field told to look required without requiring it, and unmarks a required one told not to', function (): void {
        expect($this->renderSchemaComponent(TextInput::make('name')->required()->markAsRequired(false)))
            ->toContainElement('label', 'Name')
            ->toContainElement('input[name="name"]:not([required])')
            ->not->toContainElement(Selectors::REQUIRED_MARK)
            ->and($this->createLiveComponent(MessageListForm::class)->render()->crawler())
            ->toContainElement('label[for="form.note"] ' . Selectors::REQUIRED_MARK, '*')
            ->toContainElement('input[name="note"][required]');

        $this->createLiveComponent(MessageListForm::class)
            ->fillForm(['code' => '123', 'note' => ''])
            ->call('save')
            ->assertHasNoFormErrors();
    });
});
