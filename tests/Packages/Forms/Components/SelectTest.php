<?php

declare(strict_types=1);

use Doctrine\ORM\QueryBuilder;
use Sylament\Actions\Action;
use Sylament\Actions\Testing\Testable;
use Sylament\Actions\Testing\TestAction;
use Sylament\Forms\Components\Select;
use Sylament\Forms\Components\TextInput;
use Sylament\Forms\Validation\RequiredValue;
use Sylament\Schemas\Components\Group;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Exceptions\ValidationException;
use Sylament\Tests\Fixtures\Entity\BenchPot;
use Sylament\Tests\Fixtures\Entity\Berth;
use Sylament\Tests\Fixtures\Entity\BerthCleat;
use Sylament\Tests\Fixtures\Entity\BerthMooring;
use Sylament\Tests\Fixtures\Entity\Garden;
use Sylament\Tests\Fixtures\Entity\NurseryBench;
use Sylament\Tests\Fixtures\Entity\Plant;
use Sylament\Tests\Fixtures\Entity\Quay;
use Sylament\Tests\Fixtures\Entity\QuayMooring;
use Sylament\Tests\Fixtures\Entity\Seedling;
use Sylament\Tests\Fixtures\Forms\SelectBerthForm;
use Sylament\Tests\Fixtures\Forms\SelectForm;
use Sylament\Tests\Fixtures\Forms\SelectOptionActionForm;
use Sylament\Tests\Fixtures\Forms\Selectors;
use Sylament\Tests\Fixtures\Forms\SelectRelationshipForm;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Schemas\Record\Memo;
use Sylament\Tests\Fixtures\Schemas\RecordMappingHost;
use Sylament\Tests\Fixtures\Schemas\StringBackedLetter;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\Type;
use Twig\Error\RuntimeError;
use Twig\Markup;

describe('native render', function (): void {
    it('renders one option per entry, a group as an optgroup, the placeholder first, and the field bound by its id, name and model', function (): void {
        $select = $this->renderSchemaComponent(Select::make('plant')->options(['Ferns' => ['fern' => 'Fern', 'bracken' => 'Bracken'], 'moss' => 'Moss']))->filter(Selectors::NATIVE_SELECT);

        expect($select->filter('option')->each(static fn (Crawler $option): array => [$option->attr('value'), $option->text()]))->toBe([
            ['', 'Select an option'],
            ['fern', 'Fern'],
            ['bracken', 'Bracken'],
            ['moss', 'Moss'],
        ])
            ->and($select->filter('optgroup')->attr('label'))->toBe('Ferns')
            ->and($select->filter('optgroup option')->count())->toBe(2)
            ->and([$select->attr('id'), $select->attr('name'), $select->attr('data-model')])->toBe(['schema.plant', 'plant', 'norender|data.plant']);
    });

    it('carries the disabled and autofocus switches on the select', function (): void {
        $select = $this->renderSchemaComponent(Select::make('plant')->options(['fern' => 'Fern'])->disabled()->autofocus())->filter(Selectors::NATIVE_SELECT);

        expect($select->attr('disabled'))->not->toBeNull()
            ->and($select->attr('autofocus'))->not->toBeNull();

        $plain = $this->renderSchemaComponent(Select::make('plant')->options(['fern' => 'Fern']))->filter(Selectors::NATIVE_SELECT);

        expect($plain->count())->toBe(1)
            ->and($plain->attr('disabled'))->toBeNull()
            ->and($plain->attr('autofocus'))->toBeNull();
    });

    it('marks selected the option whose key loosely equals the state, and each member of a list', function (): void {
        $render = $this->createLiveComponent(SelectForm::class)
            ->fillForm(['single' => '1', 'multiple' => ['b', 'a']])
            ->render()
            ->crawler();

        expect($render->filter('select[name="single"] option[selected]')->each(static fn (Crawler $option): ?string => $option->attr('value')))->toBe(['1'])
            ->and($render->filter('select[name="multiple"] option[selected]')->each(static fn (Crawler $option): ?string => $option->attr('value')))->toBe(['a', 'b']);
    });

    it('marks the select invalid once the field has an error', function (): void {
        $tester = $this->createLiveComponent(SelectForm::class)->fillForm(['single' => '9']);

        expect($tester->render()->crawler()->filter('select[name="single"]')->attr('aria-invalid'))->toBe('false')
            ->and($tester->call('save')->render()->crawler()->filter('select[name="single"]')->attr('aria-invalid'))->toBe('true');
    });

    it('drops the required attribute inside a container that may conceal the select, and keeps it where nothing does', function (): void {
        $required = Select::make('plant')->options(['fern' => 'Fern'])->required();

        expect($this->renderSchemaComponent(Section::make()->collapsible()->components([$required]))->filter(Selectors::NATIVE_SELECT)->attr('required'))->toBeNull()
            ->and($this->renderSchemaComponent(Select::make('plant')->options(['fern' => 'Fern'])->required())->filter(Selectors::NATIVE_SELECT)->attr('required'))->not->toBeNull();
    });

    it('takes the left padding off for an inline prefix only while a prefix it shows sits there', function (): void {
        expect($this->renderSchemaComponent(Select::make('plant')->options(['fern' => 'Fern'])->prefixAction(Action::make('pick'), isInline: true)))
            ->toContainElement(Selectors::INLINE_PREFIX_SELECT);

        expect($this->renderSchemaComponent(Select::make('plant')->options(['fern' => 'Fern'])->prefixAction(Action::make('pick')->hidden(), isInline: true)))
            ->toContainElement(Selectors::NATIVE_SELECT)
            ->not->toContainElement(Selectors::INLINE_PREFIX_SELECT);
    });
});

