<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components;

use LogicException;
use Sylament\Forms\Components\Select;
use Sylament\Forms\Tests\Fixtures\Components\Concerns\OptionStatus;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Forms\Tests\Fixtures\Records\Article;
use Sylament\Forms\Tests\Fixtures\Records\EnumArticle;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\Casts\EnumArrayStateCast;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\UX\LiveComponent\LiveResponder;
use Twig\Markup;

use function Sylament\Forms\Tests\renderField;
use function Sylament\Forms\Tests\renderToHtml;

/**
 * Wire a select into a `data`-bag-bound parent schema, mirroring `renderField()`'s
 * container. The shared `wireField()` helper binds a schema with no state path, so
 * a field's `state()` would write straight onto the host object; a select needs the
 * `data` prefix because its state round-trips through the host's writable bag.
 */
function wireSelect(Select $field): Select
{
    Schema::make(new FieldRenderHost())->statePath('data')->components([$field])->getComponents();

    return $field;
}

it('renders a native select with its options and placeholder', function (): void {
    $crawler = new Crawler(renderField(
        Select::make('status')->label('Status')->options(['draft' => 'Draft', 'published' => 'Published']),
    ));

    $select = $crawler->filter('select.sy-fo-select');
    expect($select->count())->toBe(1)
        ->and($select->attr('name'))->toBe('status')
        ->and($select->attr('id'))->toBe('status')
        ->and($select->attr('data-model'))->toBe('norender|data.status');

    $options = $select->filter('option');
    expect($options->count())->toBe(3)
        ->and($options->eq(0)->attr('value'))->toBe('')
        ->and($options->eq(0)->text())->toBe('Select an option')
        ->and($options->eq(1)->attr('value'))->toBe('draft')
        ->and($options->eq(1)->text())->toBe('Draft');
});

it('omits the placeholder option when not selectable', function (): void {
    $crawler = new Crawler(renderField(
        Select::make('status')->options(['draft' => 'Draft'])->selectablePlaceholder(false),
    ));

    expect($crawler->filter('select.sy-fo-select option')->count())->toBe(1);
});

it('renders option groups as optgroups', function (): void {
    $crawler = new Crawler(renderField(
        Select::make('status')->options([
            'Live' => ['published' => 'Published'],
            'Not live' => ['draft' => 'Draft'],
        ]),
    ));

    $groups = $crawler->filter('select.sy-fo-select optgroup');
    expect($groups->count())->toBe(2)
        ->and($groups->eq(0)->attr('label'))->toBe('Live')
        ->and($groups->eq(0)->filter('option')->attr('value'))->toBe('published');
});

it('marks a disabled option inside a group as disabled', function (): void {
    $crawler = new Crawler(renderField(
        Select::make('status')
            ->options([
                'Live' => ['published' => 'Published'],
                'Not live' => ['draft' => 'Draft'],
            ])
            ->disableOptionWhen(fn (string|int $value): bool => $value === 'published'),
    ));

    $groups = $crawler->filter('select.sy-fo-select optgroup');
    expect($groups->eq(0)->filter('option')->attr('disabled'))->not->toBeNull()
        ->and($groups->eq(1)->filter('option')->attr('disabled'))->toBeNull();
});

it('marks a disabled option as disabled', function (): void {
    $crawler = new Crawler(renderField(
        Select::make('status')
            ->options(['draft' => 'Draft', 'published' => 'Published'])
            ->disableOptionWhen(fn (string|int $value): bool => $value === 'published'),
    ));

    $options = $crawler->filter('select.sy-fo-select option');
    expect($options->eq(1)->attr('disabled'))->toBeNull()
        ->and($options->eq(2)->attr('disabled'))->not->toBeNull();
});

it('renders required, disabled and aria-invalid', function (): void {
    $crawler = new Crawler(renderField(
        Select::make('status')->options(['draft' => 'Draft'])->required()->disabled(),
    ));

    $select = $crawler->filter('select.sy-fo-select');
    expect($select->attr('required'))->not->toBeNull()
        ->and($select->attr('disabled'))->not->toBeNull()
        ->and($select->attr('aria-invalid'))->toBe('false');
});

