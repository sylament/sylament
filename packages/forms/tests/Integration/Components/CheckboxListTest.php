<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components;

use LogicException;
use Sylament\Actions\Action;
use Sylament\Forms\Components\CheckboxList;
use Sylament\Forms\Tests\Fixtures\Components\Concerns\OptionPriority;
use Sylament\Forms\Tests\Fixtures\Components\Concerns\OptionStatus;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\Casts\EnumArrayStateCast;
use Sylament\Schemas\State\Casts\OptionsArrayStateCast;
use Sylament\Support\Enums\Size;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use function Sylament\Forms\Tests\renderField;
use function Sylament\Forms\Tests\renderToHtml;

/**
 * Wire a checkbox list into a `data`-bag-bound parent schema, mirroring
 * `renderField()`'s container — the same rig `wireRadio()` and
 * `wireToggleButtons()` build one file over, and for the same reason: the shared
 * `wireField()` helper binds a schema with no state path, so a field's `state()`
 * would write straight onto the host object.
 */
function wireCheckboxList(CheckboxList $field): CheckboxList
{
    Schema::make(new FieldRenderHost())->statePath('data')->components([$field])->getComponents();

    return $field;
}

/**
 * Render a checkbox list against a host whose `data` bag is pre-filled, so
 * `getState()` resolves to a real selection and the template's server-side
 * `checked` has something to answer.
 *
 * @param array<string, mixed> $data
 */
function renderCheckboxListWithState(CheckboxList $field, array $data): string
{
    $host = new FieldRenderHost();
    $host->data = $data;

    return renderToHtml(Schema::make($host)->statePath('data')->components([$field]));
}

/**
 * Wire a checkbox list into a keyed, validator-backed schema and run the host's
 * real validation over it, so the field reports the messages a failed submit
 * leaves in the host error bag — the only way to reach the `aria-invalid="true"`
 * arm.
 */
function validatedCheckboxListSchema(CheckboxList $field): Schema
{
    $host = new FieldRenderHost();
    $host->setSchemaStateValidator(test()->getContainer()->get(ValidatorInterface::class));

    $schema = Schema::make($host)->statePath('data')->components([$field]);
    $host->cacheSchema('schema', $schema);
    $schema->hydrateState();

    $host->validateSchema($schema, shouldThrow: false);

    return $schema;
}

it('renders one checkbox per option inside a labelled group', function (): void {
    $crawler = new Crawler(renderField(
        CheckboxList::make('topics')->label('Topics')->options(['php' => 'PHP', 'js' => 'JS']),
    ));

    $group = $crawler->filter('div.sy-fo-checkbox-list');
    expect($group->count())->toBe(1)
        ->and($group->attr('role'))->toBe('group')
        ->and($group->attr('aria-labelledby'))->toBe('topics-label')
        ->and($crawler->filter('div.sy-fo-field-label')->attr('id'))->toBe('topics-label');

    $options = $group->filter('div.sy-fo-checkbox-list-options div.sy-fo-checkbox-list-option-ctn');
    expect($options->count())->toBe(2);

    $inputs = $options->filter('label.sy-fo-checkbox-list-option input.sy-fo-checkbox');
    expect($inputs->count())->toBe(2)
        ->and($inputs->eq(0)->attr('type'))->toBe('checkbox')
        ->and($inputs->eq(0)->attr('value'))->toBe('php')
        ->and($inputs->eq(1)->attr('value'))->toBe('js')
        ->and($inputs->eq(0)->attr('id'))->toBe('topics-php')
        ->and($inputs->eq(1)->attr('id'))->toBe('topics-js')
        ->and($inputs->eq(0)->attr('name'))->toBeNull()
        ->and($inputs->eq(0)->attr('data-model'))->toBe('norender|data.topics')
        ->and($inputs->eq(1)->attr('data-model'))->toBe('norender|data.topics');

    $labels = $options->filter('span.sy-fo-checkbox-list-option-label');
    expect($labels->eq(0)->text())->toBe('PHP')
        ->and($labels->eq(1)->text())->toBe('JS');
});

it('checks every option the array state holds and no other', function (): void {
    $inputs = new Crawler(renderCheckboxListWithState(
        CheckboxList::make('topics')->options(['php' => 'PHP', 'js' => 'JS', 'css' => 'CSS']),
        ['topics' => ['php', 'css']],
    ))->filter('input.sy-fo-checkbox');

    expect($inputs->count())->toBe(3)
        ->and($inputs->eq(0)->attr('checked'))->not->toBeNull()
        ->and($inputs->eq(1)->attr('checked'))->toBeNull()
        ->and($inputs->eq(2)->attr('checked'))->not->toBeNull();
});