describe('JS control render', function (): void {
    it('hands the binding to the controller instead of a data-model, and keeps the id on the select', function (): void {
        $render = $this->renderSchemaComponent(Select::make('plant')->options(['fern' => 'Fern'])->native(false));
        $select = $render->filter(Selectors::JS_CONTROL);

        expect([$select->attr('id'), $select->attr('data-skip-morph'), $select->attr('data-model')])->toBe(['schema.plant', 'true', null])
            ->and($this->selectControllerValue($render, 'statePath'))->toBe('data.plant')
            ->and($this->selectControllerValue($render, 'modelBinding'))->toBe('norender|data.plant');
    });

    it('hands the controller the options as label, value and disabled flag, a group as its label and options', function (): void {
        $select = Select::make('plant')
            ->options(['Ferns' => ['fern' => 'Fern'], 'moss' => new Markup('<b>Moss</b>', 'UTF-8')])
            ->disableOptionWhen(static fn (mixed $value): bool => $value === 'moss')
            ->native(false);

        expect($this->selectControllerValue($this->renderSchemaComponent($select), 'options'))->toBe([
            ['label' => 'Ferns', 'options' => [['label' => 'Fern', 'value' => 'fern', 'isDisabled' => false]]],
            ['label' => '<b>Moss</b>', 'value' => 'moss', 'isDisabled' => true],
        ]);
    });

    it('hands the controller the shape a transformOptionsForJsUsing() closure gives, and refuses a select whose closure was removed', function (): void {
        $select = Select::make('plant')->options(['fern' => 'Fern'])->native(false)->transformOptionsForJsUsing(static fn (array $options): array => array_map(static fn (string $label): array => ['text' => $label], array_values($options)));

        expect($this->selectControllerValue($this->renderSchemaComponent($select), 'options'))->toBe([['text' => 'Fern']])
            ->and(fn () => $this->renderSchemaComponent(Select::make('plant')->options(['fern' => 'Fern'])->native(false)->transformOptionsForJsUsing(null)))
            ->toThrow(RuntimeError::class, 'The [data.plant] select has no transformOptionsForJsUsing() closure, so its options cannot reach the JS control.');
    });

    it('carries an HTML label raw to the controller under allowHtml() while the hidden option holds its text', function (): void {
        $render = $this->renderSchemaComponent(Select::make('plant')->options(['fern' => new Markup('<b>Fern</b>', 'UTF-8')])->allowHtml());

        expect($this->selectControllerValue($render, 'htmlAllowed'))->toBe('true')
            ->and($this->selectControllerValue($render, 'options'))->toBe([['label' => '<b>Fern</b>', 'value' => 'fern', 'isDisabled' => false]])
            ->and($render->filter(Selectors::JS_CONTROL . ' option[value="fern"]')->html())->toBe('Fern');
    });

    it('hands the controller the dropdown placement and label wrapping, opening where there is room and wrapping by default', function (): void {
        $default = $this->renderSchemaComponent(Select::make('plant')->options(['fern' => 'Fern'])->native(false));
        $pinned = $this->renderSchemaComponent(Select::make('plant')->options(['fern' => 'Fern'])->native(false)->position('top')->wrapOptionLabels(false));

        expect([$this->selectControllerValue($default, 'position'), $this->selectControllerValue($default, 'labelsWrap')])->toBe([null, 'true'])
            ->and([$this->selectControllerValue($pinned, 'position'), $this->selectControllerValue($pinned, 'labelsWrap')])->toBe(['top', 'false']);
    });

    it('hands the controller the loading message, Loading... unless the field sets one', function (): void {
        expect($this->selectControllerValue($this->renderSchemaComponent(Select::make('plant')->options(['fern' => 'Fern'])->native(false)), 'loadingMessage'))->toBe('Loading...')
            ->and($this->selectControllerValue($this->renderSchemaComponent(Select::make('plant')->options(['fern' => 'Fern'])->native(false)->loadingMessage('Fetching plants...')), 'loadingMessage'))->toBe('Fetching plants...');
    });

    it('renders a required JS control aria-required rather than required, and aria-invalid once it has an error', function (): void {
        $select = $this->createLiveComponent(SelectForm::class)->call('save')->render()->crawler()->filter('select[name="chosen"]');

        expect([$select->attr('id'), $select->attr('aria-required'), $select->attr('aria-invalid'), $select->attr('required')])->toBe(['form.chosen', 'true', 'true', null])
            ->and($this->renderSchemaComponent(Select::make('plant')->options(['fern' => 'Fern'])->native(false))->filter(Selectors::JS_CONTROL)->attr('aria-required'))->toBeNull();
    });

    it('hands the controller the selection with its labels, and holds a selected value the map lacks as a selected option', function (): void {
        $render = $this->createLiveComponent(SelectForm::class)->fillForm(['chosen' => 'moss', 'searchable' => 'yew'])->render()->crawler();

        expect($this->selectControllerValue($render->filter('select[name="chosen"]'), 'selectedOptions'))->toBe([['value' => 'moss', 'label' => 'Moss']])
            ->and($this->selectControllerValue($render->filter('select[name="searchable"]'), 'selectedOptions'))->toBe([['value' => 'yew', 'label' => 'Yew']])
            ->and($render->filter('select[name="searchable"] option[selected]')->each(static fn (Crawler $option): array => [$option->attr('value'), $option->text()]))->toBe([['yew', 'Yew']]);
    });

    it('reads a scoped option map once per render, and pays the selected-record query on the JS branch alone', function (bool $isNative, int $statements): void {
        $this->seed($fern = new Garden('Fern'), $moss = new Garden('Moss'));

        $host = new RecordMappingHost();
        [$select] = $host->mapping(Select::make('garden')->relationship('garden', 'name', modifyQueryUsing: static fn (QueryBuilder $query): QueryBuilder => $query)->native($isNative))->record(Seedling::class)->getComponents();
        $host->data['garden'] = (string) $fern->id;

        $this->seed();
        $data = $select->getExtraTemplateData();

        expect($data['options'])->toBe([$fern->id => 'Fern', $moss->id => 'Moss'])
            ->and($data['selectedOptions'])->toBe($isNative ? [] : [['value' => (string) $fern->id, 'label' => 'Fern']])
            ->and($this->statementsRun())->toHaveCount($statements);
    })->with([
        'the native branch' => [true, 1],
        'the JS branch' => [false, 2],
    ]);

    it('re-renders a select whose options follow a live field with the options the field now picks', function (): void {
        $tester = $this->createLiveComponent(SelectForm::class);
        $options = static fn (Crawler $render): array => $render->filter('select[name="species"] option:not([value=""])')->each(static fn (Crawler $option): ?string => $option->attr('value'));

        expect($options($tester->render()->crawler()))->toBe(['mint'])
            ->and($options($tester->fillForm(['kind' => 'tree'])->render()->crawler()))->toBe(['oak']);
    });
});

