<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components;

use LogicException;
use Sylament\Forms\Components\Radio;
use Sylament\Forms\Tests\Fixtures\Components\Concerns\OptionPriority;
use Sylament\Forms\Tests\Fixtures\Components\Concerns\OptionStatus;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\Casts\BooleanStateCast;
use Sylament\Schemas\State\Casts\OptionStateCast;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use function Sylament\Forms\Tests\renderField;
use function Sylament\Forms\Tests\renderToHtml;

/**
 * Wire a radio into a `data`-bag-bound parent schema, mirroring `renderField()`'s
 * container — the same rig `wireSelect()` builds one file over, and for the same
 * reason: the shared `wireField()` helper binds a schema with no state path, so a
 * field's `state()` would write straight onto the host object.
 */
function wireRadio(Radio $field): Radio
{
    Schema::make(new FieldRenderHost())->statePath('data')->components([$field])->getComponents();

    return $field;
}

/**
 * Render a radio against a host whose `data` bag is pre-filled, so `getState()`
 * resolves to a real selection and the template's server-side `checked` has
 * something to answer.
 *
 * @param array<string, mixed> $data
 */
function renderRadioWithState(Radio $field, array $data): string
{
    $host = new FieldRenderHost();
    $host->data = $data;

    return renderToHtml(Schema::make($host)->statePath('data')->components([$field]));
}

/**
 * Wire a radio into a keyed, validator-backed schema and run the host's real
 * validation over it, so the field reports the messages a failed submit leaves in
 * the host error bag. `renderField()` builds no host validator, so an
 * `aria-invalid="true"` assertion needs this instead — the same rig
 * `validatedToggleSchema()` builds one file over.
 */
function validatedRadioSchema(Radio $field): Schema
{
    $host = new FieldRenderHost();
    $host->setSchemaStateValidator(test()->getContainer()->get(ValidatorInterface::class));

    $schema = Schema::make($host)->statePath('data')->components([$field]);
    $host->cacheSchema('schema', $schema);
    $schema->hydrateState();

    $host->validateSchema($schema, shouldThrow: false);

    return $schema;
}

it('renders one radio per option, grouped by a shared name', function (): void {
    $crawler = new Crawler(renderField(
        Radio::make('status')->label('Status')->options(['draft' => 'Draft', 'published' => 'Published']),
    ));

    $group = $crawler->filter('div.sy-fo-radio');
    expect($group->count())->toBe(1)
        ->and($group->attr('role'))->toBe('radiogroup');

    $inputs = $group->filter('label.sy-fo-radio-label input.sy-fo-radio-input');
    expect($inputs->count())->toBe(2)
        ->and($inputs->eq(0)->attr('type'))->toBe('radio')
        ->and($inputs->eq(0)->attr('name'))->toBe('status')
        ->and($inputs->eq(1)->attr('name'))->toBe('status')
        ->and($inputs->eq(0)->attr('id'))->toBe('status-draft')
        ->and($inputs->eq(1)->attr('id'))->toBe('status-published')
        ->and($inputs->eq(0)->attr('value'))->toBe('draft')
        ->and($inputs->eq(1)->attr('value'))->toBe('published');

    $labels = $group->filter('label.sy-fo-radio-label .sy-fo-radio-label-text p');
    expect($labels->eq(0)->text())->toBe('Draft')
        ->and($labels->eq(1)->text())->toBe('Published');
});

it('binds every radio to the one state path', function (): void {
    $inputs = new Crawler(renderField(
        Radio::make('status')->options(['draft' => 'Draft', 'published' => 'Published']),
    ))->filter('input.sy-fo-radio-input');

    expect($inputs->eq(0)->attr('data-model'))->toBe('norender|data.status')
        ->and($inputs->eq(1)->attr('data-model'))->toBe('norender|data.status');
});

it('checks the option matching the state and no other', function (): void {
    $inputs = new Crawler(renderRadioWithState(
        Radio::make('status')->options(['draft' => 'Draft', 'published' => 'Published']),
        ['status' => 'draft'],
    ))->filter('input.sy-fo-radio-input');

    expect($inputs->eq(0)->attr('checked'))->not->toBeNull()
        ->and($inputs->eq(1)->attr('checked'))->toBeNull();
});

