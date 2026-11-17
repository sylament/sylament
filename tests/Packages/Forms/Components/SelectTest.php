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
use Sylament\Schemas\State\AssociationReader;
use Sylament\Tests\Fixtures\Entity\Allotment;
use Sylament\Tests\Fixtures\Entity\BenchPot;
use Sylament\Tests\Fixtures\Entity\Berth;
use Sylament\Tests\Fixtures\Entity\BerthCleat;
use Sylament\Tests\Fixtures\Entity\BerthMooring;
use Sylament\Tests\Fixtures\Entity\CaselessHandle;
use Sylament\Tests\Fixtures\Entity\Garden;
use Sylament\Tests\Fixtures\Entity\Lighthouse;
use Sylament\Tests\Fixtures\Entity\LighthouseLog;
use Sylament\Tests\Fixtures\Entity\NurseryBench;
use Sylament\Tests\Fixtures\Entity\Plant;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Entity\PropagatedCutting;
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
use Sylament\Tests\Infrastructure\Doctrine\TestPlatform;
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

    it('saves a multiple() relationship selection through getData(), since the field stays dehydrated', function (): void {
        $garden = new Garden('North');
        $garden->addPlant($fern = new Plant('Fern'));
        $this->seed($fern, $moss = new Plant('Moss'), $garden);
        $garden = $this->entityManager()->find(Garden::class, $garden->id);

        $host = $this->validatingRecordMappingHost();
        $schema = $host->mapping(Select::make('plants')->multiple()->relationship('plants', 'name'))->record($garden);
        $schema->fill($garden);
        $host->data['plants'] = [(string) $moss->id];

        expect($schema->getData()->plants->map(static fn (Plant $plant): string => $plant->name)->getValues())->toBe(['Moss']);
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
        'a labelled list with a value whose option is disabled' => ['labelledMany', ['fern', 'ivy'], false],
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

    it('checks the submitted key of a relationship select that does not search against its option map', function (): void {
        $this->seed($garden = new Garden('North'));
        $host = $this->validatingRecordMappingHost();
        $schema = $host->mapping(Select::make('garden')->relationship('garden', 'name'))->record(Seedling::class);
        [$select] = $schema->getComponents();
        $errors = function (string $state) use ($host, $schema): array {
            $host->data['garden'] = $state;

            try {
                $schema->validate();
            } catch (ValidationException $validationException) {
                return $validationException->getErrors();
            }

            return [];
        };

        expect($select->getOptions())->toBe([$garden->id => 'North'])
            ->and($errors((string) $garden->id))->toBe([])
            ->and($errors('999999'))->toBe(['data.garden' => ['The value you selected is not a valid choice.']]);
    });

    it('probes the submitted key of a searchable relationship select against the target table, a multiple one without preload() included', function (Closure $select, string $recordClass, Closure $states, string $message): void {
        $this->seed($garden = new Garden('North'), $plant = new Plant('Fern'));
        [$known, $unknown] = $states($garden, $plant);
        $host = $this->validatingRecordMappingHost();
        $schema = $host->mapping($select())->record($recordClass);
        [$component] = $schema->getComponents();
        $errors = function (mixed $state) use ($host, $schema, $component): array {
            $host->data[$component->getName()] = $state;

            try {
                $schema->validate();
            } catch (ValidationException $validationException) {
                return $validationException->getErrors();
            }

            return [];
        };

        expect($component->getOptions())->toBe([])
            ->and($errors($known))->toBe([])
            ->and($errors($unknown))->toBe(["data.{$component->getName()}" => [$message]]);
    })->with([
        'a single selection' => [
            fn (): Select => Select::make('garden')->relationship('garden', 'name')->searchable(),
            Seedling::class,
            fn (Garden $garden): array => [(string) $garden->id, '999999'],
            'The value you selected is not a valid choice.',
        ],
        'a multiple selection' => [
            fn (): Select => Select::make('plants')->multiple()->relationship('plants', 'name'),
            Garden::class,
            fn (Garden $garden, Plant $plant): array => [[(string) $plant->id], [(string) $plant->id, '999999']],
            'One or more of the given values is invalid.',
        ],
    ]);
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
        'a preloaded search closure' => [static fn (): Select => Select::make('garden')->searchable()->getSearchResultsUsing(static fn (): array => [])->preload(), ['true', 'true']],
        'a relationship with no search column' => [static fn (): Select => Select::make('garden')->relationship('garden', 'name')->searchable([]), ['false', 'false']],
    ]);

    it('answers no search result to a client that asks a select no search closure answers for', function (): void {
        $tester = $this->createLiveComponent(SelectForm::class)
            ->call('callSchemaComponentMethod', ['componentKey' => 'form.single', 'method' => 'getSearchResultsForJs', 'arguments' => ['search' => 'On']]);

        expect($this->dispatchedBrowserEvents($tester, 'sylament:schema-component-method-result'))->toBe([
            ['componentKey' => 'form.single', 'method' => 'getSearchResultsForJs', 'fileKey' => null, 'result' => []],
        ]);
    });
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

    it('appends a created option to a multiple selection, and keeps the modal open on a blank form to create another', function (): void {
        $tester = $this->createLiveComponent(SelectOptionActionForm::class)
            ->fillForm(['plants' => ['fern']])
            ->callAction(TestAction::make('createOption')->schemaComponent('plants'), data: ['subject' => 'Moss'])
            ->assertFormSet(['plants' => ['fern', 'moss']], 'form');

        expect($tester->render()->crawler())->not->toContainElement('dialog[open]');

        $another = $this->createLiveComponent(SelectOptionActionForm::class)
            ->fillForm(['plants' => ['fern']])
            ->mountAction(TestAction::make('createOption')->schemaComponent('plants'))
            ->fillForm(['subject' => 'Moss'])
            ->callMountedAction(['another' => true])
            ->assertFormSet(['plants' => ['fern', 'moss']], 'form')
            ->assertSchemaStateSet(['subject' => null]);
        $dialog = $another->render()->crawler()->filter('dialog[open]');

        expect($dialog->filter('#' . $dialog->attr('aria-labelledby'))->text())->toBe('Create');
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

    it("runs the manage-level modifier, then the action's own, each replacing the action only when it answers one, the edit action's as the create action's", function (): void {
        $select = static fn (): Select => Select::make('plant')->options(['fern' => 'Fern'])->createOptionForm([TextInput::make('name')])->createOptionUsing(static fn (): string => 'fern');

        expect($this->renderSchemaComponent($select()->manageOptionActions(static fn (Action $action): Action => $action->label('Add a plant'))->createOptionAction(static function (Action $action): void {
            $action->color('danger');
        })))->toContainButtons(SupportSelectors::SUFFIX_ACTIONS . SupportSelectors::color('danger'), ['Add a plant'])
            ->and($this->renderSchemaComponent($select()->createOptionAction(static fn (): Action => Action::make('createOption')->label('Plant something'))))->toContainButtons(SupportSelectors::SUFFIX_ACTIONS, ['Plant something'])
            ->and(fn () => $this->renderSchemaComponent($select()->createOptionAction(static fn (): string => 'not an action')))
            ->toThrow(RuntimeError::class, 'An option action modifier on ' . Select::class . ' must return an ' . Action::class . ' instance or null.')
            ->and($this->renderSchemaComponent(
                Select::make('plant')->options(['fern' => 'Fern'])->editOptionForm([TextInput::make('name')])->editOptionAction(static fn (Action $action): Action => $action->label('Revise')),
                ['plant' => 'fern'],
            ))->toContainButtons(SupportSelectors::SUFFIX_ACTIONS, ['Revise']);
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

describe('relationship search and labels', function (): void {
    it('searches every search column with a LIKE, ORed over the scoped query, matching a term typed in another case and ordering by the title attribute', function (): void {
        $moorings = [];

        foreach ([['North', 'S1', 'Quiet'], ['South', 'S2', 'North view'], ['East', 'S3', 'Calm'], ['North', 'S4', 'Hidden']] as [$pier, $slot, $label]) {
            $mooring = new BerthMooring();
            [$mooring->pier, $mooring->slot, $mooring->label] = [$pier, $slot, $label];
            $moorings[] = $mooring;
        }

        $this->seed(...$moorings);
        [$select] = new RecordMappingHost()->mapping(
            Select::make('mooring')->relationship('mooring', 'label', modifyQueryUsing: static fn (QueryBuilder $query): QueryBuilder => $query->andWhere("record.label <> 'Hidden'"))->searchable(['pier', 'label']),
        )->record(Berth::class)->getComponents();

        expect(array_values($select->getSearchResults('north')))->toBe(['North view', 'Quiet']);

        $searches = array_values(array_filter($this->statementsRun(), static fn (string $sql): bool => str_contains($sql, ' LIKE ')));

        expect($searches)->toHaveCount(1);
    });

    it('answers no search result, without a statement, for a relationship with no column to search', function (): void {
        [$select] = new RecordMappingHost()->mapping(
            Select::make('garden')->relationship('garden')->getOptionLabelFromRecordUsing(static fn (Garden $garden): string => $garden->name)->searchable(),
        )->record(Seedling::class)->getComponents();
        $this->seed(new Garden('Fern'));

        expect($select->getSearchResults('fern'))->toBe([])
            ->and($this->statementsRun())->toBe([]);
    });

    it('caps the search results at optionsLimit(), or at the limit the scope set, which the control then speaks, and lists the map of a select with no search column in full', function (): void {
        $this->seed(new Garden('Fern'), new Garden('Fernando'), new Garden('Ferret'));
        $limitedToOne = static fn (QueryBuilder $query): QueryBuilder => $query->setMaxResults(1);
        [$limited, $scoped, $untitled] = new RecordMappingHost()->mapping(
            Select::make('limited')->relationship('garden', 'name')->searchable()->optionsLimit(2),
            Select::make('scoped')->relationship('garden', 'name', modifyQueryUsing: $limitedToOne)->searchable()->optionsLimit(2),
            Select::make('untitled')->relationship('garden')->getOptionLabelFromRecordUsing(static fn (Garden $garden): string => $garden->name)->searchable()->preload()->optionsLimit(1),
        )->record(Seedling::class)->getComponents();

        expect(array_values($limited->getSearchResults('fer')))->toBe(['Fern', 'Fernando'])
            ->and(array_values($scoped->getSearchResults('fer')))->toBe(['Fern'])
            ->and($untitled->getOptions())->toHaveCount(3);

        $render = $this->renderSchemaComponent(Group::make([
            Select::make('garden')->relationship('garden', 'name', modifyQueryUsing: $limitedToOne)->searchable()->preload()->optionsLimit(2),
        ])->record(Seedling::class));

        expect($render->filter(Selectors::JS_CONTROL . ' option:not([value=""])')->each(static fn (Crawler $option): string => $option->text()))->toBe(['Fern'])
            ->and($this->selectControllerValue($render, 'optionsLimit'))->toBe('1');
    });

    it('orders by the title attribute unless the scope ordered, and leaves the rows a record-label callback labels, in the map and the search results alike, in the order the database returns them', function (): void {
        $this->seed(new Garden('Moss'), new Garden('Fern'));
        [$titled, $ordered, $labelled] = new RecordMappingHost()->mapping(
            Select::make('titled')->relationship('garden', 'name')->searchable(),
            Select::make('ordered')->relationship('garden', 'name', modifyQueryUsing: static fn (QueryBuilder $query): QueryBuilder => $query->orderBy('record.name', SortDirection::Descending))->searchable(),
            Select::make('labelled')->relationship('garden', 'name')->getOptionLabelFromRecordUsing(static fn (Garden $garden): string => "{$garden->name} garden")->searchable()->preload(),
        )->record(Seedling::class)->getComponents();

        expect(array_values($titled->getSearchResults('')))->toBe(['Fern', 'Moss'])
            ->and(array_filter($this->statementsRun(), static fn (string $sql): bool => str_contains($sql, 'ORDER BY')))->toHaveCount(1)
            ->and(array_values($ordered->getSearchResults('')))->toBe(['Moss', 'Fern']);

        $read = count($this->statementsRun());

        expect(array_values($labelled->getSearchResults('')))->toEqualCanonicalizing(['Moss garden', 'Fern garden'])
            ->and(array_values($labelled->getOptions()))->toEqualCanonicalizing(['Moss garden', 'Fern garden'])
            ->and(array_filter(array_slice($this->statementsRun(), $read), static fn (string $sql): bool => str_contains($sql, 'ORDER BY')))->toBe([]);
    });

    it('hands the scope the typed term as $search, and null outside a search', function (): void {
        $log = $this::getContainer()->get(HookLog::class);
        [$select] = new RecordMappingHost()->mapping(
            Select::make('garden')->relationship('garden', 'name', modifyQueryUsing: static function (QueryBuilder $query, ?string $search) use ($log): void {
                $log->record('search: ' . ($search ?? 'null'));
            })->searchable()->preload(),
        )->record(Seedling::class)->getComponents();

        $select->getSearchResults('fe');
        $select->getOptions();
        $select->getOptionLabelsFromRelationship(['1']);

        expect($log->hooks)->toBe(['search: fe', 'search: null', 'search: null']);
    });

    it('labels the selected record by the record-label callback or else the title attribute, and answers no label for a selection the scope hides', function (Closure $select, ?string $label): void {
        $this->seed($fern = new Garden('Fern'));
        $host = new RecordMappingHost();
        [$component] = $host->mapping($select())->record(Seedling::class)->getComponents();
        $host->data['garden'] = (string) $fern->id;

        expect($component->getOptionLabelFromRelationship())->toBe($label);
    })->with([
        'the title attribute' => [fn (): Select => Select::make('garden')->relationship('garden', 'name'), 'Fern'],
        'the record-label callback' => [fn (): Select => Select::make('garden')->relationship('garden', 'name')->getOptionLabelFromRecordUsing(static fn (Garden $garden): string => "{$garden->name} garden"), 'Fern garden'],
        'a scope that hides it' => [fn (): Select => Select::make('garden')->relationship('garden', 'name', modifyQueryUsing: static fn (QueryBuilder $query): QueryBuilder => $query->andWhere("record.name <> 'Fern'")), null],
    ]);

    it('labels only the scoped rows of a list in no particular order, drops a nested array or an object from it, and runs no statement when nothing scalar is left', function (): void {
        $this->seed($fern = new Garden('Fern'), $moss = new Garden('Moss'));
        [$select] = new RecordMappingHost()->mapping(
            Select::make('garden')->relationship('garden', 'name', modifyQueryUsing: static fn (QueryBuilder $query): QueryBuilder => $query->andWhere("record.name <> 'Moss'")),
        )->record(Seedling::class)->getComponents();

        expect($select->getOptionLabelsFromRelationship([(string) $fern->id, (string) $moss->id, ['nested'], new stdClass()]))->toBe([$fern->id => 'Fern'])
            ->and(array_filter($this->statementsRun(), static fn (string $sql): bool => str_contains($sql, 'ORDER BY')))->toBe([]);

        $this->seed();

        expect($select->getOptions())->toBe([$fern->id => 'Fern'])
            ->and(array_filter($this->statementsRun(), static fn (string $sql): bool => str_contains($sql, 'ORDER BY')))->toHaveCount(1);

        $this->seed();

        expect($select->getOptionLabelsFromRelationship([['nested'], new stdClass()]))->toBe([])
            ->and($this->statementsRun())->toBe([]);
    });
});

describe('search columns', function (): void {
    it('refuses by name a search column that is not a mapped field of the target, and searches a field of an embeddable', function (): void {
        $this->seed(new PropagatedCutting('Rose', 'Kew'), new PropagatedCutting('Bud', 'Wisley'));
        [$hop, $embedded] = new RecordMappingHost()->mapping(
            Select::make('hop')->relationship('parent', 'name')->searchable(['parent.name']),
            Select::make('embedded')->relationship('parent', 'name')->searchable(['origin.nursery']),
        )->record(PropagatedCutting::class)->getComponents();

        expect(fn (): array => $hop->getSearchResults('rose'))->toThrow(LogicException::class, 'The [hop] field searches relationship("parent") by [parent.name], which is not a mapped field of ' . PropagatedCutting::class . ". A search column must be a field the target entity maps (or an embeddable's, as address.city) — searching through an association or a virtual property is not supported.")
            ->and(array_values($embedded->getSearchResults('kew')))->toBe(['Rose']);
    });

    it('leaves the database order alone for a title attribute read through an association', function (): void {
        $fastnet = new Lighthouse();
        $fastnet->lamp = new Product('Zeta');

        $skerry = new Lighthouse();
        $skerry->lamp = new Product('Alpha');
        $this->seed($fastnet->lamp, $skerry->lamp, $fastnet, $skerry);
        [$select, $named] = new RecordMappingHost()->mapping(
            Select::make('lighthouse')->relationship('lighthouse', 'lamp.name'),
            Select::make('named')->relationship('lighthouse', 'name'),
        )->record(LighthouseLog::class)->getComponents();

        expect(array_values($select->getOptions()))->toEqualCanonicalizing(['Zeta', 'Alpha'])
            ->and(array_filter($this->statementsRun(), static fn (string $sql): bool => str_contains($sql, 'ORDER BY')))->toBe([]);

        $this->seed();

        expect($named->getOptions())->toHaveCount(2)
            ->and(array_filter($this->statementsRun(), static fn (string $sql): bool => str_contains($sql, 'ORDER BY')))->toHaveCount(1);
    });
});

describe('ignoreRecord', function (): void {
    it('leaves the bound record out of the option map and the search results while both label arms still name it, and excludes nothing by default', function (): void {
        $bud = new PropagatedCutting('Bud');
        $bud->parent = $bud;
        $this->seed($rose = new PropagatedCutting('Rose'), $bud);
        $bud = $this->entityManager()->find(PropagatedCutting::class, $bud->id);
        $host = new RecordMappingHost();
        [$ignoring, $default] = $host->mapping(
            Select::make('parent')->relationship('parent', 'name', ignoreRecord: true)->searchable()->preload(),
            Select::make('kept')->relationship('parent', 'name')->searchable()->preload(),
        )->record($bud)->getComponents();
        $host->data['parent'] = (string) $bud->id;

        expect($ignoring->getOptions())->toBe([$rose->id => 'Rose'])
            ->and($ignoring->getSearchResults(''))->toBe([$rose->id => 'Rose'])
            ->and($ignoring->getOptionLabelFromRelationship())->toBe('Bud')
            ->and($ignoring->getOptionLabelsFromRelationship([(string) $bud->id]))->toBe([$bud->id => 'Bud'])
            ->and($default->getOptions())->toBe([$bud->id => 'Bud', $rose->id => 'Rose']);
    });

    it('serves a clone bound to another record the option map of its own record', function (): void {
        $this->seed($rose = new PropagatedCutting('Rose'), $bud = new PropagatedCutting('Bud'));
        $rose = $this->entityManager()->find(PropagatedCutting::class, $rose->id);
        $bud = $this->entityManager()->find(PropagatedCutting::class, $bud->id);
        [$select] = new RecordMappingHost()->mapping(Select::make('parent')->relationship('parent', 'name', ignoreRecord: true))->record($rose)->getComponents();

        expect(array_values($select->getOptions()))->toBe(['Bud']);

        [$clone] = new RecordMappingHost()->mapping($select->getClone())->record($bud)->getComponents();

        expect(array_values($clone->getOptions()))->toBe(['Rose']);
    });

    it('excludes nothing on a create form, or for a record whose identifier is not complete yet', function (Closure $record, string $field, string $titleAttribute, Closure $targets, array $labels): void {
        $this->seed(...$targets());
        [$select] = new RecordMappingHost()->mapping(Select::make($field)->relationship($field, $titleAttribute, ignoreRecord: true))->record($record())->getComponents();

        expect(array_values($select->getOptions()))->toBe($labels);
    })->with([
        'a create form bound to the class' => [fn (): string => PropagatedCutting::class, 'parent', 'name', fn (): array => [new PropagatedCutting('Rose'), new PropagatedCutting('Bud')], ['Bud', 'Rose']],
        'a record whose generated id is pending' => [fn (): PropagatedCutting => new PropagatedCutting('Sprig'), 'parent', 'name', fn (): array => [new PropagatedCutting('Rose'), new PropagatedCutting('Bud')], ['Bud', 'Rose']],
        'a record whose assigned key is still blank' => [fn (): CaselessHandle => new CaselessHandle(''), 'alias', 'handle', fn (): array => [new CaselessHandle('north'), new CaselessHandle('south')], ['north', 'south']],
    ]);

    it('refuses by name a bound record of another class than the target', function (): void {
        [$select] = new RecordMappingHost()->mapping(Select::make('garden')->relationship('garden', 'name', ignoreRecord: true))->record(new Seedling('Sprout'))->getComponents();

        expect(fn (): array => $select->getOptions())->toThrow(LogicException::class, 'The [garden] field declares relationship("garden", ignoreRecord: true), which needs a self-referential association: the bound record is a ' . Seedling::class . ' while the options are ' . Garden::class . ', so its identifier names no row this query could exclude.');
    });

    it('validates an untouched selection naming the bound record, as a stored self-parent does, and refuses a key no row holds, searchable or not', function (bool $isSearchable): void {
        $bud = new PropagatedCutting('Bud');
        $bud->parent = $bud;
        $this->seed(new PropagatedCutting('Rose'), $bud);
        $bud = $this->entityManager()->find(PropagatedCutting::class, $bud->id);
        $host = $this->validatingRecordMappingHost();
        $schema = $host->mapping(Select::make('parent')->relationship('parent', 'name', ignoreRecord: true)->searchable($isSearchable))->record($bud);
        $schema->fill($bud);

        expect($host->data)->toBe(['parent' => (string) $bud->id])
            ->and($schema->getData()->parent)->toBe($bud);

        $host->data['parent'] = '999999';

        expect(fn (): object => $schema->getData())->toThrow(ValidationException::class);
    })->with([
        'a select listing every option' => [false],
        'a searchable select' => [true],
    ]);
});

describe('composite relationship targets', function (): void {
    it('reads the labels of a long list in one statement for a single identifier, and in one per 500 keys for an association plus a scalar', function (Closure $select, string $recordClass, Closure $targets, Closure $keys, int $statements): void {
        $entities = $targets();
        $this->seed(...$entities);
        [$component] = new RecordMappingHost()->mapping($select())->record($recordClass)->getComponents();

        expect($component->getOptionLabelsFromRelationship($keys($entities)))->toHaveCount(1)
            ->and($this->statementsRun())->toHaveCount($statements);
    })->with([
        'a single identifier' => [
            fn (): Select => Select::make('plants')->multiple()->relationship('plants', 'name'),
            Garden::class,
            fn (): array => [new Plant('Fern')],
            fn (array $entities): array => [(string) $entities[0]->id, ...array_map(static fn (int $index): string => (string) (1_000_000 + $index), range(1, 500))],
            1,
        ],
        'an association plus a scalar' => [
            fn (): Select => Select::make('allotment')->relationship('allotment', 'number'),
            Seedling::class,
            fn (): array => [new Allotment(new Garden('Plot'), 1)],
            fn (array $entities): array => ["{$entities[0]->garden->id}~1", ...array_map(static fn (int $index): string => "999999~{$index}", range(1, 500))],
            2,
        ],
    ]);

    it('resolves a selected composite key whose string half differs from the stored one only in case as the platform collation compares them, in one statement', function (string $field, Closure $berth): void {
        $berth = $berth();
        $this->seed($berth);
        $stored = ($field === 'mooring') ? $berth->mooring : $berth->getCleat();
        $host = new RecordMappingHost();
        [$select] = $host->mapping(Select::make($field)->relationship($field, 'label'))->record(Berth::class)->getComponents();
        $host->data[$field] = mb_strtolower($this::getContainer()->get(AssociationReader::class)->getRecordKey($stored));

        expect($select->getOptionLabelFromRelationship())->toBe(match (TestPlatform::current()) {
            TestPlatform::MySql => 'Moored',
            TestPlatform::Sqlite, TestPlatform::PostgreSql => null,
        })
            ->and($this->statementsRun())->toHaveCount(1);
    })->with([
        'two scalars' => ['mooring', function (): Berth {
            $mooring = new BerthMooring();
            [$mooring->pier, $mooring->slot, $mooring->label] = ['North', 'zh Hans', 'Moored'];
            $berth = new Berth();
            $berth->mooring = $mooring;

            return $berth;
        }],
        'an association plus a scalar' => ['cleat', function (): Berth {
            $cleat = new BerthCleat();
            [$cleat->slot, $cleat->label] = ['zh Hans', 'Moored'];
            $berth = new Berth();
            $berth->setCleat($cleat);

            return $berth;
        }],
    ]);
});
