<?php

declare(strict_types=1);

use Sylament\Forms\Components\ToggleButtons;
use Sylament\Schemas\Exceptions\ValidationException;
use Sylament\Support\Enums\GridDirection;
use Sylament\Tests\Fixtures\Forms\BoolFieldsForm;
use Sylament\Tests\Fixtures\Forms\Selectors;
use Sylament\Tests\Fixtures\Schemas\Selectors as SchemasSelectors;
use Sylament\Tests\Fixtures\Schemas\StringBackedLetter;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Validator\Constraints\Length;
use Twig\Markup;

dataset('variants', [
    'the default tree' => [false],
    'the grouped tree' => [true],
]);

describe('rendering', function (): void {
    it('renders one input wired by its id to the button label right after it, per option', function (bool $grouped): void {
        $render = $this->renderSchemaComponent(ToggleButtons::make('level')->options(['low' => 'Low', 'high' => 'High'])->grouped($grouped));

        expect($render->filter(Selectors::TOGGLE_BUTTONS_OPTION)->each(static fn (Crawler $label): string => $label->attr('for') . ': ' . $label->text()))
            ->toBe(['schema.level-low: Low', 'schema.level-high: High'])
            ->and($render)->toContainElement('input#schema\.level-low[value="low"]' . SchemasSelectors::boundTo('data.level') . ' + label[for="schema.level-low"]')
            ->toContainElement('input#schema\.level-high[value="high"]' . SchemasSelectors::boundTo('data.level') . ' + label[for="schema.level-high"]');
    })->with('variants');

    it('renders checkboxes with no name in a group when multiple, and radios sharing its id as their name in a radiogroup otherwise', function (bool $grouped): void {
        $single = $this->renderSchemaComponent(ToggleButtons::make('level')->options(['low' => 'Low', 'high' => 'High'])->grouped($grouped));
        $multiple = $this->renderSchemaComponent(ToggleButtons::make('level')->options(['low' => 'Low', 'high' => 'High'])->multiple()->grouped($grouped));

        expect($single->filter('[role="radiogroup"][aria-labelledby="schema.level-label"] input[type="radio"][name="schema.level"]'))->toHaveCount(2)
            ->and($multiple->filter('[role="group"][aria-labelledby="schema.level-label"] input[type="checkbox"]:not([name])'))->toHaveCount(2);
    })->with('variants');

    it('checks the options the state holds, never another key that reads as the same number in either tree, and none of a boolean field nobody has answered', function (): void {
        $tester = $this->createLiveComponent(BoolFieldsForm::class);

        expect($tester->render()->crawler())
            ->toContainElement('input[value="1"][data-model$="data.confirmed"][checked]')
            ->toContainElement('input[value="0"][data-model$="data.confirmed"]:not([checked])');

        $render = $tester->fillForm(['channels' => ['sms'], 'size' => 'l', 'confirmed' => '0', 'codes' => ['1'], 'groupedCode' => '1'])->render()->crawler();

        expect($render)
            ->toContainElement('input[value="sms"][data-model$="data.channels"][checked]')
            ->toContainElement('input[value="email"][data-model$="data.channels"]:not([checked])')
            ->toContainElement('input[value="l"][data-model$="data.size"][checked]')
            ->toContainElement('input[value="s"][data-model$="data.size"]:not([checked])')
            ->toContainElement('input[value="0"][data-model$="data.confirmed"][checked]')
            ->toContainElement('input[value="1"][data-model$="data.confirmed"]:not([checked])')
            ->toContainElement('input[value="1"][data-model$="data.codes"][checked]')
            ->toContainElement('input[value="01"][data-model$="data.codes"]:not([checked])')
            ->toContainElement('input[value="1"][data-model$="data.groupedCode"][checked]')
            ->toContainElement('input[value="01"][data-model$="data.groupedCode"]:not([checked])')
            ->and($tester->fillForm(['confirmed' => null])->render()->crawler()->filter('input[data-model$="data.confirmed"]:not([checked])'))->toHaveCount(2);
    });

    it('marks every option input invalid once the field has an error, and the group of a single, required field, in both trees', function (): void {
        $tester = $this->createLiveComponent(BoolFieldsForm::class);
        $before = $tester->render()->crawler();

        expect($before->filter('input[data-model$="data.channels"][aria-invalid="false"], input[data-model$="data.size"][aria-invalid="false"]'))->toHaveCount(4)
            ->and($before->filter('[aria-labelledby$="-label"][aria-invalid]'))->toHaveCount(0);

        $render = $tester->fillForm(['confirmed' => 'x'])->call('save')->assertHasFormErrors(['channels', 'size', 'confirmed'])->render()->crawler();

        expect($render->filter('input[data-model$="data.channels"][aria-invalid="true"]'))->toHaveCount(2)
            ->and($render->filter('input[data-model$="data.size"][aria-invalid="true"]'))->toHaveCount(2)
            ->and($render->filter('input[data-model$="data.confirmed"][aria-invalid="true"]'))->toHaveCount(2)
            ->and($render->filter('[role="radiogroup"][aria-labelledby="form.confirmed-label"]:not([aria-invalid])'))->toHaveCount(1)
            ->and($render->filter('[role="radiogroup"][aria-invalid]')->each(static fn (Crawler $group): string => $group->attr('aria-labelledby') . '=' . $group->attr('aria-invalid')))
            ->toBe(['form.size-label=true', 'form.level-label=true'])
            ->and($render->filter('[role="group"][aria-invalid]'))->toHaveCount(0);
    });

    it("marks a refused option disabled on its input and its label's class, not by a label attribute, and every option of a disabled field", function (bool $grouped): void {
        $render = $this->renderSchemaComponent(ToggleButtons::make('level')->options(['low' => 'Low', 'high' => 'High'])->disableOptionWhen(fn (string $value): bool => $value === 'high')->grouped($grouped));

        expect($render)
            ->toContainElement('input[value="high"][disabled] + label' . SupportSelectors::DISABLED . '[aria-disabled="true"]:not([disabled])', 'High')
            ->toContainElement('input[value="low"]:not([disabled]) + label:not(' . SupportSelectors::DISABLED . '):not([aria-disabled])', 'Low')
            ->and($this->renderSchemaComponent(ToggleButtons::make('level')->options(['low' => 'Low', 'high' => 'High'])->disabled()->grouped($grouped))->filter('input[disabled] + label' . SupportSelectors::DISABLED))
            ->toHaveCount(2);
    })->with('variants');

    it('puts its extra input attributes on every option input of its grouped template too', function (): void {
        expect($this->renderSchemaComponent(ToggleButtons::make('level')->options(['low' => 'Low', 'high' => 'High'])->grouped()->extraInputAttributes(['data-probe' => 'extra']))
            ->filter('input.sy-fo-toggle-buttons-input[data-probe="extra"]'))->toHaveCount(2);
    });

    it('puts its extra attributes on the field wrapper, not on its options', function (bool $grouped): void {
        expect($this->renderSchemaComponent(ToggleButtons::make('level')->options(['low' => 'Low', 'high' => 'High'])->extraAttributes(['data-panel' => 'levels'])->grouped($grouped)))
            ->toContainElement(Selectors::FIELD_WRAPPER . '[data-panel="levels"] .sy-fo-toggle-buttons:not([data-panel])');
    })->with('variants');

    it('shows no label text and names each button by its label stripped of tags, keeping its icon', function (bool $grouped): void {
        $render = $this->renderSchemaComponent(ToggleButtons::make('level')->options(['bold' => new Markup('<b>Bold</b> ', 'UTF-8'), 'plain' => 'Plain'])->icons(['bold' => 'support:star'])->hiddenButtonLabels()->grouped($grouped));

        expect($render->filter(Selectors::TOGGLE_BUTTONS_OPTION)->each(static fn (Crawler $label): string => $label->attr('aria-label') . ': ' . $label->text()))
            ->toBe(['Bold: Star', 'Plain: '])
            ->and($this->renderSchemaComponent(ToggleButtons::make('level')->options(['plain' => 'Plain'])->grouped($grouped)))
            ->toContainElement(Selectors::TOGGLE_BUTTONS_OPTION . ':not([aria-label])', 'Plain');
    })->with('variants');
});