describe('enum', function (): void {
    it('reads a backed-enum select as the case, and a multiple one as the cases, dropping a value no case backs', function (): void {
        $form = $this->createLiveComponent(SelectForm::class)->fillForm(['enum' => 'b', 'letters' => ['c', 'zz', 'a']])->component()->getSchema('form');

        expect($form->getComponent('enum')->getState())->toBe(StringBackedLetter::B)
            ->and($form->getComponent('letters')->getState())->toBe([StringBackedLetter::C, StringBackedLetter::A]);
    });
});

describe('boolean()', function (): void {
    it('offers Yes and No under a dash placeholder, and the labels it is given', function (): void {
        $options = static fn (Crawler $render): array => $render->filter('option')->each(static fn (Crawler $option): array => [$option->attr('value'), $option->text()]);

        expect($options($this->renderSchemaComponent(Select::make('done')->boolean())))->toBe([['', '-'], ['1', 'Yes'], ['0', 'No']])
            ->and($options($this->renderSchemaComponent(Select::make('done')->boolean('Done', 'Open', 'Either'))))->toBe([['', 'Either'], ['1', 'Done'], ['0', 'Open']]);
    });

    it('reads the submitted 1 and 0 as true and false', function (string $submitted, bool $state): void {
        $tester = $this->createLiveComponent(SelectForm::class)->fillForm(['boolean' => $submitted]);

        expect($tester->component()->getSchema('form')->getComponent('boolean')->getState())->toBe($state);
    })->with([
        'yes' => ['1', true],
        'no' => ['0', false],
    ]);
});

describe('multiple()', function (): void {
    it('keeps the submitted order of a selection, an untouched one being an empty list', function (): void {
        $tester = $this->createLiveComponent(SelectForm::class);

        expect($tester->component()->getSchema('form')->getComponent('multiple')->getState())->toBe([])
            ->and($tester->fillForm(['multiple' => ['b', 'a']])->component()->getSchema('form')->getComponent('multiple')->getState())->toBe(['b', 'a']);
    });

    it('renders the chips control with the multiple attribute and the selection in its order', function (): void {
        $render = $this->createLiveComponent(SelectForm::class)->fillForm(['multiple' => ['b', 'a']])->render()->crawler()->filter('select[name="multiple"]');

        expect($render->filter(Selectors::JS_CONTROL)->attr('multiple'))->not->toBeNull()
            ->and($this->selectControllerValue($render, 'reorderable'))->toBe('true')
            ->and($this->selectControllerValue($render, 'selectedOptions'))->toBe([['value' => 'b', 'label' => 'Banana'], ['value' => 'a', 'label' => 'Apple']]);
    });

    it('labels a composite selection larger than one statement holds in batches, every selected row labelled', function (): void {
        $this->seed(new BenchPot('A', '1', 'Basil'));

        $host = new RecordMappingHost();
        [$select] = $host->mapping(Select::make('pots')->multiple()->relationship('pots', 'plant'))->record(NurseryBench::class)->getComponents();

        $this->seed();

        expect($select->getOptionLabelsFromRelationship(['A~1', ...array_map(static fn (int $index): string => "Z~{$index}", range(1, 500))]))->toBe(['A~1' => 'Basil'])
            ->and($this->statementsRun())->toHaveCount(2);
    });

    it('lists no option of a to-many relationship until the user types, unless it is preloaded', function (): void {
        $this->seed(new Plant('Fern'), new Plant('Moss'));
        $options = fn (Select $select): array => $this->renderSchemaComponent(Group::make([$select])->record(Garden::class))
            ->filter(Selectors::JS_CONTROL . ' option:not([value=""])')
            ->each(static fn (Crawler $option): string => $option->text());

        expect($options(Select::make('plants')->multiple()->relationship('plants', 'name')))->toBe([])
            ->and($options(Select::make('plants')->multiple()->relationship('plants', 'name')->preload()))->toBe(['Fern', 'Moss']);
    });

    it('searches by default, unless searchable(false) says otherwise', function (): void {
        expect($this->selectControllerValue($this->renderSchemaComponent(Select::make('plants')->multiple()->options(['fern' => 'Fern'])), 'searchable'))->toBe('true')
            ->and($this->selectControllerValue($this->renderSchemaComponent(Select::make('plants')->multiple()->options(['fern' => 'Fern'])->searchable(false)), 'searchable'))->toBe('false');
    });
});

describe('getOptionLabel()', function (): void {
    it("answers the selected option's label off the map or through the label closure, else the state itself or null when no default is asked for", function (string $field, mixed $state, bool $withDefault, ?string $label): void {
        $tester = $this->createLiveComponent(SelectForm::class)
            ->fillForm([$field => $state])
            ->call('callSchemaComponentMethod', ['componentKey' => "form.{$field}", 'method' => 'getOptionLabel', 'arguments' => ['withDefault' => $withDefault]]);

        expect(array_column($this->dispatchedBrowserEvents($tester, 'sylament:schema-component-method-result'), 'result', 'method'))->toBe(['getOptionLabel' => $label]);
    })->with([
        'a numeric-string state on an int key' => ['single', '2', true, 'Two'],
        'an enum state' => ['enum', 'c', true, 'C'],
        'a boolean state' => ['boolean', '1', true, 'Yes'],
        'an unmatched state' => ['single', '9', true, '9'],
        'an unmatched state without a default' => ['single', '9', false, null],
        'a state the closure labels' => ['labelled', 'fern', true, 'Fern'],
        'a state the closure does not label' => ['labelled', 'yew', false, null],
    ]);
});