it('leaves the placeholder text out when disabled', function (): void {
    $crawler = new Crawler(renderField(
        Select::make('status')->options(['draft' => 'Draft'])->disabled(),
    ));

    expect($crawler->filter('select.sy-fo-select option')->eq(0)->text())->toBe('');
});

it('casts option state through OptionStateCast', function (): void {
    $field = wireSelect(Select::make('status')->options([1 => 'One', 2 => 'Two']));
    $field->state('1');

    expect($field->getState())->toBe(1);
});

it('builds a Choice constraint over the enabled options', function (): void {
    $field = wireSelect(Select::make('status')->options(['draft' => 'Draft', 'published' => 'Published']));
    $field->state('draft');

    $constraints = $field->getValidationConstraints();
    $choice = array_values(array_filter($constraints, fn (object $c): bool => $c instanceof Choice));

    expect($choice)->toHaveCount(1)
        ->and($choice[0]->choices)->toBe(['draft', 'published'])
        ->and($choice[0]->strict)->toBeTrue();
});

it('excludes disabled options from the Choice constraint', function (): void {
    $field = wireSelect(
        Select::make('status')
            ->options(['draft' => 'Draft', 'published' => 'Published'])
            ->disableOptionWhen(fn (string|int $value): bool => $value === 'published'),
    );
    $field->state('draft');

    $choice = array_values(array_filter(
        $field->getValidationConstraints(),
        fn (object $c): bool => $c instanceof Choice,
    ));

    expect($choice[0]->choices)->toBe(['draft']);
});

/*
 * The four cases below run the real server-side guard, not just its shape: a host
 * validates the *raw* state a payload wrote (`HasSchemas::validateSchema()` reads
 * `getPropertyAccessor()->get($host, $path)`), so these validate `getRawState()`
 * with the container's validator against the field's own constraint list. Drop the
 * Choice constraint and the tampered value passes.
 */

it('rejects a tampered value server-side', function (): void {
    $field = wireSelect(Select::make('status')->options(['draft' => 'Draft', 'published' => 'Published']));
    $field->state('draft');

    $validator = $this::getContainer()->get(ValidatorInterface::class);
    $constraints = $field->getValidationConstraints();

    expect($validator->validate($field->getRawState(), $constraints))->toHaveCount(0)
        ->and($validator->validate('tampered', $constraints))->toHaveCount(1);
});

it('rejects a disabled option server-side', function (): void {
    $field = wireSelect(
        Select::make('status')
            ->options(['draft' => 'Draft', 'published' => 'Published'])
            ->disableOptionWhen(fn (string|int $value): bool => $value === 'published'),
    );
    $field->state('published');

    $validator = $this::getContainer()->get(ValidatorInterface::class);

    expect($validator->validate($field->getRawState(), $field->getValidationConstraints()))->toHaveCount(1);
});

it('accepts the raw state of an int-keyed option', function (): void {
    $field = wireSelect(Select::make('status')->options([1 => 'One', 2 => 'Two']));
    $field->state('1');

    $validator = $this::getContainer()->get(ValidatorInterface::class);
    $constraints = $field->getValidationConstraints();

    expect($validator->validate($field->getRawState(), $constraints))->toHaveCount(0)
        ->and($validator->validate('3', $constraints))->toHaveCount(1);
});

/*
 * A browser writes the option's `value` attribute — always a string — straight into
 * the host's state (`live_controller`'s `getValueFromElement()` on a single select),
 * and nothing re-normalizes it before validation: `hydrateState()` runs from
 * `Schema::fill()` only. So a `boolean()` select, whose cast stores `int 1`, must
 * accept `'1'` as well or it rejects its own value on every round-trip.
 */
it('accepts both stored forms of a boolean() option server-side', function (): void {
    $field = wireSelect(Select::make('featured')->boolean());
    $field->rawState('1');

    $validator = $this::getContainer()->get(ValidatorInterface::class);
    $constraints = $field->getValidationConstraints();

    $choice = array_values(array_filter($constraints, fn (object $c): bool => $c instanceof Choice));

    expect($choice[0]->choices)->toBe([1, '1', 0, '0'])
        ->and($validator->validate('1', $constraints))->toHaveCount(0)
        ->and($validator->validate('0', $constraints))->toHaveCount(0)
        ->and($validator->validate(1, $constraints))->toHaveCount(0)
        ->and($validator->validate(0, $constraints))->toHaveCount(0)
        ->and($validator->validate('2', $constraints))->toHaveCount(1);
});

