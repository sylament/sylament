<?php

declare(strict_types=1);

use Doctrine\ORM\QueryBuilder;
use Sylament\Actions\Action;
use Sylament\Forms\Components\CheckboxList;
use Sylament\Forms\Validation\RequiredValue;
use Sylament\Schemas\Components\Group;
use Sylament\Tests\Fixtures\Entity\Atelier;
use Sylament\Tests\Fixtures\Entity\AtelierPlaque;
use Sylament\Tests\Fixtures\Entity\BenchPot;
use Sylament\Tests\Fixtures\Entity\Garden;
use Sylament\Tests\Fixtures\Entity\NurseryBench;
use Sylament\Tests\Fixtures\Entity\Plant;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Forms\CheckboxListForm;
use Sylament\Tests\Fixtures\Forms\Selectors;
use Sylament\Tests\Fixtures\Schemas\RecordMappingHost;
use Sylament\Tests\Fixtures\Schemas\StringBackedLetter;
use Sylament\Tests\Fixtures\Schemas\TaggingStateCast;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\Type;

describe('rendering', function (): void {
    it('renders a group named by the field label, one checkbox per option carrying its key, an id of its own and the field model but no name, and a description only where the option has one', function (): void {
        $render = $this->renderSchemaComponent(CheckboxList::make('tags')->options(['fern' => 'Fern', 'moss' => 'Moss'])->descriptions(['fern' => 'Feathery fronds']));
        $group = $render->filter('[role="group"]');

        expect($render->filter(sprintf('[id="%s"]', $group->attr('aria-labelledby')))->text())->toBe('Tags')
            ->and($group->filter('input[type="checkbox"]')->each(static fn (Crawler $input): array => [$input->attr('value'), $input->attr('id'), $input->attr('name'), $input->attr('data-model')]))->toBe([
                ['fern', 'schema.tags-fern', null, 'norender|data.tags'],
                ['moss', 'schema.tags-moss', null, 'norender|data.tags'],
            ])
            ->and($group->filter('label')->each(static fn (Crawler $option): array => [
                $option->filter(Selectors::CHECKBOX_LIST_OPTION_LABEL)->text(),
                $option->filter(Selectors::CHECKBOX_LIST_OPTION_DESCRIPTION)->count() === 1 ? $option->filter(Selectors::CHECKBOX_LIST_OPTION_DESCRIPTION)->text() : null,
            ]))->toBe([['Fern', 'Feathery fronds'], ['Moss', null]]);
    });

    it('checks each stored option, an int key matching the submitted string and a scalar the option it names, but nothing for a state a cast leaves a string', function (): void {
        $checked = static fn (Crawler $render, string $field): array => $render->filter("[aria-labelledby=\"form.{$field}-label\"] input[type=\"checkbox\"][checked]")->each(static fn (Crawler $input): ?string => $input->attr('value'));
        $tester = $this->createLiveComponent(CheckboxListForm::class);

        expect($checked($tester->render()->crawler(), 'tags'))->toBe([]);

        $render = $tester->fillForm(['tags' => ['moss', 'fern'], 'numbered' => ['2']])->render()->crawler();

        expect($checked($render, 'tags'))->toBe(['fern', 'moss'])
            ->and($checked($render, 'numbered'))->toBe(['2'])
            ->and($checked($tester->fillForm(['tags' => 'moss'])->render()->crawler(), 'tags'))->toBe(['moss'])
            ->and($checked($tester->fillForm(['tags' => 'mos'])->render()->crawler(), 'tags'))->toBe([]);

        expect($this->renderSchemaComponent(CheckboxList::make('tags')->options(['first' => 'First'])->stateCast(TaggingStateCast::First))->filter('input[type="checkbox"]')->each(static fn (Crawler $input): array => [$input->attr('value'), $input->attr('checked')]))->toBe([['first', null]]);
    });

    it('marks every option invalid once the field has an error', function (): void {
        $invalid = static fn (Crawler $render): array => $render->filter('[aria-labelledby="form.chosen-label"] input[type="checkbox"]')->each(static fn (Crawler $input): ?string => $input->attr('aria-invalid'));
        $tester = $this->createLiveComponent(CheckboxListForm::class);

        expect($invalid($tester->render()->crawler()))->toBe(['false', 'false'])
            ->and($invalid($tester->call('save')->render()->crawler()))->toBe(['true', 'true']);
    });

    it('carries the disabled and autofocus switches on its options', function (): void {
        $options = ['fern' => 'Fern', 'moss' => 'Moss'];
        $switches = fn (CheckboxList $list): array => $this->renderSchemaComponent($list)->filter('input[type="checkbox"]')->each(static fn (Crawler $input): array => [$input->attr('disabled') !== null, $input->attr('autofocus') !== null]);

        expect($switches(CheckboxList::make('tags')->options($options)))->toBe([[false, false], [false, false]])
            ->and($switches(CheckboxList::make('tags')->options($options)->disableOptionWhen(static fn (string $value): bool => $value === 'moss')))->toBe([[false, false], [true, false]])
            ->and($switches(CheckboxList::make('tags')->options($options)->disabled()))->toBe([[true, false], [true, false]])
            ->and($switches(CheckboxList::make('tags')->options($options)->autofocus()))->toBe([[false, true], [false, false]]);
    });

    it('lays its options out down the columns it is given', function (): void {
        expect($this->renderSchemaComponent(CheckboxList::make('tags')->options(['fern' => 'Fern'])->columns(3)))
            ->toContainElement(Selectors::CHECKBOX_LIST_OPTIONS . SupportSelectors::columnWiseColumns('lg', 3));
    });

    it('renders an empty options grid for an empty options map', function (): void {
        $grid = $this->renderSchemaComponent(CheckboxList::make('tags')->options([]))->filter('[role="group"] ' . Selectors::CHECKBOX_LIST_OPTIONS);

        expect($grid->count())->toBe(1)
            ->and($grid->children()->count())->toBe(0);
    });
});