/*
 * The submitted vocabulary is the string a browser sends, and an option map may be
 * int-keyed — so the comparison has to be loose in the same way `ToggleButtons`'
 * multiple arm is, or a stored `'1'` would leave the `1`-keyed option unchecked.
 */
it('checks an int-keyed option against a string state', function (): void {
    $inputs = new Crawler(renderCheckboxListWithState(
        CheckboxList::make('sizes')->options([1 => 'One', 2 => 'Two']),
        ['sizes' => ['1']],
    ))->filter('input.sy-fo-checkbox');

    expect($inputs->eq(0)->attr('checked'))->not->toBeNull()
        ->and($inputs->eq(1)->attr('checked'))->toBeNull();
});

it('checks nothing when the state is unset', function (): void {
    $inputs = new Crawler(renderField(CheckboxList::make('topics')->options([1 => 'One', 0 => 'Zero'])))
        ->filter('input.sy-fo-checkbox');

    expect($inputs->count())->toBe(2)
        ->and($inputs->eq(0)->attr('checked'))->toBeNull()
        ->and($inputs->eq(1)->attr('checked'))->toBeNull();
});

it('renders one checkbox per enum case, labelled and described by the enum', function (): void {
    $crawler = new Crawler(renderField(CheckboxList::make('status')->options(OptionStatus::class)));

    $inputs = $crawler->filter('input.sy-fo-checkbox');
    expect($inputs->count())->toBe(2)
        ->and($inputs->eq(0)->attr('value'))->toBe('draft')
        ->and($inputs->eq(1)->attr('value'))->toBe('published')
        ->and($crawler->filter('span.sy-fo-checkbox-list-option-label')->eq(0)->text())->toBe('Draft');

    $described = new Crawler(renderField(CheckboxList::make('priority')->options(OptionPriority::class)));
    $options = $described->filter('div.sy-fo-checkbox-list-option-ctn');

    expect($options->eq(0)->filter('p.sy-fo-checkbox-list-option-description')->text())
        ->toBe('Handled when there is time')
        ->and($options->eq(1)->filter('p.sy-fo-checkbox-list-option-description')->count())->toBe(0);
});

it('renders explicit descriptions keyed by option value', function (): void {
    $options = new Crawler(renderField(
        CheckboxList::make('topics')
            ->options(['php' => 'PHP', 'js' => 'JS'])
            ->descriptions(['js' => 'Runs in the browser']),
    ))->filter('div.sy-fo-checkbox-list-option-ctn');

    expect($options->eq(0)->filter('p.sy-fo-checkbox-list-option-description')->count())->toBe(0)
        ->and($options->eq(1)->filter('p.sy-fo-checkbox-list-option-description')->text())->toBe('Runs in the browser');
});

/*
 * `allowHtml()` opens exactly one hole: the option label. A description carrying
 * the same markup stays escaped, which is the escaping map vendor ships and the
 * assertion that would catch a template widening the raw site by one element.
 */
it('renders a label raw under allowHtml while its description stays escaped', function (): void {
    $html = renderField(
        CheckboxList::make('topics')
            ->options(['php' => '<b>PHP</b>'])
            ->descriptions(['php' => '<i>Server side</i>'])
            ->allowHtml(),
    );

    expect($html)->toContain('<b>PHP</b>')
        ->and($html)->toContain('&lt;i&gt;Server side&lt;/i&gt;')
        ->and($html)->not->toContain('<i>Server side</i>');

    $escaped = renderField(CheckboxList::make('topics')->options(['php' => '<b>PHP</b>']));

    expect($escaped)->toContain('&lt;b&gt;PHP&lt;/b&gt;')
        ->and($escaped)->not->toContain('<b>PHP</b>');
});

it('disables only the option the callback names, and every option when the field is', function (): void {
    $inputs = new Crawler(renderField(
        CheckboxList::make('topics')
            ->options(['php' => 'PHP', 'js' => 'JS'])
            ->disableOptionWhen(fn (string|int $value): bool => $value === 'js'),
    ))->filter('input.sy-fo-checkbox');

    expect($inputs->eq(0)->attr('disabled'))->toBeNull()
        ->and($inputs->eq(1)->attr('disabled'))->not->toBeNull();

    $allDisabled = new Crawler(renderField(
        CheckboxList::make('topics')->options(['php' => 'PHP', 'js' => 'JS'])->disabled(),
    ))->filter('input.sy-fo-checkbox');

    expect($allDisabled->eq(0)->attr('disabled'))->not->toBeNull()
        ->and($allDisabled->eq(1)->attr('disabled'))->not->toBeNull();
});

