<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Forms\Components\Select;
use Sylament\Forms\Validation\RequiredValue;
use Sylament\Schemas\Components\Section;
use Sylament\Tests\Fixtures\Forms\SelectForm;
use Sylament\Tests\Fixtures\Forms\Selectors;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Schemas\RecordMappingHost;
use Sylament\Tests\Fixtures\Schemas\StringBackedLetter;
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

    it('re-renders a select whose options follow a live field with the options the field now picks', function (): void {
        $tester = $this->createLiveComponent(SelectForm::class);
        $options = static fn (Crawler $render): array => $render->filter('select[name="species"] option:not([value=""])')->each(static fn (Crawler $option): ?string => $option->attr('value'));

        expect($options($tester->render()->crawler()))->toBe(['mint'])
            ->and($options($tester->fillForm(['kind' => 'tree'])->render()->crawler()))->toBe(['oak']);
    });
});

describe('state', function (): void {
    it('stores a cleared select as null', function (): void {
        $tester = $this->createLiveComponent(SelectForm::class)->fillForm(['single' => '2'])->fillForm(['single' => '']);

        expect($tester->component()->getSchema('form')->getComponent('single')->getState())->toBeNull();
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

    it('fails a multiple select handed a scalar by a type violation', function (): void {
        $this->createLiveComponent(SelectForm::class)
            ->fillForm(['multiple' => 'a'])
            ->call('save')
            ->assertHasFormErrors(['multiple' => Type::class])
            ->assertHasNoFormErrors(['multiple' => Choice::class]);
    });

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

    it('asks the server while the user types only for a select with a search closure, which then shows no up-front no-options message', function (): void {
        $render = $this->createLiveComponent(SelectForm::class)->render()->crawler();
        $flags = fn (string $field): array => [
            $this->selectControllerValue($render->filter("select[name=\"{$field}\"]"), 'dynamicSearchResults'),
            $this->selectControllerValue($render->filter("select[name=\"{$field}\"]"), 'initialNoOptionsMessage'),
        ];

        expect($flags('chosen'))->toBe(['false', 'true'])
            ->and($flags('searchable'))->toBe(['true', 'false']);
    });
});