it('builds no Choice constraint for blank state', function (): void {
    $field = wireSelect(Select::make('status')->options(['draft' => 'Draft']));

    expect($field->getValidationConstraints())->toBe([]);
});

it('builds no Choice constraint for the empty string a placeholder submits, so the presence gate finds nothing to skip', function (): void {
    $host = new FieldRenderHost();
    $host->setSchemaStateValidator($this::getContainer()->get(ValidatorInterface::class));

    $field = Select::make('status')->options(['draft' => 'Draft']);
    $schema = Schema::make($host)->statePath('data')->components([$field]);
    $host->cacheSchema('schema', $schema);

    $host->data['status'] = '';
    $schema->getComponents();

    expect($field->getRawState())->toBe('')
        ->and($field->getValidationConstraints())->toBe([]);

    $host->validateSchema($schema, shouldThrow: false);

    expect($host->getErrorsForSchemaStatePath('data.status'))->toBe([]);
});

it('throws when validating a select with no options', function (): void {
    $field = wireSelect(Select::make('status'));
    $field->state('draft');

    $field->getValidationConstraints();
})->throws(LogicException::class);

it('configures boolean() options, placeholder and int-backed cast', function (): void {
    $field = wireSelect(Select::make('featured')->boolean());
    $field->state(true);

    expect($field->getOptions())->toBe([1 => 'Yes', 0 => 'No'])
        ->and($field->getPlaceholder())->toBe('-')
        ->and($field->getRawState())->toBe(1);
});

it('resolves the label of the selected option', function (): void {
    $field = wireSelect(Select::make('status')->options(['draft' => 'Draft', 'published' => 'Published']));
    $field->state('published');

    expect($field->getOptionLabel())->toBe('Published');
});

it('resolves the label of a boolean option', function (): void {
    $field = wireSelect(Select::make('featured')->boolean());

    $field->state(true);

    expect($field->getOptionLabel())->toBe('Yes');

    $field->state(false);
    expect($field->getOptionLabel())->toBe('No');
});

it('resolves a grouped option label', function (): void {
    $field = wireSelect(Select::make('status')->options(['Live' => ['published' => 'Published']]));
    $field->state('published');

    expect($field->getOptionLabel())->toBe('Published');
});

it('still derives required for a boolean() select on a non-nullable bool column', function (): void {
    // The regression pin for the case the Checkbox/Toggle override must not swallow:
    // boolean()'s cast is nullable, so the placeholder dehydrates null — and null is
    // exactly what a non-nullable column cannot take.
    $field = Select::make('featured')->boolean();
    $crawler = new Crawler(renderToHtml(
        Schema::make(new FieldRenderHost())->statePath('data')->record(Article::class)->components([$field]),
    ));

    expect($field->isRequired())->toBeTrue()
        ->and($crawler->filter('select.sy-fo-select')->attr('required'))->not->toBeNull();
});

it('renders one option per enum case, labelled by the enum', function (): void {
    $crawler = new Crawler(renderField(Select::make('status')->options(OptionStatus::class)));

    $options = $crawler->filter('select.sy-fo-select option');

    expect($options->count())->toBe(3)
        ->and($options->eq(1)->attr('value'))->toBe('draft')
        ->and($options->eq(1)->text())->toBe('Draft')
        ->and($options->eq(2)->attr('value'))->toBe('published')
        ->and($options->eq(2)->text())->toBe('Published');
});

it('marks the option matching an enum-valued state as selected', function (): void {
    $host = new FieldRenderHost();
    $host->data['status'] = OptionStatus::Published;

    $crawler = new Crawler(renderToHtml(
        Schema::make($host)->statePath('data')->components([Select::make('status')->options(OptionStatus::class)]),
    ));

    $options = $crawler->filter('select.sy-fo-select option');

    expect($options->eq(1)->attr('selected'))->toBeNull()
        ->and($options->eq(2)->attr('selected'))->not->toBeNull();
});