it('lays the options out as a single-column grid, with no inline escape', function (): void {
    $options = new Crawler(renderField(
        CheckboxList::make('topics')->options(['php' => 'PHP', 'js' => 'JS']),
    ))->filter('div.sy-fo-checkbox-list-options');

    expect($options->attr('class'))->toBe('sy-fo-checkbox-list-options sy-grid sy-grid-direction-col sy-grid-cols')
        ->and($options->attr('style'))->toBe('--cols-default: 1');

    $columns = new Crawler(renderField(
        CheckboxList::make('topics')->options(['php' => 'PHP'])->columns(2),
    ))->filter('div.sy-fo-checkbox-list-options');

    expect($columns->attr('class'))->toBe('sy-fo-checkbox-list-options sy-grid sy-grid-direction-col lg:sy-grid-cols sy-grid-cols')
        ->and($columns->attr('style'))->toBe('--cols-lg: 2; --cols-default: 1');
});

it('marks every input invalid when the field has errors, and valid otherwise', function (): void {
    $valid = new Crawler(renderField(
        CheckboxList::make('topics')->options(['php' => 'PHP', 'js' => 'JS']),
    ))->filter('input.sy-fo-checkbox');

    expect($valid->eq(0)->attr('aria-invalid'))->toBe('false')
        ->and($valid->eq(1)->attr('aria-invalid'))->toBe('false');

    $crawler = new Crawler(renderToHtml(validatedCheckboxListSchema(
        CheckboxList::make('topics')->options(['php' => 'PHP', 'js' => 'JS'])->required(),
    )));
    $invalid = $crawler->filter('input.sy-fo-checkbox');

    expect($invalid->count())->toBe(2)
        ->and($invalid->eq(0)->attr('aria-invalid'))->toBe('true')
        ->and($invalid->eq(1)->attr('aria-invalid'))->toBe('true')
        ->and($crawler->filter('.sy-fo-field-error-message')->count())->toBe(1);
});

it('merges extra input attributes onto every checkbox and lets them win', function (): void {
    $crawler = new Crawler(renderField(
        CheckboxList::make('topics')
            ->options(['php' => 'PHP', 'js' => 'JS'])
            ->disabled()
            ->extraInputAttributes([
                'data-extra' => 'yes',
                'disabled' => false,
            ]),
    ));

    $inputs = $crawler->filter('input.sy-fo-checkbox');

    expect($inputs->count())->toBe(2)
        ->and($inputs->eq(0)->attr('data-extra'))->toBe('yes')
        ->and($inputs->eq(0)->attr('disabled'))->toBeNull()
        ->and($inputs->eq(1)->attr('disabled'))->toBeNull()
        ->and($crawler->filter('div.sy-fo-checkbox-list[data-extra]')->count())->toBe(0);
});

it('renders no controller, search box or actions by default', function (): void {
    $crawler = new Crawler(renderField(CheckboxList::make('topics')->options(['php' => 'PHP'])));

    expect($crawler->filter('div.sy-fo-checkbox-list')->attr('data-controller'))->toBeNull()
        ->and($crawler->filter('input[type="search"]')->count())->toBe(0)
        ->and($crawler->filter('div.sy-fo-checkbox-list-actions')->count())->toBe(0)
        ->and($crawler->filter('[data-sylament--forms--checkbox-list-target]')->count())->toBe(0);
});

it('renders the search box, its targets and the hidden no-results node when searchable', function (): void {
    $crawler = new Crawler(renderField(
        CheckboxList::make('topics')->options(['php' => 'PHP', 'js' => 'JS'])->searchable(),
    ));

    $root = $crawler->filter('div.sy-fo-checkbox-list');
    expect($root->attr('data-controller'))->toBe('sylament--forms--checkbox-list')
        ->and($root->attr('data-sylament--forms--checkbox-list-debounce-value'))->toBe('0');

    $search = $crawler->filter('input[type="search"]');
    expect($search->count())->toBe(1)
        ->and($search->attr('class'))->toBe('sy-fo-input')
        ->and($search->attr('name'))->toBeNull()
        ->and($search->attr('data-model'))->toBeNull()
        ->and($search->attr('placeholder'))->toBe('Start typing to search...')
        ->and($search->attr('aria-label'))->toBe('Start typing to search...')
        ->and($search->attr('data-sylament--forms--checkbox-list-target'))->toBe('search');

    $noResults = $crawler->filter('[data-sylament--forms--checkbox-list-target="noResults"]');
    expect($noResults->count())->toBe(1)
        ->and($noResults->attr('hidden'))->not->toBeNull()
        ->and($noResults->attr('role'))->toBe('status')
        ->and($noResults->attr('aria-live'))->toBe('polite')
        ->and($noResults->text())->toBe('No options match your search.')
        ->and($crawler->filter('[data-sylament--forms--checkbox-list-target="option"]')->count())->toBe(2);
});