describe('layout', function (): void {
    it('lays its options out in a column-wise grid by default, in the columns and direction it is given, and in a wrapping row when inline', function (): void {
        expect($this->renderSchemaComponent(ToggleButtons::make('level')->options(['low' => 'Low'])))
            ->toContainElement('.sy-fo-toggle-buttons' . SupportSelectors::GRID_DIRECTION_COLUMN . SupportSelectors::columnWiseColumns('default', 1) . ':not(' . SupportSelectors::INLINE . ')')
            ->and($this->renderSchemaComponent(ToggleButtons::make('level')->options(['low' => 'Low'])->columns(3)))
            ->toContainElement('.sy-fo-toggle-buttons' . SupportSelectors::GRID_DIRECTION_COLUMN . SupportSelectors::columnWiseColumns('lg', 3))
            ->and($this->renderSchemaComponent(ToggleButtons::make('level')->options(['low' => 'Low'])->columns(3)->gridDirection(GridDirection::Row)))
            ->toContainElement('.sy-fo-toggle-buttons:not(' . SupportSelectors::GRID_DIRECTION_COLUMN . ')' . SupportSelectors::columns('lg', 3))
            ->and($this->renderSchemaComponent(ToggleButtons::make('level')->options(['low' => 'Low'])->inline()))
            ->toContainElement('.sy-fo-toggle-buttons' . SupportSelectors::INLINE . ':not(' . SupportSelectors::GRID . '):not([style])');
    });
});