it('round-trips an enum through an enum-typed record property', function (): void {
    $record = new EnumArticle();
    $record->status = OptionStatus::Draft;

    $host = new FieldRenderHost();
    $host->setSchemaStateValidator($this::getContainer()->get(ValidatorInterface::class));

    $select = Select::make('status')->options(OptionStatus::class);
    $schema = Schema::make($host)->statePath('data')->record($record)->components([$select]);

    $host->cacheSchema('schema', $schema);
    $schema->getComponents();
    $schema->fill($record);

    expect($host->data['status'])->toBe('draft')
        ->and($select->getState())->toBe(OptionStatus::Draft);

    $host->data['status'] = 'published';

    expect($schema->getData())->toBe($record)
        ->and($record->status)->toBe(OptionStatus::Published);
});

it('round-trips an enum list through a multiple select', function (): void {
    $record = new EnumArticle();
    $record->tags = [OptionStatus::Draft];

    $host = new FieldRenderHost();
    $host->setSchemaStateValidator($this::getContainer()->get(ValidatorInterface::class));

    $select = Select::make('tags')->multiple()->options(OptionStatus::class);
    $schema = Schema::make($host)->statePath('data')->record($record)->components([$select]);

    $host->cacheSchema('schema', $schema);
    $schema->getComponents();

    expect($select->getStateCasts())->toHaveCount(1)
        ->and($select->getStateCasts()[0])->toBeInstanceOf(EnumArrayStateCast::class);

    $schema->fill($record);

    expect($host->data['tags'])->toBe(['draft']);

    $host->data['tags'] = ['draft', 'published'];

    expect($schema->getData())->toBe($record)
        ->and($record->tags)->toBe([OptionStatus::Draft, OptionStatus::Published]);
});

it('validates an enum-only select instead of throwing for missing options', function (): void {
    $field = wireSelect(Select::make('status')->enum(OptionStatus::class));
    $field->state(OptionStatus::Draft);

    $validator = $this::getContainer()->get(ValidatorInterface::class);
    $constraints = $field->getValidationConstraints();
    $choice = array_values(array_filter($constraints, fn (object $c): bool => $c instanceof Choice));

    expect($choice)->toHaveCount(1)
        ->and($choice[0]->choices)->toBe(['draft', 'published'])
        ->and($validator->validate($field->getRawState(), $constraints))->toHaveCount(0)
        ->and($validator->validate('tampered', $constraints))->toHaveCount(1);
});

/*
 * The enum arm of the tamper case, driven end to end rather than by handing the
 * validator a value the field never held: the tampered string *is* the raw state,
 * so the builder sees exactly what a payload leaves behind. `EnumStateCast` fails
 * closed to null for a value matching no case, so a guard reading the cast state
 * would read "nothing selected" and skip the `Choice` that rejects it.
 */
it('rejects a tampered value the enum cast nulls out', function (): void {
    $host = new FieldRenderHost();
    $host->setSchemaStateValidator($this::getContainer()->get(ValidatorInterface::class));

    $field = Select::make('status')->options(OptionStatus::class)->required();
    $schema = Schema::make($host)->statePath('data')->components([$field]);
    $host->cacheSchema('schema', $schema);

    $host->data['status'] = 'deleted-by-hand';
    $schema->getComponents();

    expect($field->getRawState())->toBe('deleted-by-hand')
        ->and($field->getState())->toBeNull();

    $host->validateSchema($schema, shouldThrow: false);

    expect($host->getErrorsForSchemaStatePath('data.status'))
        ->toBe(['The value you selected is not a valid choice.']);
});

it('rejects a tampered element of a multiple enum select', function (): void {
    $host = new FieldRenderHost();
    $host->setSchemaStateValidator($this::getContainer()->get(ValidatorInterface::class));

    $field = Select::make('tags')->multiple()->options(OptionStatus::class);
    $schema = Schema::make($host)->statePath('data')->components([$field]);
    $host->cacheSchema('schema', $schema);

    $host->data['tags'] = ['deleted-by-hand'];
    $schema->getComponents();

    expect($field->getState())->toBe([]);

    $host->validateSchema($schema, shouldThrow: false);

    expect($host->getErrorsForSchemaStatePath('data.tags'))
        ->toBe(['One or more of the given values is invalid.']);
});