it('takes a custom search prompt and no-results message', function (): void {
    $crawler = new Crawler(renderField(
        CheckboxList::make('topics')
            ->options(['php' => 'PHP'])
            ->searchable()
            ->searchPrompt('Filter topics')
            ->noSearchResultsMessage('Nothing here'),
    ));

    expect($crawler->filter('input[type="search"]')->attr('placeholder'))->toBe('Filter topics')
        ->and($crawler->filter('[data-sylament--forms--checkbox-list-target="noResults"]')->text())->toBe('Nothing here');
});

it('forces the search debounce to zero, overriding the trait default', function (): void {
    expect(CheckboxList::make('topics')->getSearchDebounce())->toBe(0)
        ->and(CheckboxList::make('topics')->searchDebounce(250)->getSearchDebounce())->toBe(250);
});

it('hides the search box and the actions on a disabled field', function (): void {
    $crawler = new Crawler(renderField(
        CheckboxList::make('topics')->options(['php' => 'PHP'])->searchable()->bulkToggleable()->disabled(),
    ));

    expect($crawler->filter('input[type="search"]')->count())->toBe(0)
        ->and($crawler->filter('div.sy-fo-checkbox-list-actions')->count())->toBe(0)
        ->and($crawler->filter('div.sy-fo-checkbox-list')->attr('data-controller'))->toBeNull();
});

/*
 * The bulk toggle is a client-only affordance: the two spans carry the Stimulus
 * click and the rendered links carry none of their own. `data-action="live#action"`
 * plus its parameters is what a round-trip wiring emits, so their absence is the
 * assertion — the dispatch attributes live on a different branch of the action
 * partial and would pass here whatever the wiring did.
 */
it('renders both bulk-toggle actions inert inside targeted spans', function (): void {
    $crawler = new Crawler(renderField(
        CheckboxList::make('topics')->options(['php' => 'PHP', 'js' => 'JS'])->bulkToggleable(),
    ));

    $selectAll = $crawler->filter('[data-sylament--forms--checkbox-list-target="selectAllWrap"]');
    $deselectAll = $crawler->filter('[data-sylament--forms--checkbox-list-target="deselectAllWrap"]');

    expect($selectAll->count())->toBe(1)
        ->and($selectAll->nodeName())->toBe('span')
        ->and($selectAll->attr('data-action'))->toBe('click->sylament--forms--checkbox-list#toggleAll')
        ->and($selectAll->attr('hidden'))->toBeNull()
        ->and($selectAll->filter('.sy-link')->text())->toBe('Select all')
        ->and($deselectAll->count())->toBe(1)
        ->and($deselectAll->attr('data-action'))->toBe('click->sylament--forms--checkbox-list#toggleAll')
        ->and($deselectAll->attr('hidden'))->not->toBeNull()
        ->and($deselectAll->filter('.sy-link')->text())->toBe('Deselect all');

    $links = $crawler->filter('div.sy-fo-checkbox-list-actions .sy-link');

    expect($links->count())->toBe(2)
        ->and($links->eq(0)->attr('data-action'))->toBeNull()
        ->and($links->eq(1)->attr('data-action'))->toBeNull()
        ->and($crawler->filter('div.sy-fo-checkbox-list-actions [data-live-action-param]')->count())->toBe(0)
        ->and($crawler->filter('div.sy-fo-checkbox-list-actions [data-live-name-param]')->count())->toBe(0);
});

it('drops the bulk-toggle row when there are no options', function (): void {
    $crawler = new Crawler(renderField(CheckboxList::make('topics')->options([])->bulkToggleable()));

    expect($crawler->filter('div.sy-fo-checkbox-list-actions')->count())->toBe(0)
        ->and($crawler->filter('div.sy-fo-checkbox-list')->attr('data-controller'))->toBeNull();
});