describe('getOptionLabels()', function (): void {
    it('answers the labels of a list through the map, an unlabelled value as itself', function (): void {
        $render = $this->createLiveComponent(SelectForm::class)->fillForm(['multiple' => ['b', 'zz']])->render()->crawler();

        expect($this->selectControllerValue($render->filter('select[name="multiple"]'), 'selectedOptions'))->toBe([['value' => 'b', 'label' => 'Banana'], ['value' => 'zz', 'label' => 'zz']]);
    });

    it('answers the labels of a list through the labels closure, a blank label as its value and a value the closure leaves out not at all', function (): void {
        $render = $this->createLiveComponent(SelectForm::class)->fillForm(['labelledMany' => ['moss', 'bare', 'yew']])->render()->crawler();

        expect($this->selectControllerValue($render->filter('select[name="labelledMany"]'), 'selectedOptions'))->toBe([['value' => 'moss', 'label' => 'Moss'], ['value' => 'bare', 'label' => 'bare']]);
    });
});

describe('validation', function (): void {
    it('accepts an enabled option and refuses a value outside the options or a disabled one', function (string $field, mixed $state, bool $isValid): void {
        $tester = $this->createLiveComponent(SelectForm::class)->fillForm([$field => $state])->call('save');

        $isValid ? $tester->assertHasNoFormErrors([$field]) : $tester->assertHasFormErrors([$field => Choice::class]);
    })->with([
        'an enabled option' => ['single', '2', true],
        'a tampered value' => ['single', '9', false],
        'a disabled option' => ['single', '3', false],
        'a value a label closure would label outside a static map' => ['mapped', 'yew', false],
        'a list of options' => ['multiple', ['a', 'c'], true],
        'a list with one tampered element' => ['multiple', ['a', 'zz'], false],
        'a value the label closure labels' => ['labelled', 'fern', true],
        'a value the label closure does not label' => ['labelled', 'yew', false],
        'a labelled value whose option is disabled' => ['labelled', 'moss', false],
        'a list the labels closure labels' => ['labelledMany', ['fern', 'moss'], true],
        'a list with a value the labels closure leaves out' => ['labelledMany', ['fern', 'yew'], false],
        'a list with a value the labels closure labels blank' => ['labelledMany', ['fern', 'bare'], false],
    ]);

    it('refuses a value the enum cast would null out, and a single select handed a list or false, as a violation', function (mixed $state): void {
        $this->createLiveComponent(SelectForm::class)
            ->fillForm(['enum' => $state])
            ->call('save')
            ->assertHasFormErrors(['enum' => Choice::class]);
    })->with([
        'a value no case backs' => ['deleted-by-hand'],
        'false' => [false],
        'a list' => [['a']],
    ]);

    it('lets a blank state through an optional select, building it no membership constraint', function (mixed $blank): void {
        $this->createLiveComponent(SelectForm::class)
            ->fillForm(['single' => $blank, 'enum' => $blank])
            ->call('save')
            ->assertHasNoFormErrors(['single', 'enum']);

        $host = new RecordMappingHost();
        [$select] = $host->mapping(Select::make('plant')->options(['fern' => 'Fern']))->getComponents();
        $constraints = static fn (): array => array_map(get_class(...), $select->getValidationConstraints());

        $host->data['plant'] = $blank;
        $blankConstraints = $constraints();
        $host->data['plant'] = 'fern';

        expect($blankConstraints)->toBe([])
            ->and($constraints())->toBe([Choice::class]);
    })->with([
        'null' => [null],
        'an empty string' => [''],
    ]);

    it('fails a required select left blank by the required constraint alone', function (): void {
        $this->createLiveComponent(SelectForm::class)
            ->call('save')
            ->assertHasFormErrors(['chosen' => RequiredValue::class])
            ->assertHasNoFormErrors(['chosen' => Choice::class]);
    });

    it('fails a multiple select handed a scalar by a type violation, ahead of the check that it has anything to validate with', function (string $field): void {
        $this->createLiveComponent(SelectForm::class)
            ->fillForm([$field => 'fern'])
            ->call('save')
            ->assertHasFormErrors([$field => Type::class])
            ->assertHasNoFormErrors([$field => Choice::class]);
    })->with([
        'a select with options' => ['multiple'],
        'a select with nothing to validate with' => ['unusableMany'],
    ]);

    it('fails a list outside the item bounds by the count, an empty one included', function (array $state, string $message): void {
        $this->createLiveComponent(SelectForm::class)
            ->fillForm(['bounded' => $state])
            ->call('save')
            ->assertHasFormErrors(['bounded' => Count::class])
            ->assertHasFormErrors(['bounded' => $message]);
    })->with([
        'too few' => [[], 'This collection should contain 1 element or more.'],
        'too many' => [['a', 'b', 'c'], 'This collection should contain 2 elements or less.'],
    ]);

    it('hands the controller the item limit and its message, Only :count can be selected. unless the field sets one', function (): void {
        $default = $this->renderSchemaComponent(Select::make('plants')->multiple()->options(['fern' => 'Fern'])->maxItems(2));
        $custom = $this->renderSchemaComponent(Select::make('plants')->multiple()->options(['fern' => 'Fern'])->maxItems(2)->maxItemsMessage('Two plants at most.'));

        expect([$this->selectControllerValue($default, 'maxItems'), $this->selectControllerValue($default, 'maxItemsMessage')])->toBe(['2', 'Only 2 can be selected.'])
            ->and($this->selectControllerValue($custom, 'maxItemsMessage'))->toBe('Two plants at most.');
    });

    it('refuses to validate a filled select with neither options nor the label closure of its multiplicity', function (string $field, mixed $state, string $closure): void {
        expect(fn () => $this->createLiveComponent(SelectForm::class)->fillForm([$field => $state])->call('save'))
            ->toThrow(LogicException::class, "Sylament failed to validate the [data.{$field}] select because it has neither an options() nor a {$closure}() configuration.");
    })->with([
        'a single select' => ['unusable', 'fern', 'getOptionLabelUsing'],
        'a multiple select carrying only the single closure' => ['unusableMany', ['fern'], 'getOptionLabelsUsing'],
    ]);

    it('checks an in() list instead of the options', function (string $state, bool $isValid): void {
        $tester = $this->createLiveComponent(SelectForm::class)->fillForm(['restricted' => $state])->call('save');

        $isValid ? $tester->assertHasNoFormErrors(['restricted']) : $tester->assertHasFormErrors(['restricted' => Choice::class]);
    })->with([
        'a listed value no option offers' => ['yew', true],
        'an option the list leaves out' => ['moss', false],
    ]);

    it('words the Choice violation with the message validationMessages() gives it', function (): void {
        $this->createLiveComponent(SelectForm::class)
            ->fillForm(['messaged' => 'yew'])
            ->call('save')
            ->assertHasFormErrors(['messaged' => 'Pick a listed plant.']);
    });

    it('probes a searchable relationship select through its scoped query, so a row the scope leaves out is no choice', function (): void {
        $this->seed($moss = new Garden('Moss'));

        $this->createLiveComponent(SelectRelationshipForm::class)
            ->fillForm(['scoped' => (string) $moss->id, 'searched' => (string) $moss->id])
            ->call('save')
            ->assertHasFormErrors(['scoped' => Choice::class])
            ->assertHasNoFormErrors(['searched']);
    });

    it('fails a to-many selection past maxItems() by the count and the membership both, but a composite one by the count alone without a statement', function (): void {
        $this->seed($fern = new Plant('Fern'), $moss = new Plant('Moss'), new BenchPot('A', '1', 'Basil'));
        $errors = function (Select $select, string $recordClass, array $state): array {
            $host = $this->validatingRecordMappingHost();
            $schema = $host->mapping($select)->record($recordClass);
            $host->data[$select->getName()] = $state;

            try {
                $schema->validate();
            } catch (ValidationException $validationException) {
                return $validationException->getErrors();
            }

            return [];
        };

        expect($errors(Select::make('plants')->multiple()->relationship('plants', 'name')->maxItems(2), Garden::class, [(string) $fern->id, (string) $moss->id, '999']))->toBe(['data.plants' => [
            'This collection should contain 2 elements or less.',
            'One or more of the given values is invalid.',
        ]]);

        $this->seed();

        expect($errors(Select::make('pots')->multiple()->relationship('pots', 'plant')->maxItems(2), NurseryBench::class, ['A~1', 'B~2', 'C~3']))->toBe(['data.pots' => [
            'This collection should contain 2 elements or less.',
        ]])
            ->and($this->statementsRun())->toBe([]);
    });
});