it('merges extra input attributes onto the select and lets them win', function (): void {
    $crawler = new Crawler(renderField(
        Select::make('status')
            ->options(['draft' => 'Draft'])
            ->disabled()
            ->extraInputAttributes([
                'data-extra' => 'yes',
                'disabled' => false,
            ]),
    ));

    $select = $crawler->filter('select.sy-fo-select');

    expect($select->attr('data-extra'))->toBe('yes')
        ->and($select->attr('disabled'))->toBeNull()
        ->and($crawler->filter('option[data-extra]')->count())->toBe(0);
});

/*
 * The searchable surface — every member Filament's JS control reads off the field,
 * exercised on the PHP side; the JS branch's rendered contract has cases of its
 * own further down.
 */

it('is not searchable by default and takes the literal, closure and null arms', function (): void {
    expect(Select::make('status')->isSearchable())->toBeFalse()
        ->and(Select::make('status')->searchable()->isSearchable())->toBeTrue()
        ->and(Select::make('status')->searchable(false)->isSearchable())->toBeFalse()
        ->and(Select::make('status')->searchable(fn (): bool => true)->isSearchable())->toBeTrue()
        ->and(Select::make('status')->searchable(null)->isSearchable())->toBeFalse();
});

it('reads searchable(array) as searchable over the named columns', function (): void {
    $field = Select::make('status')->searchable(['name', 'email']);

    expect($field->isSearchable())->toBeTrue()
        ->and($field->getSearchColumns())->toBe(['name', 'email']);
});

it('is searchable by default when multiple, unless told otherwise', function (): void {
    expect(Select::make('tags')->multiple()->isSearchable())->toBeTrue()
        ->and(Select::make('tags')->multiple()->searchable(false)->isSearchable())->toBeFalse()
        ->and(Select::make('tags')->multiple()->searchable(null)->isSearchable())->toBeTrue();
});

it('limits options to fifty by default', function (): void {
    expect(Select::make('status')->getOptionsLimit())->toBe(50)
        ->and(Select::make('status')->optionsLimit(10)->getOptionsLimit())->toBe(10)
        ->and(Select::make('status')->optionsLimit(fn (): int => 5)->getOptionsLimit())->toBe(5);
});

it('carries the position, label-wrap and case-insensitivity switches', function (): void {
    expect(Select::make('status')->getPosition())->toBeNull()
        ->and(Select::make('status')->position('top')->getPosition())->toBe('top')
        ->and(Select::make('status')->canOptionLabelsWrap())->toBeTrue()
        ->and(Select::make('status')->wrapOptionLabels(false)->canOptionLabelsWrap())->toBeFalse()
        ->and(Select::make('status')->isSearchForcedCaseInsensitive())->toBeNull()
        ->and(Select::make('status')->forceSearchCaseInsensitive()->isSearchForcedCaseInsensitive())->toBeTrue()
        ->and(Select::make('status')->forceSearchCaseInsensitive(false)->isSearchForcedCaseInsensitive())->toBeFalse();
});

it('substitutes the count into the max items message', function (): void {
    expect(Select::make('tags')->multiple()->maxItems(2)->getMaxItemsMessage())->toBe('Only 2 can be selected.')
        ->and(Select::make('tags')->multiple()->maxItems(2)->maxItemsMessage('Two, tops')->getMaxItemsMessage())->toBe('Two, tops');
});

it('transforms flat and grouped options into the JS option shape', function (): void {
    $field = wireSelect(
        Select::make('status')
            ->options([
                1 => 'One',
                'Live' => ['published' => new Markup('<b>Published</b>', 'UTF-8')],
                'draft' => 'Draft',
            ])
            ->disableOptionWhen(fn (string|int $value): bool => $value === 'draft'),
    );

    expect($field->getOptionsForJs())->toBe([
        ['label' => 'One', 'value' => '1', 'isDisabled' => false],
        ['label' => 'Live', 'options' => [
            ['label' => '<b>Published</b>', 'value' => 'published', 'isDisabled' => false],
        ]],
        ['label' => 'Draft', 'value' => 'draft', 'isDisabled' => true],
    ]);
});