it('checks nothing when the state is unset, including a zero-keyed option', function (): void {
    $inputs = new Crawler(renderField(Radio::make('featured')->boolean()))
        ->filter('input.sy-fo-radio-input');

    expect($inputs->count())->toBe(2)
        ->and($inputs->eq(0)->attr('checked'))->toBeNull()
        ->and($inputs->eq(1)->attr('checked'))->toBeNull();
});

it('renders one radio per enum case, labelled by the enum', function (): void {
    $inputs = new Crawler(renderField(Radio::make('status')->options(OptionStatus::class)))
        ->filter('input.sy-fo-radio-input');

    expect($inputs->count())->toBe(2)
        ->and($inputs->eq(0)->attr('value'))->toBe('draft')
        ->and($inputs->eq(1)->attr('value'))->toBe('published');

    $labels = new Crawler(renderField(Radio::make('status')->options(OptionStatus::class)))
        ->filter('.sy-fo-radio-label-text p');

    expect($labels->eq(0)->text())->toBe('Draft')
        ->and($labels->eq(1)->text())->toBe('Published');
});

it('checks the option matching an enum-valued state', function (): void {
    $inputs = new Crawler(renderRadioWithState(
        Radio::make('status')->options(OptionStatus::class),
        ['status' => OptionStatus::Published],
    ))->filter('input.sy-fo-radio-input');

    expect($inputs->eq(0)->attr('checked'))->toBeNull()
        ->and($inputs->eq(1)->attr('checked'))->not->toBeNull();
});

it('renders a description under the option the enum describes, and none under the one it does not', function (): void {
    $crawler = new Crawler(renderField(Radio::make('priority')->options(OptionPriority::class)));

    $options = $crawler->filter('label.sy-fo-radio-label');
    expect($options->count())->toBe(2)
        ->and($options->eq(0)->filter('.sy-fo-radio-label-description')->count())->toBe(1)
        ->and($options->eq(0)->filter('.sy-fo-radio-label-description')->text())->toBe('Handled when there is time')
        ->and($options->eq(1)->filter('.sy-fo-radio-label-description')->count())->toBe(0);
});

it('renders explicit descriptions keyed by option value', function (): void {
    $crawler = new Crawler(renderField(
        Radio::make('status')
            ->options(['draft' => 'Draft', 'published' => 'Published'])
            ->descriptions(['published' => 'Visible to everyone']),
    ));

    $options = $crawler->filter('label.sy-fo-radio-label');
    expect($options->eq(0)->filter('.sy-fo-radio-label-description')->count())->toBe(0)
        ->and($options->eq(1)->filter('.sy-fo-radio-label-description')->text())->toBe('Visible to everyone');
});

it('disables only the option the callback names', function (): void {
    $inputs = new Crawler(renderField(
        Radio::make('status')
            ->options(['draft' => 'Draft', 'published' => 'Published'])
            ->disableOptionWhen(fn (string|int $value): bool => $value === 'published'),
    ))->filter('input.sy-fo-radio-input');

    expect($inputs->eq(0)->attr('disabled'))->toBeNull()
        ->and($inputs->eq(1)->attr('disabled'))->not->toBeNull();
});

it('disables every option when the field is disabled', function (): void {
    $inputs = new Crawler(renderField(
        Radio::make('status')->options(['draft' => 'Draft', 'published' => 'Published'])->disabled(),
    ))->filter('input.sy-fo-radio-input');

    expect($inputs->eq(0)->attr('disabled'))->not->toBeNull()
        ->and($inputs->eq(1)->attr('disabled'))->not->toBeNull();
});

it('autofocuses the first option only', function (): void {
    $inputs = new Crawler(renderField(
        Radio::make('status')->options(['draft' => 'Draft', 'published' => 'Published'])->autofocus(),
    ))->filter('input.sy-fo-radio-input');

    expect($inputs->eq(0)->attr('autofocus'))->not->toBeNull()
        ->and($inputs->eq(1)->attr('autofocus'))->toBeNull();
});