describe('search', function (): void {
    it('hands the typed text to the search closure and answers its results in the JS shape through a browser event', function (): void {
        $tester = $this->createLiveComponent(SelectForm::class)
            ->call('callSchemaComponentMethod', ['componentKey' => 'form.searchable', 'method' => 'getSearchResultsForJs', 'arguments' => ['search' => 'fe']]);

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['search: fe fe fe'])
            ->and($this->dispatchedBrowserEvents($tester, 'sylament:schema-component-method-result'))->toBe([
                ['componentKey' => 'form.searchable', 'method' => 'getSearchResultsForJs', 'fileKey' => null, 'result' => [['label' => 'Fern (fe)', 'value' => 'fern', 'isDisabled' => false]]],
            ]);
    });

    it('lowers both sides of the relationship LIKE when forced case-insensitive, and neither when forced not to, on every platform', function (string $field, bool $isLowered): void {
        $this->createLiveComponent(SelectRelationshipForm::class)
            ->call('callSchemaComponentMethod', ['componentKey' => "form.{$field}", 'method' => 'getSearchResultsForJs', 'arguments' => ['search' => 'Fern']]);

        $searches = array_values(array_filter($this->statementsRun(), static fn (string $sql): bool => str_contains($sql, ' LIKE ')));

        expect($searches)->toHaveCount(1)
            ->and(str_contains($searches[0], 'LOWER('))->toBe($isLowered);
    })->with([
        'forced case-insensitive' => ['caseless', true],
        'forced case-sensitive' => ['caseful', false],
    ]);
});

describe('searchable()', function (): void {
    it('searches the columns an array names, and the title attribute again once searchable() is called bare', function (): void {
        $quiet = new BerthMooring();
        [$quiet->pier, $quiet->slot, $quiet->label] = ['North', 'S1', 'Quiet'];
        $view = new BerthMooring();
        [$view->pier, $view->slot, $view->label] = ['South', 'S2', 'North view'];
        $this->seed($quiet, $view);

        $found = function (string $field): array {
            $tester = $this->createLiveComponent(SelectBerthForm::class)
                ->call('callSchemaComponentMethod', ['componentKey' => "form.{$field}", 'method' => 'getSearchResultsForJs', 'arguments' => ['search' => 'North']]);

            return array_column($this->dispatchedBrowserEvents($tester, 'sylament:schema-component-method-result')[0]['result'] ?? [], 'label');
        };

        expect($found('byPier'))->toBe(['Quiet'])
            ->and($found('byLabel'))->toBe(['North view']);
    });
});