it('lets transformOptionsForJsUsing replace the JS option shape', function (): void {
    $field = wireSelect(
        Select::make('status')
            ->options(['draft' => 'Draft'])
            ->transformOptionsForJsUsing(fn (array $options): array => array_keys($options)),
    );

    expect($field->getOptionsForJs())->toBe(['draft'])
        ->and(wireSelect(Select::make('status'))->getOptionsForJs())->toBe([]);
});

it('hands the search term to getSearchResultsUsing under all three names', function (): void {
    $field = wireSelect(
        Select::make('status')->getSearchResultsUsing(
            fn (string $query, string $search, string $searchQuery): array => [$query => "{$search}/{$searchQuery}"],
        ),
    );

    expect($field->getSearchResults('ab'))->toBe(['ab' => 'ab/ab'])
        ->and($field->getSearchResultsForJs('ab'))->toBe([['label' => 'ab/ab', 'value' => 'ab', 'isDisabled' => false]])
        ->and(wireSelect(Select::make('status'))->getSearchResults('ab'))->toBe([]);
});

it('resolves the option label off the map, falling back to the state only when asked', function (): void {
    $field = wireSelect(Select::make('status')->options(['draft' => 'Draft']));
    $field->state('gone');

    expect($field->getOptionLabel())->toBe('gone')
        ->and($field->getOptionLabel(withDefault: false))->toBeNull();

    $field->state('draft');

    expect($field->getOptionLabel(withDefault: false))->toBe('Draft');
});

it('resolves the option label through getOptionLabelUsing, handing it the state lazily', function (): void {
    $reads = 0;

    $field = wireSelect(
        Select::make('status')->getOptionLabelUsing(function (mixed $value) use (&$reads): ?string {
            $reads++;

            return $value === 'draft' ? 'Draft' : null;
        }),
    );
    $field->state('draft');

    expect($field->getOptionLabel())->toBe('Draft')
        ->and($reads)->toBe(1);

    $field->state('gone');

    expect($field->getOptionLabel(withDefault: false))->toBeNull()
        ->and($field->getOptionLabel())->toBe('gone');

    $untouched = wireSelect(Select::make('status')->options(['draft' => 'Draft'])->getOptionLabelUsing(fn (): string => 'Closure'));
    $untouched->state('draft');

    expect($untouched->getOptionLabel())->toBe('Closure');
});

it('resolves the option labels of a multiple selection off the map or through getOptionLabelsUsing', function (): void {
    $field = wireSelect(Select::make('tags')->multiple()->options([
        'draft' => 'Draft',
        'Live' => ['published' => 'Published'],
    ]));
    $field->state(['draft', 'published', 'gone']);

    expect($field->getOptionLabels())->toBe(['draft' => 'Draft', 'published' => 'Published', 'gone' => 'gone'])
        ->and($field->getOptionLabels(withDefaults: false))->toBe(['draft' => 'Draft', 'published' => 'Published'])
        ->and($field->getOptionLabelsForJs())->toBe([
            ['label' => 'Draft', 'value' => 'draft', 'isDisabled' => false],
            ['label' => 'Published', 'value' => 'published', 'isDisabled' => false],
            ['label' => 'gone', 'value' => 'gone', 'isDisabled' => false],
        ]);

    $closure = wireSelect(
        Select::make('tags')->multiple()->getOptionLabelsUsing(
            fn (array $values): array => array_combine($values, array_map(fn (string $value): ?string => $value === 'gone' ? null : mb_strtoupper($value), $values)),
        ),
    );
    $closure->state(['draft', 'gone']);

    expect($closure->getOptionLabels())->toBe(['draft' => 'DRAFT', 'gone' => 'gone'])
        ->and($closure->getOptionLabels(withDefaults: false))->toBe(['draft' => 'DRAFT']);
});