describe('grouped', function (): void {
    it('renders the joined group with its button labels as its direct children, choosing the tree when it renders', function (): void {
        $compact = false;
        $field = ToggleButtons::make('level')->options(['low' => 'Low', 'high' => 'High'])->grouped(function () use (&$compact): bool {
            return $compact;
        });
        $compact = true;

        $render = $this->renderSchemaComponent($field);

        expect($render->filter('.sy-fo-toggle-buttons' . SupportSelectors::BUTTON_GROUP . ' > label.sy-btn.sy-btn-group-btn'))->toHaveCount(2)
            ->and($render->filter('.sy-fo-toggle-buttons-btn-ctn'))->toHaveCount(0)
            ->and($this->renderSchemaComponent(ToggleButtons::make('level')->options(['low' => 'Low', 'high' => 'High'])))
            ->toContainElement('.sy-fo-toggle-buttons-btn-ctn ' . Selectors::TOGGLE_BUTTONS_OPTION, 'Low');
    });

    it('picks its template by an explicit setting, then a default setting, then its grouping', function (): void {
        expect($this->renderSchemaComponent(ToggleButtons::make('level')->options(['low' => 'Low'])->grouped()->defaultTemplate('@Fixtures/forms/ToggleButtonsMarker.html.twig')))
            ->toContainElement('.toggle-buttons-marker', 'Marked level')
            ->not->toContainElement(SupportSelectors::BUTTON_GROUP)
            ->and($this->renderSchemaComponent(ToggleButtons::make('level')->options(['low' => 'Low'])->grouped()->defaultTemplate('@Fixtures/forms/ToggleButtonsMarker.html.twig')->template(ToggleButtons::TEMPLATE)))
            ->toContainElement('.sy-fo-toggle-buttons-btn-ctn ' . Selectors::TOGGLE_BUTTONS_OPTION, 'Low')
            ->not->toContainElement('.toggle-buttons-marker');
    });

    it('keeps the inline row to the ungrouped tree', function (): void {
        $default = $this->renderSchemaComponent(ToggleButtons::make('level')->options(['low' => 'Low', 'high' => 'High'])->inline());
        $grouped = $this->renderSchemaComponent(ToggleButtons::make('level')->options(['low' => 'Low', 'high' => 'High'])->inline()->grouped());

        expect($default)->toContainElement('.sy-fo-toggle-buttons' . SupportSelectors::INLINE)
            ->and($grouped)->toContainElement('.sy-fo-toggle-buttons' . SupportSelectors::BUTTON_GROUP . ':not(' . SupportSelectors::INLINE . ')');
    });

    it('autofocuses only the first input of a single choice, as Filament does', function (): void {
        $options = ['low' => 'Low', 'high' => 'High'];
        $single = $this->renderSchemaComponent(ToggleButtons::make('level')->options($options)->grouped()->autofocus());

        expect($single->filter('[autofocus]')->each(static fn (Crawler $node): string => $node->nodeName() . ':' . $node->attr('value')))->toBe(['input:low'])
            ->and($this->renderSchemaComponent(ToggleButtons::make('level')->options($options)->grouped()->autofocus()->multiple()))->not->toContainElement('[autofocus]')
            ->and($this->renderSchemaComponent(ToggleButtons::make('level')->options($options)->grouped()))->not->toContainElement('[autofocus]');
    });
});