it('lays the options out as a single-column grid by default', function (): void {
    $group = new Crawler(renderField(
        Radio::make('status')->options(['draft' => 'Draft', 'published' => 'Published']),
    ))->filter('div.sy-fo-radio');

    expect($group->attr('class'))->toBe('sy sy-fo-radio sy-grid sy-grid-direction-col sy-grid-cols')
        ->and($group->attr('style'))->toBe('--cols-default: 1');
});

it('emits the grid classes and column variables columns() asks for', function (): void {
    $group = new Crawler(renderField(
        Radio::make('status')->options(['draft' => 'Draft', 'published' => 'Published'])->columns(2),
    ))->filter('div.sy-fo-radio');

    expect($group->attr('class'))->toBe('sy sy-fo-radio sy-grid sy-grid-direction-col lg:sy-grid-cols sy-grid-cols')
        ->and($group->attr('style'))->toBe('--cols-lg: 2; --cols-default: 1');
});

it('flows the grid across the columns when the direction is Row', function (): void {
    $group = new Crawler(renderField(
        Radio::make('status')
            ->options(['draft' => 'Draft', 'published' => 'Published'])
            ->columns(2)
            ->gridDirection('row'),
    ))->filter('div.sy-fo-radio');

    expect($group->attr('class'))->toBe('sy sy-fo-radio sy-grid lg:sy-grid-cols sy-grid-cols')
        ->and($group->attr('style'))->toBe('--cols-lg: repeat(2, minmax(0, 1fr)); --cols-default: repeat(1, minmax(0, 1fr))');
});

it('drops the grid entirely when inline', function (): void {
    $group = new Crawler(renderField(
        Radio::make('status')->options(['draft' => 'Draft', 'published' => 'Published'])->columns(2)->inline(),
    ))->filter('div.sy-fo-radio');

    expect($group->attr('class'))->toBe('sy sy-fo-radio sy-inline')
        ->and($group->attr('style'))->toBeNull();
});

it('defaults to not inline', function (): void {
    expect(Radio::make('status')->isInline())->toBeFalse()
        ->and(Radio::make('status')->inline()->isInline())->toBeTrue()
        ->and(Radio::make('status')->inline(fn (): bool => true)->isInline())->toBeTrue();
});

it('points aria-labelledby at the wrapper heading the field renders', function (): void {
    $crawler = new Crawler(renderField(
        Radio::make('status')->label('Status')->options(['draft' => 'Draft']),
    ));

    $label = $crawler->filter('div.sy-fo-field-label');
    $group = $crawler->filter('div.sy-fo-radio');

    expect($crawler->filter('label.sy-fo-field-label')->count())->toBe(0)
        ->and($label->count())->toBe(1)
        ->and($label->attr('id'))->toBe('status-label')
        ->and($group->attr('aria-labelledby'))->toBe('status-label');
});

it('keeps aria-labelledby resolvable when the label is hidden', function (): void {
    $crawler = new Crawler(renderField(
        Radio::make('status')->label('Status')->hiddenLabel()->options(['draft' => 'Draft']),
    ));

    expect($crawler->filter('div.sy-fo-field-label.sy-fo-sr-only')->attr('id'))->toBe('status-label')
        ->and($crawler->filter('div.sy-fo-radio')->attr('aria-labelledby'))->toBe('status-label');
});

it('marks every input invalid when the field has errors, and valid otherwise', function (): void {
    $valid = new Crawler(renderField(
        Radio::make('status')->options(['draft' => 'Draft', 'published' => 'Published']),
    ))->filter('input.sy-fo-radio-input');

    expect($valid->eq(0)->attr('aria-invalid'))->toBe('false')
        ->and($valid->eq(1)->attr('aria-invalid'))->toBe('false');

    // The true arm is what `radio.css`'s `[aria-invalid='true']` danger ring keys
    // off, and it has to reach every option: a radiogroup is invalid as a whole.
    $crawler = new Crawler(renderToHtml(validatedRadioSchema(
        Radio::make('status')->options(['draft' => 'Draft', 'published' => 'Published'])->required(),
    )));
    $invalid = $crawler->filter('input.sy-fo-radio-input');

    expect($invalid->count())->toBe(2)
        ->and($invalid->eq(0)->attr('aria-invalid'))->toBe('true')
        ->and($invalid->eq(1)->attr('aria-invalid'))->toBe('true')
        ->and($crawler->filter('.sy-fo-field-error-message')->count())->toBe(1);
});