it('answers the dynamic-options, dynamic-search-results and initial-no-options predicates as Filament does', function (): void {
    $static = Select::make('status')->options(['a' => 'A']);
    $closure = Select::make('status')->options(fn (): array => ['a' => 'A']);
    $searched = Select::make('status')->getSearchResultsUsing(fn (): array => []);
    $preloaded = Select::make('status')->preload()->options(['a' => 'A']);
    $disabling = Select::make('status')->options(['a' => 'A'])->disableOptionWhen(fn (): bool => false);
    $relationship = Select::make('author')->relationship(titleAttribute: 'name');
    $searchableRelationship = Select::make('author')->relationship(titleAttribute: 'name')->searchable();
    $preloadedRelationship = Select::make('author')->relationship(titleAttribute: 'name')->searchable()->preload();
    $columnlessRelationship = Select::make('author')->relationship()->searchable();

    expect($static->hasDynamicOptions())->toBeFalse()
        ->and($closure->hasDynamicOptions())->toBeTrue()
        ->and($preloaded->hasDynamicOptions())->toBeTrue()
        ->and($disabling->hasDynamicOptions())->toBeTrue()
        ->and($relationship->hasDynamicOptions())->toBeTrue()
        ->and($searchableRelationship->hasDynamicOptions())->toBeFalse()
        ->and($preloadedRelationship->hasDynamicOptions())->toBeTrue()
        ->and($static->dynamicOptions()->hasDynamicOptions())->toBeTrue()
        ->and($closure->dynamicOptions(false)->hasDynamicOptions())->toBeFalse()
        ->and($static->hasDynamicSearchResults())->toBeFalse()
        ->and($searched->hasDynamicSearchResults())->toBeTrue()
        ->and($searchableRelationship->hasDynamicSearchResults())->toBeTrue()
        ->and($columnlessRelationship->hasDynamicSearchResults())->toBeFalse()
        ->and($static->hasInitialNoOptionsMessage())->toBeTrue()
        ->and($searched->hasInitialNoOptionsMessage())->toBeFalse()
        ->and($preloaded->hasInitialNoOptionsMessage())->toBeTrue()
        ->and($relationship->hasInitialNoOptionsMessage())->toBeTrue()
        ->and($searchableRelationship->hasInitialNoOptionsMessage())->toBeFalse()
        ->and($preloadedRelationship->hasInitialNoOptionsMessage())->toBeTrue();
});

it('resolves the selected record through getSelectedRecordUsing and memoizes it', function (): void {
    $record = new Article();
    $reads = 0;

    $field = wireSelect(Select::make('status')->getSelectedRecordUsing(function (mixed $state) use ($record, &$reads): ?object {
        $reads++;

        return $state === 'a' ? $record : null;
    }));

    expect($field->getSelectedRecord())->toBeNull()
        ->and($reads)->toBe(0);

    $field->state('a');

    expect($field->getSelectedRecord())->toBe($record)
        ->and($field->getSelectedRecord())->toBe($record)
        ->and($reads)->toBe(1)
        ->and(wireSelect(Select::make('status'))->getSelectedRecord())->toBeNull();
});

it('is native only when nothing forces the JS control', function (): void {
    $isNative = fn (Select $field): bool => wireSelect($field)->getExtraTemplateData()['isNative'];

    expect($isNative(Select::make('status')->options(['a' => 'A'])))->toBeTrue()
        ->and($isNative(Select::make('status')->options(['a' => 'A'])->native(false)))->toBeFalse()
        ->and($isNative(Select::make('status')->options(['a' => 'A'])->searchable()))->toBeFalse()
        ->and($isNative(Select::make('status')->options(['a' => 'A'])->multiple()))->toBeFalse()
        ->and($isNative(Select::make('status')->options(['a' => 'A'])->multiple()->searchable(false)))->toBeFalse()
        ->and($isNative(Select::make('status')->options(['a' => 'A'])->allowHtml()))->toBeFalse()
        ->and($isNative(Select::make('status')->options(['a' => 'A'])->native(true)))->toBeTrue();
});