describe('multiple()', function (): void {
    it('rests at an empty list and keeps every input on one model', function (): void {
        $tester = $this->createLiveComponent(BoolFieldsForm::class);

        expect($tester->component()->data['channels'])->toBe([])
            ->and($tester->render()->crawler()->filter('input[value="email"], input[value="sms"]')->each(static fn (Crawler $input): ?string => $input->attr('data-model')))
            ->toBe(['norender|data.channels', 'norender|data.channels']);
    });
});

describe('state', function (): void {
    it('reads a blank single answer as null, a blank multiple one as an empty list, and an option key as the key', function (): void {
        $host = $this->validatingRecordMappingHost();
        $schema = $host->mapping(
            ToggleButtons::make('single')->options(['low' => 'Low']),
            ToggleButtons::make('many')->options(['low' => 'Low'])->multiple(),
            ToggleButtons::make('numbered')->options([1 => 'One', 2 => 'Two']),
        );
        $host->data = ['single' => '', 'many' => null, 'numbered' => '1'];

        expect($schema->getStateSnapshot())->toBe(['single' => null, 'many' => [], 'numbered' => 1]);
    });
});

describe('boolean()', function (): void {
    it('offers Yes and No with their colours and icons, or the labels it is given', function (): void {
        expect($this->renderSchemaComponent(ToggleButtons::make('confirmed')->boolean()))
            ->toContainElement('input[value="1"] + label' . SupportSelectors::color('success'), 'check-20-solid Yes')
            ->toContainElement('input[value="0"] + label' . SupportSelectors::color('danger'), 'x-mark-20-solid No')
            ->and($this->renderSchemaComponent(ToggleButtons::make('confirmed')->boolean('On', 'Off'))->filter(Selectors::TOGGLE_BUTTONS_OPTION)->each(static fn (Crawler $label): string => $label->text()))
            ->toBe(['check-20-solid On', 'x-mark-20-solid Off']);
    });

    it('stores its answer as 1 or 0 and reads it as a boolean', function (): void {
        $host = $this->validatingRecordMappingHost();
        $schema = $host->mapping(ToggleButtons::make('yes')->boolean()->default(true), ToggleButtons::make('no')->boolean()->default(false))->fill();

        expect($host->data)->toBe(['yes' => 1, 'no' => 0])
            ->and($schema->getStateSnapshot())->toBe(['yes' => true, 'no' => false]);
    });

    it('starts at 1 or 0 from a boolean default, and at any other default as given', function (): void {
        $host = $this->validatingRecordMappingHost();
        $host->mapping(
            ToggleButtons::make('yes')->options([1 => 'Yes', 0 => 'No'])->default(true),
            ToggleButtons::make('no')->options([1 => 'Yes', 0 => 'No'])->default(false),
            ToggleButtons::make('level')->options(['low' => 'Low'])->default('low'),
        )->fill();

        expect($host->data)->toBe(['yes' => '1', 'no' => '0', 'level' => 'low']);
    });
});

describe('enum()', function (): void {
    it("reads a stored value as its enum case, and a multiple field's as a list of cases", function (): void {
        $host = $this->validatingRecordMappingHost();
        $schema = $host->mapping(
            ToggleButtons::make('letter')->enum(StringBackedLetter::class),
            ToggleButtons::make('letters')->enum(StringBackedLetter::class)->multiple(),
        );
        $host->data = ['letter' => 'a', 'letters' => ['b', 'c']];

        expect($schema->getStateSnapshot())->toBe(['letter' => StringBackedLetter::A, 'letters' => [StringBackedLetter::B, StringBackedLetter::C]]);
    });
});