it('merges extra input attributes onto every radio and lets them win', function (): void {
    $crawler = new Crawler(renderField(
        Radio::make('status')
            ->options(['draft' => 'Draft', 'published' => 'Published'])
            ->disabled()
            ->extraInputAttributes([
                'data-extra' => 'yes',
                'disabled' => false,
            ]),
    ));

    $inputs = $crawler->filter('input.sy-fo-radio-input');

    expect($inputs->count())->toBe(2)
        ->and($inputs->eq(0)->attr('data-extra'))->toBe('yes')
        ->and($inputs->eq(0)->attr('disabled'))->toBeNull()
        ->and($inputs->eq(1)->attr('disabled'))->toBeNull()
        ->and($crawler->filter('div.sy-fo-radio[data-extra]')->count())->toBe(0);
});

it('leaves extraAttributes on the field wrapper rather than the radiogroup', function (): void {
    $crawler = new Crawler(renderField(
        Radio::make('status')->options(['draft' => 'Draft'])->extraAttributes(['data-wrapper' => 'yes']),
    ));

    expect($crawler->filter('div.sy-fo-field[data-wrapper="yes"]')->count())->toBe(1)
        ->and($crawler->filter('div.sy-fo-radio[data-wrapper]')->count())->toBe(0);
});

it('casts option state through OptionStateCast', function (): void {
    $field = wireRadio(Radio::make('status')->options([1 => 'One', 2 => 'Two']));
    $field->state('1');

    expect($field->getStateCasts())->toHaveCount(1)
        ->and($field->getStateCasts()[0])->toBeInstanceOf(OptionStateCast::class)
        ->and($field->getState())->toBe(1);
});

it('collapses the cast chain to the boolean cast alone and round-trips true', function (): void {
    $field = wireRadio(Radio::make('featured')->boolean());
    $field->state(true);

    expect($field->getOptions())->toBe([1 => 'Yes', 0 => 'No'])
        ->and($field->getStateCasts())->toHaveCount(1)
        ->and($field->getStateCasts()[0])->toBeInstanceOf(BooleanStateCast::class)
        ->and($field->getRawState())->toBe(1)
        ->and($field->getState())->toBeTrue();
});

it('renders boolean() as Yes and No over the values 1 and 0, checking the true option', function (): void {
    $crawler = new Crawler(renderRadioWithState(Radio::make('featured')->boolean(), ['featured' => 1]));

    $inputs = $crawler->filter('input.sy-fo-radio-input');
    $labels = $crawler->filter('.sy-fo-radio-label-text p');

    expect($inputs->count())->toBe(2)
        ->and($inputs->eq(0)->attr('value'))->toBe('1')
        ->and($inputs->eq(1)->attr('value'))->toBe('0')
        ->and($labels->eq(0)->text())->toBe('Yes')
        ->and($labels->eq(1)->text())->toBe('No')
        ->and($inputs->eq(0)->attr('checked'))->not->toBeNull()
        ->and($inputs->eq(1)->attr('checked'))->toBeNull();
});

it('takes the boolean() labels a caller passes', function (): void {
    expect(Radio::make('featured')->boolean('Live', 'Hidden')->getOptions())->toBe([1 => 'Live', 0 => 'Hidden']);
});

it('coerces a boolean default into the option vocabulary', function (): void {
    expect(Radio::make('featured')->boolean()->default(true)->getDefaultState())->toBe(1)
        ->and(Radio::make('featured')->boolean()->default(false)->getDefaultState())->toBe(0)
        ->and(Radio::make('status')->default('draft')->getDefaultState())->toBe('draft')
        ->and(Radio::make('status')->getDefaultState())->toBeNull();
});

it('declares a nullable boolean state', function (): void {
    expect(Radio::make('featured')->hasNullableBooleanState())->toBeTrue();
});