describe('preload()', function (): void {
    it('lists a searchable relationship select only once preloaded, limited and ordered by title, and a select that does not search in full', function (): void {
        $this->seed(new Garden('Moss'), new Garden('Fern'), new Garden('Ferret'));

        $render = $this->createLiveComponent(SelectRelationshipForm::class)->render()->crawler();
        $options = static fn (string $field): array => $render->filter("select[name=\"{$field}\"] option:not([value=\"\"])")->each(static fn (Crawler $option): string => $option->text());

        expect($options('searched'))->toBe([])
            ->and($options('preloaded'))->toBe(['Fern', 'Ferret'])
            ->and($this->selectControllerValue($render->filter('select[name="preloaded"]'), 'optionsLimit'))->toBe('2')
            ->and($options('garden'))->toBe(['Fern', 'Ferret', 'Moss']);
    });

    it('lists fifty options unless optionsLimit() says otherwise', function (): void {
        $this->seed(...array_map(static fn (int $index): Garden => new Garden(sprintf('Garden %02d', $index)), range(1, 51)));

        $render = $this->renderSchemaComponent(Group::make([Select::make('garden')->relationship('garden', 'name')->searchable()->preload()])->record(Seedling::class));

        expect($render->filter(Selectors::JS_CONTROL . ' option:not([value=""])')->count())->toBe(50)
            ->and($this->selectControllerValue($render, 'optionsLimit'))->toBe('50');
    });
});

describe('dynamic options', function (): void {
    it('asks the server while the user types only where a search can answer, showing the no-options message up front otherwise', function (Closure $select, array $flags): void {
        $render = $this->renderSchemaComponent(Group::make([$select()])->record(Seedling::class));

        expect([$this->selectControllerValue($render, 'dynamicSearchResults'), $this->selectControllerValue($render, 'initialNoOptionsMessage')])->toBe($flags);
    })->with([
        'a static map' => [static fn (): Select => Select::make('garden')->options(['fern' => 'Fern'])->searchable(), ['false', 'true']],
        'a search closure' => [static fn (): Select => Select::make('garden')->searchable()->getSearchResultsUsing(static fn (): array => []), ['true', 'false']],
        'a searchable relationship' => [static fn (): Select => Select::make('garden')->relationship('garden', 'name')->searchable(), ['true', 'false']],
        'a preloaded relationship' => [static fn (): Select => Select::make('garden')->relationship('garden', 'name')->searchable()->preload(), ['true', 'true']],
        'a relationship with no search column' => [static fn (): Select => Select::make('garden')->relationship('garden', 'name')->searchable([]), ['false', 'false']],
    ]);
});