it('builds the two bulk-toggle actions as small links, and lets a callback modify them', function (): void {
    $field = wireCheckboxList(CheckboxList::make('topics')->options(['php' => 'PHP'])->bulkToggleable());

    $selectAll = $field->getSelectAllAction();

    expect($field->getSelectAllActionName())->toBe('selectAll')
        ->and($field->getDeselectAllActionName())->toBe('deselectAll')
        ->and($selectAll->getName())->toBe('selectAll')
        ->and($selectAll->getLabel())->toBe('Select all')
        ->and($selectAll->isLink())->toBeTrue()
        ->and($selectAll->getSize())->toBe(Size::Small)
        ->and($selectAll->isLiveComponentClickHandlerEnabled())->toBeFalse()
        ->and($field->getDeselectAllAction()->getLabel())->toBe('Deselect all')
        ->and($field->getAction('selectAll'))->toBeInstanceOf(Action::class)
        ->and($field->getAction('deselectAll'))->toBeInstanceOf(Action::class);

    $modified = wireCheckboxList(
        CheckboxList::make('topics')
            ->options(['php' => 'PHP'])
            ->bulkToggleable()
            ->selectAllAction(fn (Action $action): Action => $action->label('All of them'))
            ->deselectAllAction(fn (Action $action): Action => $action->label('None of them')),
    );

    expect($modified->getSelectAllAction()->getLabel())->toBe('All of them')
        ->and($modified->getDeselectAllAction()->getLabel())->toBe('None of them');
});

it('is not bulk toggleable, searchable or html-allowing by default', function (): void {
    expect(CheckboxList::make('topics')->isBulkToggleable())->toBeFalse()
        ->and(CheckboxList::make('topics')->bulkToggleable()->isBulkToggleable())->toBeTrue()
        ->and(CheckboxList::make('topics')->bulkToggleable(fn (): bool => true)->isBulkToggleable())->toBeTrue()
        ->and(CheckboxList::make('topics')->isSearchable())->toBeFalse()
        ->and(CheckboxList::make('topics')->searchable()->isSearchable())->toBeTrue()
        ->and(CheckboxList::make('topics')->isHtmlAllowed())->toBeFalse()
        ->and(CheckboxList::make('topics')->allowHtml()->isHtmlAllowed())->toBeTrue()
        ->and(CheckboxList::make('topics')->isMultiple())->toBeTrue();
});

it('casts state through the array casts, in both the plain and enum arms', function (): void {
    expect(CheckboxList::make('topics')->getDefaultStateCasts()[0])->toBeInstanceOf(OptionsArrayStateCast::class)
        ->and(CheckboxList::make('status')->enum(OptionStatus::class)->getEnumDefaultStateCast())
        ->toBeInstanceOf(EnumArrayStateCast::class)
        ->and(CheckboxList::make('topics')->getEnumDefaultStateCast())->toBeNull();

    $field = wireCheckboxList(CheckboxList::make('sizes')->options([1 => 'One', 2 => 'Two']));
    $field->state(['1']);

    expect($field->getStateCasts())->toHaveCount(1)
        ->and($field->getState())->toBe([1]);
});

it('rests an unset list at an array rather than null', function (): void {
    $field = wireCheckboxList(CheckboxList::make('topics')->options(['php' => 'PHP']));

    expect($field->getState())->toBe([]);
});

it('builds a multi-value Choice over the enabled options', function (): void {
    $field = wireCheckboxList(CheckboxList::make('topics')->options(['php' => 'PHP', 'js' => 'JS']));
    $field->state(['php']);

    $choice = array_values(array_filter(
        $field->getValidationConstraints(),
        fn (object $c): bool => $c instanceof Choice,
    ));

    expect($choice)->toHaveCount(1)
        ->and($choice[0]->choices)->toBe(['php', 'js'])
        ->and($choice[0]->multiple)->toBeTrue()
        ->and($choice[0]->strict)->toBeTrue();

    $validator = $this::getContainer()->get(ValidatorInterface::class);

    expect($validator->validate($field->getRawState(), $field->getValidationConstraints()))->toHaveCount(0)
        ->and($validator->validate(['php', 'tampered'], $field->getValidationConstraints()))->toHaveCount(1);
});

it('rejects a disabled option server-side', function (): void {
    $field = wireCheckboxList(
        CheckboxList::make('topics')
            ->options(['php' => 'PHP', 'js' => 'JS'])
            ->disableOptionWhen(fn (string|int $value): bool => $value === 'js'),
    );
    $field->state(['js']);

    expect($this::getContainer()->get(ValidatorInterface::class)->validate($field->getRawState(), $field->getValidationConstraints()))
        ->toHaveCount(1);
});