it('builds a Choice constraint over the enabled options', function (): void {
    $field = wireRadio(Radio::make('status')->options(['draft' => 'Draft', 'published' => 'Published']));
    $field->state('draft');

    $choice = array_values(array_filter(
        $field->getValidationConstraints(),
        fn (object $c): bool => $c instanceof Choice,
    ));

    expect($choice)->toHaveCount(1)
        ->and($choice[0]->choices)->toBe(['draft', 'published'])
        ->and($choice[0]->multiple)->toBeFalse()
        ->and($choice[0]->strict)->toBeTrue();
});

it('rejects a tampered value server-side', function (): void {
    $field = wireRadio(Radio::make('status')->options(['draft' => 'Draft', 'published' => 'Published']));
    $field->state('draft');

    $validator = $this::getContainer()->get(ValidatorInterface::class);
    $constraints = $field->getValidationConstraints();

    expect($validator->validate($field->getRawState(), $constraints))->toHaveCount(0)
        ->and($validator->validate('tampered', $constraints))->toHaveCount(1);
});

it('rejects a disabled option server-side', function (): void {
    $field = wireRadio(
        Radio::make('status')
            ->options(['draft' => 'Draft', 'published' => 'Published'])
            ->disableOptionWhen(fn (string|int $value): bool => $value === 'published'),
    );
    $field->state('published');

    $validator = $this::getContainer()->get(ValidatorInterface::class);

    expect($validator->validate($field->getRawState(), $field->getValidationConstraints()))->toHaveCount(1);
});

it('accepts both stored forms of a boolean() option server-side', function (): void {
    $field = wireRadio(Radio::make('featured')->boolean());
    $field->rawState('1');

    $validator = $this::getContainer()->get(ValidatorInterface::class);
    $constraints = $field->getValidationConstraints();

    $choice = array_values(array_filter($constraints, fn (object $c): bool => $c instanceof Choice));

    expect($choice[0]->choices)->toBe([1, '1', 0, '0'])
        ->and($validator->validate('1', $constraints))->toHaveCount(0)
        ->and($validator->validate('0', $constraints))->toHaveCount(0)
        ->and($validator->validate('2', $constraints))->toHaveCount(1);
});

it('validates an enum-only radio instead of throwing for missing options', function (): void {
    $field = wireRadio(Radio::make('status')->enum(OptionStatus::class));
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

    $field = Radio::make('status')->options(OptionStatus::class)->required();
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

it('builds no Choice constraint for blank state', function (): void {
    $field = wireRadio(Radio::make('status')->options(['draft' => 'Draft']));

    expect($field->getValidationConstraints())->toBe([]);
});

it('throws when validating a radio with no options', function (): void {
    $field = wireRadio(Radio::make('status'));
    $field->state('draft');

    $field->getValidationConstraints();
})->throws(LogicException::class);

it('fails a required radio with nothing selected', function (): void {
    $host = new FieldRenderHost();
    $host->setSchemaStateValidator($this::getContainer()->get(ValidatorInterface::class));

    $field = Radio::make('status')->options(['draft' => 'Draft'])->required();
    $schema = Schema::make($host)->statePath('data')->components([$field]);

    $host->cacheSchema('schema', $schema);
    $schema->getComponents();
    $schema->fill();

    $host->validateSchema($schema, shouldThrow: false);

    expect($host->getErrorsForSchemaStatePath('data.status'))->toHaveCount(1);

    $field->state('draft');
    $host->validateSchema($schema, shouldThrow: false);

    expect($host->getErrorsForSchemaStatePath('data.status'))->toBe([]);
});

/*
 * The `0`-keyed false option is the one required() case worth pinning rather than
 * assuming: NotBlank rejects null, '' and false but carves out `0` and '0', so a
 * required boolean() radio rejects only *no answer*. A cast that ever stored the
 * false option as a real `false` would silently turn required() into accepted().
 */
it('accepts the false option of a required boolean() radio, in both stored forms', function (): void {
    $field = wireRadio(Radio::make('featured')->boolean()->required());
    $field->state(false);

    $validator = $this::getContainer()->get(ValidatorInterface::class);
    $constraints = $field->getValidationConstraints();

    expect($field->getRawState())->toBe(0)
        ->and($validator->validate($field->getRawState(), $constraints))->toHaveCount(0)
        ->and($validator->validate('0', $constraints))->toHaveCount(0)
        ->and($validator->validate(null, $constraints))->toHaveCount(1);
});