describe('validation', function (): void {
    beforeEach(function (): void {
        $this->host = $this->validatingRecordMappingHost();
    });

    it('refuses a value outside its enabled options, in both multiplicities', function (Closure $field, mixed $raw, string $message): void {
        $schema = $this->host->mapping($field());
        $this->host->data = ['pick' => $raw];

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception) use ($message): void {
            expect($exception->getErrors())->toBe(['data.pick' => [$message]]);
        });
    })->with([
        'a stray value' => [fn (): ToggleButtons => ToggleButtons::make('pick')->options(['low' => 'Low']), 'tampered', 'The value you selected is not a valid choice.'],
        'a list holding a stray value' => [fn (): ToggleButtons => ToggleButtons::make('pick')->options(['low' => 'Low'])->multiple(), ['low', 'tampered'], 'One or more of the given values is invalid.'],
        'an option disabled though listed' => [fn (): ToggleButtons => ToggleButtons::make('pick')->options(['low' => 'Low', 'high' => 'High'])->disableOptionWhen(fn (string $value): bool => $value === 'high'), 'high', 'The value you selected is not a valid choice.'],
        'a value no enum case matches' => [fn (): ToggleButtons => ToggleButtons::make('pick')->enum(StringBackedLetter::class), 'z', 'The value you selected is not a valid choice.'],
        'a list no enum case matches' => [fn (): ToggleButtons => ToggleButtons::make('pick')->enum(StringBackedLetter::class)->multiple(), ['z'], 'One or more of the given values is invalid.'],
    ]);

    it("refuses a multiple field's raw state that is not a list", function (): void {
        $schema = $this->host->mapping(ToggleButtons::make('pick')->options(['low' => 'Low'])->multiple());
        $this->host->data = ['pick' => 'low'];

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data.pick' => ['This value should be of type array.']]);
        });
    });

    it('checks a blank answer against no option, so only required() refuses it', function (): void {
        $schema = $this->host->mapping(ToggleButtons::make('pick')->options(['low' => 'Low']), ToggleButtons::make('needed')->options(['low' => 'Low'])->required());
        $this->host->data = ['pick' => '', 'needed' => ''];

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data.needed' => ['This value should not be blank.']]);
        });
    });

    it('checks a user in() list instead of its options, on a field with no options too', function (): void {
        $schema = $this->host->mapping(
            ToggleButtons::make('listed')->options(['low' => 'Low'])->in(['high']),
            ToggleButtons::make('unlisted')->options(['low' => 'Low'])->in(['high']),
            ToggleButtons::make('free')->in(['high']),
        );
        $this->host->data = ['listed' => 'low', 'unlisted' => 'high', 'free' => 'high'];

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data.listed' => ['The value you selected is not a valid choice.']]);
        });
    });

    it('refuses to validate an answer without options, naming its state path, while it passes a blank one', function (): void {
        $schema = $this->host->mapping(ToggleButtons::make('pick'));
        $this->host->data = ['pick' => ''];

        expect($this->host->validateSchema($schema))->toBeNull();

        $this->host->data = ['pick' => 'low'];

        expect(fn () => $this->host->validateSchema($schema))->toThrow(LogicException::class, 'Sylament failed to validate the [data.pick] toggle buttons because it has no options() configuration.');
    });

    it('applies nested constraints to each item of a multiple field', function (): void {
        $schema = $this->host->mapping(ToggleButtons::make('pick')->options(['a' => 'A', 'bb' => 'BB'])->multiple()->nestedRecursiveConstraints([new Length(max: 1)]));
        $this->host->data = ['pick' => ['a', 'bb']];

        expect(fn () => $this->host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe(['data.pick' => ['This value is too long. It should have 1 character or less.']]);
        });

        $this->host->data = ['pick' => ['a']];

        expect($this->host->validateSchema($schema))->toBeNull();
    });
});

describe('required()', function (): void {
    it('rejects no answer in either multiplicity and counts a boolean No as an answer', function (): void {
        $host = $this->validatingRecordMappingHost();
        $schema = $host->mapping(
            ToggleButtons::make('single')->options(['low' => 'Low'])->required(),
            ToggleButtons::make('many')->options(['low' => 'Low'])->multiple()->required(),
            ToggleButtons::make('no')->boolean()->required(),
            ToggleButtons::make('submittedNo')->boolean()->required(),
        );
        $host->data = ['single' => null, 'many' => [], 'no' => 0, 'submittedNo' => '0'];

        expect(fn () => $host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe([
                'data.single' => ['This value should not be blank.'],
                'data.many' => ['This value should not be blank.'],
            ]);
        });
    });
});