it('fails a list whose raw state is not an array', function (): void {
    $field = wireCheckboxList(CheckboxList::make('topics')->options(['php' => 'PHP']));
    $field->rawState('php');

    $constraints = $field->getValidationConstraints();
    $type = array_values(array_filter($constraints, fn (object $c): bool => $c instanceof Type));

    expect($type)->toHaveCount(1)
        ->and($constraints)->toHaveCount(1)
        ->and($this::getContainer()->get(ValidatorInterface::class)->validate('php', $constraints))->toHaveCount(1);
});

/*
 * The enum arm of the tamper case, driven end to end rather than by handing the
 * validator a value the field never held: the tampered element *is* the raw state,
 * so the builder sees exactly what a payload leaves behind. `EnumArrayStateCast`
 * drops every element matching no case, so a guard reading the cast state would
 * read an empty selection and skip the `Choice` that rejects it.
 */
it('rejects a tampered element the enum cast drops', function (): void {
    $host = new FieldRenderHost();
    $host->setSchemaStateValidator($this::getContainer()->get(ValidatorInterface::class));

    $field = CheckboxList::make('topics')->options(OptionStatus::class)->required();
    $schema = Schema::make($host)->statePath('data')->components([$field]);
    $host->cacheSchema('schema', $schema);

    $host->data['topics'] = ['deleted-by-hand'];
    $schema->getComponents();

    expect($field->getRawState())->toBe(['deleted-by-hand'])
        ->and($field->getState())->toBe([]);

    $host->validateSchema($schema, shouldThrow: false);

    expect($host->getErrorsForSchemaStatePath('data.topics'))
        ->toBe(['One or more of the given values is invalid.']);
});

it('builds no Choice constraint for an empty selection', function (): void {
    expect(wireCheckboxList(CheckboxList::make('topics')->options(['php' => 'PHP']))->getValidationConstraints())->toBe([]);
});

it('throws when validating a list with no options', function (): void {
    $field = wireCheckboxList(CheckboxList::make('topics'));
    $field->state(['php']);

    $field->getValidationConstraints();
})->throws(LogicException::class);

it('bounds the selection with a Count constraint from minItems and maxItems', function (): void {
    $field = wireCheckboxList(
        CheckboxList::make('topics')->options(['php' => 'PHP', 'js' => 'JS', 'css' => 'CSS'])->minItems(2)->maxItems(2),
    );
    $field->state(['php']);

    $count = array_values(array_filter($field->getValidationConstraints(), fn (object $c): bool => $c instanceof Count));

    expect($count)->toHaveCount(1)
        ->and($count[0]->min)->toBe(2)
        ->and($count[0]->max)->toBe(2);

    $validator = $this::getContainer()->get(ValidatorInterface::class);

    expect($validator->validate(['php'], $field->getValidationConstraints()))->toHaveCount(1)
        ->and($validator->validate(['php', 'js'], $field->getValidationConstraints()))->toHaveCount(0)
        ->and($validator->validate(['php', 'js', 'css'], $field->getValidationConstraints()))->toHaveCount(1);
});

it('bounds an empty selection too, where the Choice short-circuit would not', function (): void {
    $field = wireCheckboxList(CheckboxList::make('topics')->options(['php' => 'PHP'])->minItems(1));

    $constraints = $field->getValidationConstraints();

    expect(array_filter($constraints, fn (object $c): bool => $c instanceof Count))->toHaveCount(1)
        ->and($this::getContainer()->get(ValidatorInterface::class)->validate([], $constraints))->toHaveCount(1);
});

it('builds no Count constraint when neither bound is declared', function (): void {
    $field = wireCheckboxList(CheckboxList::make('topics')->options(['php' => 'PHP']));
    $field->state(['php']);

    expect(array_filter($field->getValidationConstraints(), fn (object $c): bool => $c instanceof Count))->toBe([]);
});

it('fails a required list with nothing selected', function (): void {
    $field = wireCheckboxList(CheckboxList::make('topics')->options(['php' => 'PHP'])->required());

    $validator = $this::getContainer()->get(ValidatorInterface::class);

    expect($validator->validate([], $field->getValidationConstraints()))->toHaveCount(1);

    $field->state(['php']);

    expect($validator->validate($field->getRawState(), $field->getValidationConstraints()))->toHaveCount(0);
});