it('exposes the option map, its JS shape and the selected options to the template in one read', function (): void {
    $reads = 0;

    $field = wireSelect(
        Select::make('status')
            ->searchable()
            ->options(function () use (&$reads): array {
                $reads++;

                return ['draft' => 'Draft', 'published' => 'Published'];
            }),
    );
    $field->state('published');

    $data = $field->getExtraTemplateData();

    expect($reads)->toBe(1)
        ->and($data['options'])->toBe(['draft' => 'Draft', 'published' => 'Published'])
        ->and($data['optionsForJs'])->toBe([
            ['label' => 'Draft', 'value' => 'draft', 'isDisabled' => false],
            ['label' => 'Published', 'value' => 'published', 'isDisabled' => false],
        ])
        ->and($data['selectedOptions'])->toBe([['value' => 'published', 'label' => 'Published']])
        ->and($data['isNative'])->toBeFalse();

    $multiple = wireSelect(Select::make('tags')->multiple()->options(['a' => 'A', 'b' => 'B']));
    $multiple->state(['b', 'gone']);

    expect($multiple->getExtraTemplateData()['selectedOptions'])->toBe([
        ['value' => 'b', 'label' => 'B'],
        ['value' => 'gone', 'label' => 'gone'],
    ]);

    $blank = wireSelect(Select::make('status')->searchable()->options(['a' => 'A']));

    expect($blank->getExtraTemplateData()['selectedOptions'])->toBe([]);
});

it('dispatches the refresh-selected-option-label event on its host', function (): void {
    $host = new FieldRenderHost();
    $responder = new LiveResponder();
    $host->setLiveResponder($responder);

    $field = Select::make('status')->options(['a' => 'A']);
    Schema::make($host)->statePath('data')->components([$field])->getComponents();

    $field->refreshSelectedOptionLabel();

    expect($responder->getBrowserEventsToDispatch())->toBe([
        ['event' => 'sylament:select-refresh-selected-option-label', 'payload' => ['statePath' => 'data.status']],
    ]);
});

/*
 * Validation's second arm: a select whose option map is not the whole truth —
 * no `options()` but a `getOptionLabelUsing()` — is validated by probing the
 * label of the submitted value, Filament's `getInValidationRuleValues()`, and a
 * failed probe is a `Choice` over nothing, so the value is rejected rather than
 * waved through.
 */

it('throws when validating a select with neither options nor a label closure', function (): void {
    $field = wireSelect(Select::make('status')->getSearchResultsUsing(fn (): array => []));
    $field->state('draft');

    expect(fn (): array => $field->getValidationConstraints())
        ->toThrow(LogicException::class, 'getOptionLabelUsing()');
});

it('accepts a value the label closure resolves and rejects one it does not', function (): void {
    $field = wireSelect(
        Select::make('status')
            ->getSearchResultsUsing(fn (): array => ['draft' => 'Draft'])
            ->getOptionLabelUsing(fn (mixed $value): ?string => $value === 'draft' ? 'Draft' : null),
    );
    $validator = $this::getContainer()->get(ValidatorInterface::class);

    $field->state('draft');
    $accepted = $field->getValidationConstraints();

    expect(array_filter($accepted, fn (object $c): bool => $c instanceof Choice))->toBe([])
        ->and($validator->validate($field->getRawState(), $accepted))->toHaveCount(0);

    $field->state('tampered');
    $rejected = $field->getValidationConstraints();
    $choice = array_values(array_filter($rejected, fn (object $c): bool => $c instanceof Choice));

    expect($choice)->toHaveCount(1)
        ->and($choice[0]->choices)->toBe([])
        ->and($choice[0]->strict)->toBeTrue()
        ->and($validator->validate($field->getRawState(), $rejected))->toHaveCount(1);
});

it('rejects a probed value whose option is disabled', function (): void {
    $field = wireSelect(
        Select::make('status')
            ->getOptionLabelUsing(fn (): string => 'Draft')
            ->disableOptionWhen(fn (string|int $value): bool => $value === 'draft'),
    );
    $field->state('draft');

    $choice = array_values(array_filter($field->getValidationConstraints(), fn (object $c): bool => $c instanceof Choice));

    expect($choice)->toHaveCount(1)
        ->and($choice[0]->choices)->toBe([]);
});