describe('state', function (): void {
    it('rests at an empty list across a request, never null, and reads a selection back as its option keys', function (): void {
        $tester = $this->createLiveComponent(CheckboxListForm::class)->fillForm(['numbered' => ['2', '1']]);
        $form = $tester->component()->getSchema('form');

        expect($form->getComponent('tags')->getRawState())->toBe([])
            ->and($form->getComponent('numbered')->getState())->toBe([2, 1]);
    });

    it('reads an enum list as its cases, dropping a value no case backs', function (): void {
        $tester = $this->createLiveComponent(CheckboxListForm::class)->fillForm(['letters' => ['c', 'zz', 'a']]);

        expect($tester->component()->getSchema('form')->getComponent('letters')->getState())->toBe([StringBackedLetter::C, StringBackedLetter::A]);
    });
});

describe('validation', function (): void {
    it('accepts enabled options and an empty optional list, and refuses a list holding an option it does not offer or offers disabled', function (string $field, array $state, bool $isValid): void {
        $tester = $this->createLiveComponent(CheckboxListForm::class)->fillForm([$field => $state])->call('save');

        $isValid ? $tester->assertHasNoFormErrors([$field]) : $tester->assertHasFormErrors([$field => Choice::class]);
    })->with([
        'enabled options' => ['tags', ['fern', 'moss'], true],
        'an empty optional list' => ['tags', [], true],
        'an option it does not offer' => ['tags', ['fern', 'yew'], false],
        'a disabled option' => ['tags', ['ivy'], false],
        'a list no enum case backs' => ['letters', ['zz'], false],
    ]);

    it('fails a list handed a scalar by a type violation alone, ahead of the count and the check that it has options', function (string $field): void {
        $this->createLiveComponent(CheckboxListForm::class)
            ->fillForm([$field => 'fern'])
            ->call('save')
            ->assertHasFormErrors([$field => Type::class])
            ->assertHasNoFormErrors([$field => Choice::class])
            ->assertHasNoFormErrors([$field => Count::class]);
    })->with([
        'a list with options' => ['tags'],
        'a list with item bounds' => ['bounded'],
        'a list with nothing to enumerate' => ['unusable'],
    ]);

    it('checks an in() list instead of the options', function (string $state, bool $isValid): void {
        $tester = $this->createLiveComponent(CheckboxListForm::class)->fillForm(['restricted' => [$state]])->call('save');

        $isValid ? $tester->assertHasNoFormErrors(['restricted']) : $tester->assertHasFormErrors(['restricted' => Choice::class]);
    })->with([
        'a listed value no option offers' => ['yew', true],
        'an option the list leaves out' => ['moss', false],
    ]);

    it('refuses to validate a filled list with nothing to enumerate, naming its state path', function (): void {
        expect(fn () => $this->createLiveComponent(CheckboxListForm::class)->fillForm(['unusable' => ['fern']])->call('save'))
            ->toThrow(LogicException::class, 'Sylament failed to validate the [data.unusable] checkbox list because it has no options() configuration. Call options() so Sylament knows which values are valid.');
    });

    it('fails an empty required list by the required constraint alone and passes any selection, while minItems() still counts beside required()', function (): void {
        $this->createLiveComponent(CheckboxListForm::class)
            ->fillForm(['chosen' => [], 'chosenPair' => []])
            ->call('save')
            ->assertHasFormErrors(['chosen' => RequiredValue::class, 'chosenPair' => RequiredValue::class])
            ->assertHasNoFormErrors(['chosen' => Count::class])
            ->assertHasFormErrors(['chosenPair' => Count::class]);

        $this->createLiveComponent(CheckboxListForm::class)
            ->fillForm(['chosen' => ['moss'], 'chosenPair' => ['moss']])
            ->call('save')
            ->assertHasNoFormErrors(['chosen'])
            ->assertHasNoFormErrors(['chosenPair' => RequiredValue::class])
            ->assertHasFormErrors(['chosenPair' => Count::class]);
    });

    it('fails a selection outside the item bounds by the count, an empty one included, and passes one inside them', function (array $state, ?string $message): void {
        $tester = $this->createLiveComponent(CheckboxListForm::class)->fillForm(['bounded' => $state])->call('save');

        $message === null
            ? $tester->assertHasNoFormErrors(['bounded'])
            : $tester->assertHasFormErrors(['bounded' => Count::class])->assertHasFormErrors(['bounded' => $message]);
    })->with([
        'none' => [[], 'This collection should contain 2 elements or more.'],
        'too few' => [['a'], 'This collection should contain 2 elements or more.'],
        'within the bounds' => [['a', 'b'], null],
        'too many' => [['a', 'b', 'c', 'd'], 'This collection should contain 3 elements or less.'],
    ]);
});

