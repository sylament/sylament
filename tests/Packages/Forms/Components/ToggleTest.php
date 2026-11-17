<?php

declare(strict_types=1);

use Sylament\Forms\Components\TextInput;
use Sylament\Forms\Components\Toggle;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Exceptions\ValidationException;
use Sylament\Tests\Fixtures\Forms\BoolFieldsForm;
use Sylament\Tests\Fixtures\Forms\Selectors;
use Sylament\Tests\Fixtures\Schemas\Record\CardAddress;
use Sylament\Tests\Fixtures\Schemas\Record\CardAuthor;
use Sylament\Tests\Fixtures\Schemas\Record\CatalogueCard;
use Sylament\Tests\Fixtures\Schemas\Record\ParcelLabel;
use Sylament\Tests\Fixtures\Schemas\Selectors as SchemasSelectors;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Type;

describe('rendering', function (): void {
    it('renders a switch input inside a span beside the label when inline, and inside a label for it in the content column otherwise, wiring no controller', function (): void {
        $inline = $this->renderSchemaComponent(Toggle::make('notify'));
        $stacked = $this->renderSchemaComponent(Toggle::make('notify')->inline(false));

        expect($inline->filter('input[role="switch"]'))->toHaveCount(1)
            ->and($inline)->toContainElement(Selectors::LABEL_ROW . ' label[for="schema.notify"] span' . Selectors::TOGGLE . ' input[type="checkbox"][role="switch"]')
            ->and($stacked->filter('input[role="switch"]'))->toHaveCount(1)
            ->and($stacked)->toContainElement(Selectors::CONTENT_COLUMN . ' label' . Selectors::TOGGLE . '[for="schema.notify"] input[type="checkbox"][role="switch"]')
            ->and($stacked)->toContainElement(Selectors::TOGGLE . ' ' . Selectors::TOGGLE_TRACK)
            ->and($stacked->filter(Selectors::FIELD_WRAPPER . ' [data-controller]'))->toHaveCount(0);
    });

    it('binds the switch to the state by its id and name, with autofocus and disabled when set', function (): void {
        expect($this->renderSchemaComponent(Toggle::make('notify')->autofocus()))
            ->toContainElement('input#schema\.notify[role="switch"][name="notify"]' . SchemasSelectors::boundTo('data.notify') . '[autofocus]:not([value]):not([disabled])')
            ->and($this->renderSchemaComponent(Toggle::make('notify')->disabled()))
            ->toContainElement('input[role="switch"][disabled]:not([autofocus])');
    });

    it('renders checked from the state', function (): void {
        $tester = $this->createLiveComponent(BoolFieldsForm::class);

        expect($tester->render()->crawler())->toContainElement('input[name="notify"]:not([checked])')
            ->and($tester->fillForm(['notify' => true])->render()->crawler())->toContainElement('input[name="notify"][checked]');
    });

    it('marks its input invalid once the form refuses it', function (): void {
        $tester = $this->createLiveComponent(BoolFieldsForm::class);

        expect($tester->render()->crawler())->toContainElement('input[name="notify"][aria-invalid="false"]')
            ->and($tester->call('save')->assertHasFormErrors(['notify'])->render()->crawler())->toContainElement('input[name="notify"][aria-invalid="true"]');
    });

    it('puts its extra input attributes on the input, not on the control root, where they outrank the ones it computes', function (): void {
        expect($this->renderSchemaComponent(Toggle::make('notify')->disabled()->extraInputAttributes(['disabled' => false, 'data-channel' => 'email'])))
            ->toContainElement('input[role="switch"][data-channel="email"]:not([disabled])')
            ->toContainElement(Selectors::TOGGLE . ':not([data-channel])');
    });
});

describe('state', function (): void {
    it('starts at false and reads a written value as a boolean', function (): void {
        $host = $this->validatingRecordMappingHost();
        $schema = $host->mapping(Toggle::make('blank'), Toggle::make('on'), Toggle::make('unset'));
        $host->data = ['blank' => '', 'on' => '1'];

        expect($this->createLiveComponent(BoolFieldsForm::class)->component()->data['notify'])->toBeFalse()
            ->and($schema->getStateSnapshot())->toBe(['blank' => false, 'on' => true, 'unset' => false]);
    });

    it('reads a switch resting at false as blank, so a nullable branch holding only it is discarded', function (): void {
        $host = $this->validatingRecordMappingHost();
        $card = new CatalogueCard();
        $card->author = new CardAuthor();
        $card->author->address = new CardAddress();

        $schema = $host->mapping(Section::make()->statePath('author')->nullWhenBlank()->components([
            TextInput::make('name'),
            Toggle::make('email'),
        ]))->record($card);
        $host->data = ['author' => ['name' => null, 'email' => false]];

        expect($schema->getData()->author)->toBeNull();

        $host->data = ['author' => ['name' => null, 'email' => true]];

        expect($schema->getData()->author)->toBeInstanceOf(CardAuthor::class);
    });
});

describe('validation', function (): void {
    beforeEach(function (): void {
        $this->host = $this->validatingRecordMappingHost();
    });

    it('refuses a raw state that is not a real bool, under its own message when given one', function (mixed $raw): void {
        $schema = $this->host->mapping(
            Toggle::make('notify'),
            Toggle::make('muted')->validationMessages([Type::class => 'Switch it on or off.']),
        );
        $this->host->data = ['notify' => $raw, 'muted' => $raw];

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe([
                'data.notify' => ['This value should be of type bool.'],
                'data.muted' => ['Switch it on or off.'],
            ]);
        });
    })->with([
        'a number Laravel would accept' => [1],
        'a word' => ['yes'],
    ]);

    it('passes a real bool and an unset key', function (): void {
        $schema = $this->host->mapping(Toggle::make('on'), Toggle::make('off'), Toggle::make('unset'));
        $this->host->data = ['on' => true, 'off' => false];

        expect($this->host->validateSchema($schema))->toBeNull();
    });
});

describe('required()', function (): void {
    beforeEach(function (): void {
        $this->host = $this->validatingRecordMappingHost();
    });

    it('accepts an off switch and a blank string and refuses only a missing state, with no native required attribute but its asterisk', function (): void {
        $schema = $this->host->mapping(
            Toggle::make('notify')->required(),
            Toggle::make('muted')->required()->validationMessages([NotNull::class => 'Say on or off.']),
        );
        $this->host->data = ['notify' => false, 'muted' => ''];

        expect($this->host->validateSchema($schema))->toBeNull();

        $this->host->data = ['notify' => null, 'muted' => null];

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe([
                'data.notify' => ['This value should not be null.'],
                'data.muted' => ['Say on or off.'],
            ]);
        })
            ->and($this->renderSchemaComponent(Toggle::make('notify')->required()))
            ->toContainElement('input[role="switch"]:not([required])')
            ->toContainElement('label[for="schema.notify"] ' . Selectors::REQUIRED_MARK);
    });

    it('asks for an on switch through accepted()', function (): void {
        $schema = $this->host->mapping(Toggle::make('notify')->required()->accepted());
        $this->host->data = ['notify' => false];

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data.notify' => ['This value should be true.']]);
        });
    });

    it('is not marked required by a non-nullable bool target, where a text field over a non-nullable target is', function (): void {
        $render = $this->renderSchemaComponent(Section::make()->record(ParcelLabel::class)->components([Toggle::make('fragile'), TextInput::make('weight')]));

        expect($render)->toContainElement('label[for="schema.weight"] ' . Selectors::REQUIRED_MARK)
            ->toContainElement('label[for="schema.fragile"]:not(:has(' . Selectors::REQUIRED_MARK . '))');
    });
});