describe('option actions', function (): void {
    it('puts a gray create button in the suffix once a create form is declared, a hand-built field included, and none while the select is disabled', function (): void {
        $select = static fn (): Select => Select::make('plant')->options(['fern' => 'Fern'])->createOptionUsing(static fn (): string => 'fern');
        $render = $this->renderSchemaComponent($select()->createOptionForm([TextInput::make('name')]));

        expect($render)->toContainButtons(SupportSelectors::SUFFIX_ACTIONS . SupportSelectors::SMALL_ICON_BUTTON . SupportSelectors::GRAY, ['Create'])
            ->toContainElement(SupportSelectors::SUFFIX_ACTIONS . ' svg', 'plus-20-solid');

        $handBuilt = new Select();
        $handBuilt->name('plant')->statePath('plant')->configure();

        expect($this->renderSchemaComponent($handBuilt->options(['fern' => 'Fern'])->createOptionForm([TextInput::make('name')])->createOptionUsing(static fn (): string => 'fern')))
            ->toContainElement(SupportSelectors::SUFFIX_ACTIONS . ' svg', 'plus-20-solid');

        expect($this->renderSchemaComponent($select()))->toContainElement(Selectors::NATIVE_SELECT)->toContainButtons(SupportSelectors::SUFFIX_ACTIONS, [])
            ->and($this->renderSchemaComponent($select()->createOptionForm([TextInput::make('name')])->disabled()))->toContainButtons(SupportSelectors::SUFFIX_ACTIONS, []);
    });

    it('puts an edit button beside it for a single selection only, and opens each in its modal', function (): void {
        $chrome = static function (Testable $tester): array {
            $dialog = $tester->render()->crawler()->filter('dialog[open]');

            return [$dialog->filter('#' . $dialog->attr('aria-labelledby'))->text(), $dialog->filter('footer button')->each(static fn (Crawler $button): string => $button->text())];
        };
        $tester = $this->createLiveComponent(SelectOptionActionForm::class)->fillForm(['plant' => 'fern', 'plants' => ['fern']]);
        $triggers = static fn (string $field): string => sprintf('button[data-live-context-param=\'{"schemaComponent":"form.%s"}\']', $field);
        $render = $tester->render()->crawler();

        expect($render)->toContainButtons($triggers('plant'), ['Create', 'Edit'])
            ->toContainElement($triggers('plant') . ' svg', 'pencil-square-20-solid')
            ->toContainButtons($triggers('plants'), ['Create']);

        expect($chrome($this->createLiveComponent(SelectOptionActionForm::class)->mountAction(TestAction::make('createOption')->schemaComponent('plant'))))->toBe(['Create', ['Create', 'Cancel']])
            ->and($chrome($this->createLiveComponent(SelectOptionActionForm::class)->mountAction(TestAction::make('createOption')->schemaComponent('plants'))))->toBe(['Create', ['Create', 'Create & create another', 'Cancel']])
            ->and($chrome($tester->mountAction(TestAction::make('editOption')->schemaComponent('plant'))))->toBe(['Edit', ['Save', 'Cancel']])
            ->and($chrome($this->createLiveComponent(SelectRelationshipForm::class)->mountAction(TestAction::make('createOption')->schemaComponent('garden'))))->toBe(['New garden', ['Create', 'Cancel']]);
    });

    it('creates the option a relationship select names, selects it and asks the control to refresh its label', function (): void {
        $tester = $this->createLiveComponent(SelectRelationshipForm::class)
            ->callAction(TestAction::make('createOption')->schemaComponent('garden'), data: ['name' => 'Rose']);

        $rose = $this->entityManager()->getRepository(Garden::class)->findOneBy(['name' => 'Rose']);

        expect($rose)->toBeInstanceOf(Garden::class)
            ->and($tester->component()->data['garden'] ?? null)->toBe((string) $rose?->id)
            ->and($this->dispatchedBrowserEvents($tester, 'sylament:select-refresh-selected-option-label'))->toBe([['statePath' => 'data.garden']]);

        $tester->call('save')->assertHasNoFormErrors(['garden']);
    });

    it('validates the create modal alone, writing nothing while it fails', function (): void {
        $this->createLiveComponent(SelectRelationshipForm::class)
            ->callAction(TestAction::make('createOption')->schemaComponent('garden'), data: ['name' => ''])
            ->assertHasFormErrors(['name' => RequiredValue::class])
            ->assertFormSet(['garden' => null], 'form');

        expect($this->entityManager()->getRepository(Garden::class)->count([]))->toBe(0);
    });

    it('fills the edit modal from the selected record, writes the edit onto it and asks the control to refresh its label', function (): void {
        $this->seed($fern = new Garden('Fern'));

        $tester = $this->createLiveComponent(SelectRelationshipForm::class)
            ->fillForm(['garden' => (string) $fern->id])
            ->mountAction(TestAction::make('editOption')->schemaComponent('garden'))
            ->assertSchemaStateSet(['name' => 'Fern']);

        $dialog = $tester->render()->crawler()->filter('dialog[open]');

        expect($dialog->filter('#' . $dialog->attr('aria-labelledby'))->text())->toBe('Rename garden');

        $tester->fillForm(['name' => 'Fernery'])->callMountedAction();

        expect($this->reload($fern)->name)->toBe('Fernery')
            ->and($this->dispatchedBrowserEvents($tester, 'sylament:select-refresh-selected-option-label'))->toBe([['statePath' => 'data.garden']]);
    });

    it('fills the edit modal from the array a fillEditOptionActionFormUsing() closure answers', function (): void {
        $this->seed($fern = new Garden('Fern'));

        $this->createLiveComponent(SelectRelationshipForm::class)
            ->fillForm(['filledByHand' => (string) $fern->id])
            ->mountAction(TestAction::make('editOption')->schemaComponent('filledByHand'))
            ->assertSchemaStateSet(['name' => SelectRelationshipForm::CUSTOM_LABEL]);
    });

    it("binds a non-relationship select's create modal to the field's record and its edit modal to the selected record the resolver answers", function (): void {
        $tester = $this->createLiveComponent(SelectOptionActionForm::class)
            ->fillForm(['plant' => 'fern'])
            ->callAction(TestAction::make('editOption')->schemaComponent('plant'), data: ['subject' => 'Renamed']);

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['update on Fern: Renamed']);

        $tester->callAction(TestAction::make('createOption')->schemaComponent('plant'), data: ['subject' => 'Sprout']);

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['create on ' . SelectOptionActionForm::RECORD_SUBJECT . ': Sprout'])
            ->and($tester->component()->data['plant'] ?? null)->toBe('moss');
    });

    it('resolves the selected record once while the selection stays, again once it changes, and not at all for a blank one', function (): void {
        $log = $this::getContainer()->get(HookLog::class);
        $host = new RecordMappingHost();
        [$select] = $host->mapping(Select::make('plant')->options(['fern' => 'Fern', 'moss' => 'Moss'])->getSelectedRecordUsing(static function (string $state) use ($log): Memo {
            $log->record("resolve: {$state}");

            return new Memo($state);
        }))->getComponents();

        expect($select->getSelectedRecord())->toBeNull();

        $host->data['plant'] = 'fern';
        $first = $select->getSelectedRecord();

        expect($select->getSelectedRecord())->toBe($first);

        $host->data['plant'] = 'moss';

        expect($select->getSelectedRecord())->toEqual(new Memo('moss'))
            ->and($log->hooks)->toBe(['resolve: fern', 'resolve: moss']);
    });

    it('answers no selected record, without a statement, for a key the relationship cannot decode or a list', function (mixed $state): void {
        $host = new RecordMappingHost();
        [$select] = $host->mapping(Select::make('garden')->relationship('garden', 'name')->searchable())->record(Seedling::class)->getComponents();
        $host->data['garden'] = $state;

        $this->seed();

        expect($select->getSelectedRecord())->toBeNull()
            ->and($this->statementsRun())->toBe([]);
    })->with([
        'a malformed key' => ['not-a-key'],
        'a list' => [['1', '2']],
    ]);

    it('keeps the relationship defaults over a closure declared before relationship(), and a closure declared after it', function (): void {
        $this->seed($fern = new Garden('Fern'));

        $before = $this->createLiveComponent(SelectRelationshipForm::class)->callAction(TestAction::make('createOption')->schemaComponent('createdBefore'), data: ['name' => 'Tulip']);

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe([])
            ->and($before->component()->data['createdBefore'] ?? null)->toBe((string) $this->entityManager()->getRepository(Garden::class)->findOneBy(['name' => 'Tulip'])?->id);

        $after = $this->createLiveComponent(SelectRelationshipForm::class)->callAction(TestAction::make('createOption')->schemaComponent('createdAfter'), data: ['name' => 'Lily']);

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['createOption: Lily'])
            ->and($after->component()->data['createdAfter'] ?? null)->toBe('by-hand')
            ->and($this->entityManager()->getRepository(Garden::class)->findOneBy(['name' => 'Lily']))->toBeNull();

        $render = $this->createLiveComponent(SelectRelationshipForm::class)->fillForm(['labelledBefore' => (string) $fern->id, 'labelledAfter' => (string) $fern->id])->render()->crawler();

        expect($this->selectControllerValue($render->filter('select[name="labelledBefore"]'), 'selectedOptions'))->toBe([['value' => (string) $fern->id, 'label' => 'Fern']])
            ->and($this->selectControllerValue($render->filter('select[name="labelledAfter"]'), 'selectedOptions'))->toBe([['value' => (string) $fern->id, 'label' => SelectRelationshipForm::CUSTOM_LABEL]]);
    });

    it("writes what the create action's mutateDataUsing() answers, and refuses a path it adds that no component maps", function (string $field, array $data, ?string $written): void {
        $create = fn () => $this->createLiveComponent(SelectRelationshipForm::class)->callAction(TestAction::make('createOption')->schemaComponent($field), data: $data);

        if ($written === null) {
            expect($create)->toThrow(LogicException::class, "The [data.unmapped] select's [createOption] action added [nickname] in mutateDataUsing(), but no component of its schema maps those paths, so the default write would drop them. Map each with a component — a Hidden one for a value the user should not type, with required(false) where the record would otherwise require it — or take the write over with createOptionUsing().")
                ->and($this->entityManager()->getRepository(Garden::class)->count([]))->toBe(0);

            return;
        }

        $create();

        expect(array_map(static fn (Garden $garden): string => $garden->name, $this->entityManager()->getRepository(Garden::class)->findAll()))->toBe([$written]);
    })->with([
        'an unmapped path added' => ['unmapped', ['name' => 'Rose'], null],
        'a Hidden path set' => ['hiddenMapped', [], 'Hidden fern'],
        'a mapped path overwritten' => ['overwritten', ['name' => 'Rose'], 'ROSE'],
    ]);

    it("runs the manage-level modifier, then the action's own, each replacing the action only when it answers one", function (): void {
        $select = static fn (): Select => Select::make('plant')->options(['fern' => 'Fern'])->createOptionForm([TextInput::make('name')])->createOptionUsing(static fn (): string => 'fern');

        expect($this->renderSchemaComponent($select()->manageOptionActions(static fn (Action $action): Action => $action->label('Add a plant'))->createOptionAction(static function (Action $action): void {
            $action->color('danger');
        })))->toContainButtons(SupportSelectors::SUFFIX_ACTIONS . SupportSelectors::color('danger'), ['Add a plant'])
            ->and($this->renderSchemaComponent($select()->createOptionAction(static fn (): Action => Action::make('createOption')->label('Plant something'))))->toContainButtons(SupportSelectors::SUFFIX_ACTIONS, ['Plant something'])
            ->and(fn () => $this->renderSchemaComponent($select()->createOptionAction(static fn (): string => 'not an action')))
            ->toThrow(RuntimeError::class, 'An option action modifier on ' . Select::class . ' must return an ' . Action::class . ' instance or null.');
    });

    it('refuses a created option on a key a record already holds under the key fields, writing nothing', function (): void {
        $mooring = new BerthMooring();
        [$mooring->pier, $mooring->slot, $mooring->label] = ['North', 'S1', 'Quiet'];
        $this->seed($mooring);

        $this->createLiveComponent(SelectBerthForm::class)
            ->callAction(TestAction::make('createOption')->schemaComponent('mooring'), data: ['pier' => 'North', 'slot' => 'S1', 'label' => 'Loud'])
            ->assertHasFormErrors(['pier' => 'This value is already used.', 'slot' => 'This value is already used.']);

        expect($this->entityManager()->getConnection()->fetchAllAssociative('SELECT pier, slot, label FROM BerthMooring'))->toBe([['pier' => 'North', 'slot' => 'S1', 'label' => 'Quiet']]);
    });

    it('refuses an edit that changes the selected key, keeping the row and the selection', function (): void {
        $mooring = new BerthMooring();
        [$mooring->pier, $mooring->slot, $mooring->label] = ['North', 'S1', 'Quiet'];
        $this->seed($mooring);

        $this->createLiveComponent(SelectBerthForm::class)
            ->fillForm(['mooring' => 'North~S1'])
            ->callAction(TestAction::make('editOption')->schemaComponent('mooring'), data: ['pier' => 'North', 'slot' => 'S9', 'label' => 'Moved'])
            ->assertHasFormErrors(['slot' => 'This value cannot be changed.'])
            ->assertHasNoFormErrors(['pier'])
            ->assertFormSet(['mooring' => 'North~S1'], 'form');

        expect($this->entityManager()->getConnection()->fetchAllAssociative('SELECT pier, slot, label FROM BerthMooring'))->toBe([['pier' => 'North', 'slot' => 'S1', 'label' => 'Quiet']]);
    });

    it('selects a created option by the key its row reads back', function (): void {
        $tester = $this->createLiveComponent(SelectBerthForm::class, ['record' => Quay::class])
            ->callAction(TestAction::make('createOption')->schemaComponent('bollard'), data: ['code' => 'Bollard-1', 'label' => 'East']);

        expect($tester->component()->data['bollard'] ?? null)->toBe('bollard-1')
            ->and($this->entityManager()->getConnection()->fetchAllAssociative('SELECT code FROM QuayBollard'))->toBe([['code' => 'bollard-1']]);
    });

    it('refuses by name a created option whose identifier is incomplete, writing nothing', function (string $record, string $field, array $data, string $class, string $advice): void {
        expect(fn () => $this->createLiveComponent(SelectBerthForm::class, ['record' => $record])->callAction(TestAction::make('createOption')->schemaComponent($field), data: $data))
            ->toThrow(LogicException::class, "The [{$field}] select created a [{$class}] whose identifier is incomplete, so no option could name it. Put every identifier field in createOptionForm().{$advice}")
            ->and($this->entityManager()->getRepository($class)->count([]))->toBe(0);
    })->with([
        'two strings, one no field maps' => [Berth::class, 'partialMooring', ['pier' => 'North', 'label' => 'Quiet'], BerthMooring::class, ''],
        'the owner and a string, the owner no field maps' => [Berth::class, 'cleat', ['slot' => 'S1', 'label' => 'Quiet'], BerthCleat::class, ''],
        'two strings a prePersist callback leaves blank' => [Quay::class, 'mooring', ['pier' => 'North', 'label' => 'Quiet'], QuayMooring::class, ''],
        'a blank key field under validateRecordKey(false)' => [Berth::class, 'unguardedMooring', ['pier' => 'North', 'slot' => '', 'label' => 'Quiet'], BerthMooring::class, ' The schema switches the key rules off with validateRecordKey(false), so a key field it maps is not refused while blank; switch them back on to have the blank field refused under it.'],
    ]);
});