describe('bulk toggle', function (): void {
    it('renders Select all and a hidden Deselect all, each in a span whose click the controller handles, the links themselves mounting no action', function (): void {
        $render = $this->renderSchemaComponent(CheckboxList::make('tags')->options(['fern' => 'Fern'])->bulkToggleable());
        $span = static fn (string $target): Crawler => $render->filter("[data-sylament--forms--checkbox-list-target=\"{$target}\"]");

        expect($render->filter('[role="group"]')->attr('data-controller'))->toBe('sylament--forms--checkbox-list')
            ->and([$span('selectAllWrap')->text(), $span('selectAllWrap')->attr('hidden'), $span('selectAllWrap')->attr('data-action')])->toBe(['Select all', null, 'click->sylament--forms--checkbox-list#toggleAll'])
            ->and([$span('deselectAllWrap')->text(), $span('deselectAllWrap')->attr('hidden'), $span('deselectAllWrap')->attr('data-action')])->toBe(['Deselect all', '', 'click->sylament--forms--checkbox-list#toggleAll'])
            ->and($render->filter('[data-sylament--forms--checkbox-list-target$="Wrap"] ' . SupportSelectors::SMALL_LINK)->each(static fn (Crawler $link): ?string => $link->attr('data-action')))->toBe([null, null]);
    });

    it('renders no bulk toggle and no controller for a list that is not bulk toggleable, is disabled or has no options', function (Closure $list): void {
        $group = $this->renderSchemaComponent($list())->filter('[role="group"]');

        expect($group->count())->toBe(1)
            ->and($group->attr('data-controller'))->toBeNull()
            ->and($group->filter('[data-sylament--forms--checkbox-list-target$="Wrap"]')->count())->toBe(0);
    })->with([
        'not bulk toggleable' => [fn (): CheckboxList => CheckboxList::make('tags')->options(['fern' => 'Fern'])],
        'disabled' => [fn (): CheckboxList => CheckboxList::make('tags')->options(['fern' => 'Fern'])->bulkToggleable()->disabled()],
        'no options' => [fn (): CheckboxList => CheckboxList::make('tags')->options([])->bulkToggleable()],
    ]);

    it('relabels either action through its modifier', function (string $modifier, string $target): void {
        $list = CheckboxList::make('tags')->options(['fern' => 'Fern'])->bulkToggleable()->{$modifier}(static fn (Action $action): Action => $action->label('Every plant'));

        expect($this->renderSchemaComponent($list)->filter("[data-sylament--forms--checkbox-list-target=\"{$target}\"]")->text())->toBe('Every plant');
    })->with([
        'select all' => ['selectAllAction', 'selectAllWrap'],
        'deselect all' => ['deselectAllAction', 'deselectAllWrap'],
    ]);
});

describe('search', function (): void {
    it('renders a search box named by its prompt behind an inline icon prefix, the controller at no debounce with every option its target, and a hidden no-results status', function (): void {
        $render = $this->renderSchemaComponent(CheckboxList::make('tags')->options(['fern' => 'Fern', 'moss' => 'Moss'])->searchable());
        $group = $render->filter('[role="group"]');
        $search = $group->filter('input[type="search"]');
        $status = $group->filter('[role="status"]');

        expect([$group->attr('data-controller'), $group->attr('data-sylament--forms--checkbox-list-debounce-value')])->toBe(['sylament--forms--checkbox-list', '0'])
            ->and([$search->attr('placeholder'), $search->attr('aria-label'), $search->attr('data-sylament--forms--checkbox-list-target')])->toBe(['Start typing to search...', 'Start typing to search...', 'search'])
            ->and($group->filter(SupportSelectors::INLINE_PREFIX . ' svg')->text())->toBe('magnifying-glass-20-solid')
            ->and($group->filter('[data-sylament--forms--checkbox-list-target="option"]')->count())->toBe(2)
            ->and([$status->text(), $status->attr('hidden'), $status->attr('data-sylament--forms--checkbox-list-target')])->toBe(['No options match your search.', '', 'noResults']);
    });

    it('hands the controller the debounce searchDebounce() sets, and renders no search for a disabled list', function (): void {
        expect($this->renderSchemaComponent(CheckboxList::make('tags')->options(['fern' => 'Fern'])->searchable()->searchDebounce(300))->filter('[role="group"]')->attr('data-sylament--forms--checkbox-list-debounce-value'))->toBe('300');

        $disabled = $this->renderSchemaComponent(CheckboxList::make('tags')->options(['fern' => 'Fern'])->searchable()->disabled());

        expect($disabled->filter('[role="group"] input[type="checkbox"]')->count())->toBe(1)
            ->and($disabled->filter('input[type="search"]')->count())->toBe(0)
            ->and($disabled->filter('[role="status"]')->count())->toBe(0);
    });
});

describe('relationship', function (): void {
    it('labels and describes each option off one query of its records, a label answering nothing as an empty label and a blank description as none, and describes an option map built before the description closure was declared', function (): void {
        $this->seed($fern = new Plant('Fern'), $moss = new Plant('Moss'));
        $list = CheckboxList::make('plants')
            ->relationship('plants', 'name')
            ->getOptionLabelFromRecordUsing(static fn (Plant $record): ?string => $record->name === 'Moss' ? null : mb_strtoupper($record->name))
            ->getOptionDescriptionFromRecordUsing(static fn (Plant $record): string => $record->name === 'Fern' ? 'Feathery fronds' : '');
        $options = static fn (Crawler $render): array => $render->filter('[role="group"] label')->each(static fn (Crawler $option): array => [
            $option->filter('input')->attr('value'),
            $option->filter(Selectors::CHECKBOX_LIST_OPTION_LABEL)->text(),
            $option->filter(Selectors::CHECKBOX_LIST_OPTION_DESCRIPTION)->count() === 1 ? $option->filter(Selectors::CHECKBOX_LIST_OPTION_DESCRIPTION)->text() : null,
        ]);

        $this->seed();
        $render = $this->renderSchemaComponent(Group::make([$list])->record(Garden::class));

        expect($options($render))->toEqualCanonicalizing([[(string) $fern->id, 'FERN', 'Feathery fronds'], [(string) $moss->id, '', null]])
            ->and($this->statementsRun())->toHaveCount(1);

        $late = CheckboxList::make('plants')->relationship('plants', 'name')->getOptionLabelFromRecordUsing(static fn (Plant $record): string => $record->name);
        $this->renderSchemaComponent(Group::make([$late])->record(Garden::class));
        $late->getOptionDescriptionFromRecordUsing(static fn (Plant $record): string => "{$record->name} described");

        expect($options($this->renderSchemaComponent(Group::make([$late])->record(Garden::class))))->toEqualCanonicalizing([[(string) $fern->id, 'Fern', 'Fern described'], [(string) $moss->id, 'Moss', 'Moss described']]);
    });

    it('describes each option from its record while the title attribute labels it, off a query for the descriptions and one for the labels', function (): void {
        $this->seed($fern = new Plant('Fern'));
        $list = CheckboxList::make('plants')->relationship('plants', 'name')->getOptionDescriptionFromRecordUsing(static fn (Plant $record): string => "{$record->name} described");

        $this->seed();
        $option = $this->renderSchemaComponent(Group::make([$list])->record(Garden::class))->filter('[role="group"] label');

        expect([$option->filter('input')->attr('value'), $option->filter(Selectors::CHECKBOX_LIST_OPTION_LABEL)->text(), $option->filter(Selectors::CHECKBOX_LIST_OPTION_DESCRIPTION)->text()])->toBe([(string) $fern->id, 'Fern', 'Fern described'])
            ->and($this->statementsRun())->toHaveCount(2);
    });

    it('keeps its record-built options for the next read, and queries again on every read under a query scope', function (): void {
        $this->seed($fern = new Plant('Fern'));
        $label = static fn (Plant $record): string => mb_strtoupper($record->name);
        [$list, $scoped] = new RecordMappingHost()->mapping(
            CheckboxList::make('plants')->relationship('plants', 'name')->getOptionLabelFromRecordUsing($label),
            CheckboxList::make('scoped')->relationship('plants', 'name', modifyQueryUsing: static fn (QueryBuilder $query): QueryBuilder => $query)->getOptionLabelFromRecordUsing($label),
        )->record(Garden::class)->getComponents();

        expect([$list->getOptions(), $list->getOptions(), $scoped->getOptions(), $scoped->getOptions()])->toBe(array_fill(0, 4, [$fern->id => 'FERN']))
            ->and($this->statementsRun())->toHaveCount(3);
    });

    it('lists its record-built options in the order modifyQueryUsing gives the query', function (): void {
        $this->seed(new Plant('Moss'), new Plant('Fern'), new Plant('Ivy'));
        $list = CheckboxList::make('plants')
            ->relationship('plants', 'name', modifyQueryUsing: static fn (QueryBuilder $query): QueryBuilder => $query->orderBy('record.name'))
            ->getOptionLabelFromRecordUsing(static fn (Plant $record): string => $record->name);

        expect($this->renderSchemaComponent(Group::make([$list])->record(Garden::class))->filter(Selectors::CHECKBOX_LIST_OPTION_LABEL)->each(static fn (Crawler $label): string => $label->text()))->toBe(['Fern', 'Ivy', 'Moss']);
    });

    it('keys the record-built labels and descriptions of a composite-keyed target by its record key, so each description sits under its option', function (string $recordClass, string $field, Closure $targets, Closure $key, Closure $describe): void {
        $entities = $targets();
        $this->seed(...$entities);
        $list = CheckboxList::make($field)->relationship($field)->getOptionLabelFromRecordUsing($describe)->getOptionDescriptionFromRecordUsing(static fn (object $record): string => 'described ' . $describe($record));
        $option = $this->renderSchemaComponent(Group::make([$list])->record($recordClass))->filter('[role="group"] label');

        expect([$option->filter('input')->attr('value'), $option->filter(Selectors::CHECKBOX_LIST_OPTION_LABEL)->text(), $option->filter(Selectors::CHECKBOX_LIST_OPTION_DESCRIPTION)->text()])->toBe([$key($entities), 'Basil', 'described Basil']);
    })->with([
        'two scalars' => [NurseryBench::class, 'pots', fn (): array => [new BenchPot('A', '1', 'Basil')], fn (): string => 'A~1', static fn (BenchPot $record): string => $record->plant],
        'an association plus a scalar' => [Atelier::class, 'plaques', function (): array {
            $atelier = new Atelier('North');

            return [$atelier, new AtelierPlaque($atelier, 'en', 'Basil')];
        }, fn (array $entities): string => "{$entities[0]->id}~en", static fn (AtelierPlaque $record): string => $record->inscription],
    ]);

    it('labels itself by its relationship, an explicit label over it, and by its own name without one', function (Closure $list, string $label): void {
        expect($this->renderSchemaComponent(Group::make([$list()])->record(Garden::class))->filter('[id="schema.plantIds-label"]')->text())->toBe($label);
    })->with([
        'a relationship' => [fn (): CheckboxList => CheckboxList::make('plantIds')->relationship('plants', 'name'), 'Plants'],
        'an explicit label' => [fn (): CheckboxList => CheckboxList::make('plantIds')->relationship('plants', 'name')->label('Greenery'), 'Greenery'],
        'no relationship' => [fn (): CheckboxList => CheckboxList::make('plantIds')->options([]), 'Plant ids'],
    ]);

    it('refuses a to-one association, since a list always speaks a list', function (): void {
        $garden = new Garden('North');
        $schema = new RecordMappingHost()->mapping(CheckboxList::make('supplier')->relationship('supplier', 'name'))->record($garden);

        expect(fn (): mixed => $schema->fill($garden))->toThrow(LogicException::class, 'The [supplier] field declares relationship("supplier"), but that association holds a single ' . Product::class . ' rather than a collection, so the list of selections the field always speaks could not map it. Point relationship() at a to-many association.');
    });
});
