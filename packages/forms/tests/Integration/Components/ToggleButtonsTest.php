<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components;

use LogicException;
use Sylament\Forms\Components\ToggleButtons;
use Sylament\Forms\Tests\Fixtures\Components\Concerns\OptionRating;
use Sylament\Forms\Tests\Fixtures\Components\Concerns\OptionStatus;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Forms\Twig\FormsIconAlias;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\Casts\BooleanStateCast;
use Sylament\Schemas\State\Casts\EnumArrayStateCast;
use Sylament\Schemas\State\Casts\EnumStateCast;
use Sylament\Schemas\State\Casts\OptionsArrayStateCast;
use Sylament\Schemas\State\Casts\OptionStateCast;
use Sylament\Support\Colors\Color;
use Sylament\Support\Icons\IconManager;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Twig\Markup;

use function Sylament\Forms\Tests\renderField;
use function Sylament\Forms\Tests\renderToHtml;

/**
 * Wire a toggle-buttons field into a `data`-bag-bound parent schema, mirroring
 * `renderField()`'s container — the same rig `wireRadio()` builds one file over,
 * and for the same reason: the shared `wireField()` helper binds a schema with no
 * state path, so a field's `state()` would write straight onto the host object.
 */
function wireToggleButtons(ToggleButtons $field): ToggleButtons
{
    Schema::make(new FieldRenderHost())->statePath('data')->components([$field])->getComponents();

    return $field;
}

/**
 * Render a toggle-buttons field against a host whose `data` bag is pre-filled, so
 * `getState()` resolves to a real selection and the template's server-side
 * `checked` has something to answer.
 *
 * @param array<string, mixed> $data
 */
function renderToggleButtonsWithState(ToggleButtons $field, array $data): string
{
    $host = new FieldRenderHost();
    $host->data = $data;

    return renderToHtml(Schema::make($host)->statePath('data')->components([$field]));
}

/**
 * Wire a toggle-buttons field into a keyed, validator-backed schema and run the
 * host's real validation over it, so the field reports the messages a failed
 * submit leaves in the host error bag — the rig `validatedRadioSchema()` builds
 * one file over, and the only way to reach the `aria-invalid="true"` arm.
 */
function validatedToggleButtonsSchema(ToggleButtons $field): Schema
{
    $host = new FieldRenderHost();
    $host->setSchemaStateValidator(test()->getContainer()->get(ValidatorInterface::class));

    $schema = Schema::make($host)->statePath('data')->components([$field]);
    $host->cacheSchema('schema', $schema);
    $schema->hydrateState();

    $host->validateSchema($schema, shouldThrow: false);

    return $schema;
}

it('renders one button per option, each wired to its own hidden input', function (): void {
    $crawler = new Crawler(renderField(
        ToggleButtons::make('status')->label('Status')->options(['draft' => 'Draft', 'published' => 'Published']),
    ));

    $group = $crawler->filter('div.sy-fo-toggle-buttons');
    expect($group->count())->toBe(1)
        ->and($group->attr('role'))->toBe('radiogroup')
        ->and($group->attr('aria-labelledby'))->toBe('status-label');

    $containers = $group->filter('div.sy-fo-toggle-buttons-btn-ctn');
    expect($containers->count())->toBe(2);

    $inputs = $containers->filter('input.sy-fo-toggle-buttons-input');
    expect($inputs->eq(0)->attr('type'))->toBe('radio')
        ->and($inputs->eq(0)->attr('name'))->toBe('status')
        ->and($inputs->eq(1)->attr('name'))->toBe('status')
        ->and($inputs->eq(0)->attr('id'))->toBe('status-draft')
        ->and($inputs->eq(1)->attr('id'))->toBe('status-published')
        ->and($inputs->eq(0)->attr('value'))->toBe('draft')
        ->and($inputs->eq(1)->attr('value'))->toBe('published');

    $labels = $containers->filter('label.sy-btn');
    expect($labels->eq(0)->attr('for'))->toBe('status-draft')
        ->and($labels->eq(1)->attr('for'))->toBe('status-published')
        ->and($labels->eq(0)->text())->toBe('Draft')
        ->and($labels->eq(1)->text())->toBe('Published')
        ->and($labels->eq(0)->attr('class'))->toContain('sy-size-md');
});

it('binds every input to the one state path', function (): void {
    $inputs = new Crawler(renderField(
        ToggleButtons::make('status')->options(['draft' => 'Draft', 'published' => 'Published']),
    ))->filter('input.sy-fo-toggle-buttons-input');

    expect($inputs->eq(0)->attr('data-model'))->toBe('norender|data.status')
        ->and($inputs->eq(1)->attr('data-model'))->toBe('norender|data.status');
});

it('checks the option matching the state and no other', function (): void {
    $inputs = new Crawler(renderToggleButtonsWithState(
        ToggleButtons::make('status')->options(['draft' => 'Draft', 'published' => 'Published']),
        ['status' => 'draft'],
    ))->filter('input.sy-fo-toggle-buttons-input');

    expect($inputs->eq(0)->attr('checked'))->not->toBeNull()
        ->and($inputs->eq(1)->attr('checked'))->toBeNull();
});

it('checks nothing when the state is unset, including a zero-keyed option', function (): void {
    $inputs = new Crawler(renderField(ToggleButtons::make('featured')->boolean()))
        ->filter('input.sy-fo-toggle-buttons-input');

    expect($inputs->count())->toBe(2)
        ->and($inputs->eq(0)->attr('checked'))->toBeNull()
        ->and($inputs->eq(1)->attr('checked'))->toBeNull();
});

it('switches to checkboxes with no shared name when multiple', function (): void {
    $crawler = new Crawler(renderField(
        ToggleButtons::make('tags')->multiple()->options(['a' => 'A', 'b' => 'B']),
    ));

    expect($crawler->filter('div.sy-fo-toggle-buttons')->attr('role'))->toBe('group');

    $inputs = $crawler->filter('input.sy-fo-toggle-buttons-input');
    expect($inputs->count())->toBe(2)
        ->and($inputs->eq(0)->attr('type'))->toBe('checkbox')
        ->and($inputs->eq(1)->attr('type'))->toBe('checkbox')
        ->and($inputs->eq(0)->attr('name'))->toBeNull()
        ->and($inputs->eq(1)->attr('name'))->toBeNull();
});

it('checks every option an array state holds when multiple', function (): void {
    $inputs = new Crawler(renderToggleButtonsWithState(
        ToggleButtons::make('tags')->multiple()->options(['a' => 'A', 'b' => 'B', 'c' => 'C']),
        ['tags' => ['a', 'c']],
    ))->filter('input.sy-fo-toggle-buttons-input');

    expect($inputs->count())->toBe(3)
        ->and($inputs->eq(0)->attr('checked'))->not->toBeNull()
        ->and($inputs->eq(1)->attr('checked'))->toBeNull()
        ->and($inputs->eq(2)->attr('checked'))->not->toBeNull();
});

it('is not multiple, inline or grouped by default', function (): void {
    expect(ToggleButtons::make('status')->isMultiple())->toBeFalse()
        ->and(ToggleButtons::make('status')->multiple()->isMultiple())->toBeTrue()
        ->and(ToggleButtons::make('status')->multiple(fn (): bool => true)->isMultiple())->toBeTrue()
        ->and(ToggleButtons::make('status')->isInline())->toBeFalse()
        ->and(ToggleButtons::make('status')->inline()->isInline())->toBeTrue()
        ->and(ToggleButtons::make('status')->isGrouped())->toBeFalse()
        ->and(ToggleButtons::make('status')->grouped()->isGrouped())->toBeTrue()
        ->and(ToggleButtons::make('status')->grouped(fn (): bool => true)->isGrouped())->toBeTrue()
        ->and(ToggleButtons::make('status')->areButtonLabelsHidden())->toBeFalse()
        ->and(ToggleButtons::make('status')->hiddenButtonLabels()->areButtonLabelsHidden())->toBeTrue();
});

it('selects the grouped template only while grouped, and lets either setting outrank it', function (): void {
    expect(ToggleButtons::make('status')->getTemplate())->toBe(ToggleButtons::TEMPLATE)
        ->and(ToggleButtons::make('status')->grouped()->getTemplate())->toBe(ToggleButtons::GROUPED_TEMPLATE)
        ->and(ToggleButtons::make('status')->grouped()->template('@App/mine.html.twig')->getTemplate())
        ->toBe('@App/mine.html.twig')
        ->and(ToggleButtons::make('status')->grouped()->defaultTemplate('@App/theirs.html.twig')->getTemplate())
        ->toBe('@App/theirs.html.twig');
});

it('renders the grouped variant as a flat button group with no grid', function (): void {
    $crawler = new Crawler(renderField(
        ToggleButtons::make('status')->grouped()->columns(2)->options(['draft' => 'Draft', 'published' => 'Published']),
    ));

    $group = $crawler->filter('div.sy-fo-toggle-buttons');
    expect($group->attr('class'))->toBe('sy sy-fo-toggle-buttons sy-btn-group')
        ->and($group->attr('style'))->toBeNull()
        ->and($group->attr('role'))->toBe('radiogroup')
        ->and($crawler->filter('div.sy-fo-toggle-buttons-btn-ctn')->count())->toBe(0);

    $labels = $group->filter('label.sy-btn');
    expect($labels->count())->toBe(2)
        ->and($labels->eq(0)->attr('class'))->toContain('sy-btn-group-btn')
        ->and($labels->eq(1)->attr('class'))->toContain('sy-btn-group-btn')
        ->and($labels->eq(0)->attr('for'))->toBe('status-draft')
        ->and($group->filter('input.sy-fo-toggle-buttons-input')->count())->toBe(2)
        ->and($crawler->filter('div.sy-btn-group > label.sy-btn')->count())->toBe(2);
});

it('lays the default variant out as a single-column grid, and drops the grid when inline', function (): void {
    $group = new Crawler(renderField(
        ToggleButtons::make('status')->options(['draft' => 'Draft', 'published' => 'Published']),
    ))->filter('div.sy-fo-toggle-buttons');

    expect($group->attr('class'))->toBe('sy sy-fo-toggle-buttons sy-grid sy-grid-direction-col sy-grid-cols')
        ->and($group->attr('style'))->toBe('--cols-default: 1');

    $inline = new Crawler(renderField(
        ToggleButtons::make('status')->options(['draft' => 'Draft'])->columns(2)->inline(),
    ))->filter('div.sy-fo-toggle-buttons');

    expect($inline->attr('class'))->toBe('sy sy-fo-toggle-buttons sy-inline')
        ->and($inline->attr('style'))->toBeNull();
});

it('autofocuses the first option of the default variant and none of the grouped one', function (): void {
    $inputs = new Crawler(renderField(
        ToggleButtons::make('status')->options(['draft' => 'Draft', 'published' => 'Published'])->autofocus(),
    ))->filter('input.sy-fo-toggle-buttons-input');

    expect($inputs->eq(0)->attr('autofocus'))->not->toBeNull()
        ->and($inputs->eq(1)->attr('autofocus'))->toBeNull();

    $grouped = new Crawler(renderField(
        ToggleButtons::make('status')->grouped()->options(['draft' => 'Draft', 'published' => 'Published'])->autofocus(),
    ))->filter('input.sy-fo-toggle-buttons-input');

    expect($grouped->eq(0)->attr('autofocus'))->toBeNull()
        ->and($grouped->eq(1)->attr('autofocus'))->toBeNull();
});

it('paints each button with its own colour, defaulting to primary', function (): void {
    $labels = new Crawler(renderField(
        ToggleButtons::make('status')
            ->options(['draft' => 'Draft', 'published' => 'Published'])
            ->colors(['published' => 'success']),
    ))->filter('label.sy-btn');

    expect($labels->eq(0)->attr('class'))->toContain('sy-color-primary')
        ->and($labels->eq(1)->attr('class'))->toContain('sy-color-success')
        ->and($labels->eq(1)->attr('class'))->not->toContain('sy-color-primary');
});

it('renders an inline palette as a style declaration when the colour is a shade map', function (): void {
    $label = new Crawler(renderField(
        ToggleButtons::make('status')
            ->options(['draft' => 'Draft'])
            ->colors(['draft' => Color::Rose]),
    ))->filter('label.sy-btn');

    expect($label->attr('class'))->toBe('sy-btn sy-size-md sy-color')
        ->and($label->attr('style'))->toContain('--sy-color-500: ' . Color::Rose[500]);
});

it('renders the icon a value is mapped to inside its button', function (): void {
    $labels = new Crawler(renderField(
        ToggleButtons::make('status')
            ->options(['draft' => 'Draft', 'published' => 'Published'])
            ->icons(['published' => 'heroicons:star']),
    ))->filter('label.sy-btn');

    expect($labels->eq(0)->filter('svg')->count())->toBe(0)
        ->and($labels->eq(1)->filter('svg')->count())->toBe(1)
        ->and($labels->eq(1)->filter('svg')->attr('class'))->toBe('sy sy-icon sy-size-md');
});

it('derives colours and icons from an enum that carries them', function (): void {
    $labels = new Crawler(renderField(
        ToggleButtons::make('rating')->options(OptionRating::class),
    ))->filter('label.sy-btn');

    expect($labels->count())->toBe(2)
        ->and($labels->eq(0)->attr('class'))->toContain('sy-color-success')
        ->and($labels->eq(1)->attr('class'))->toContain('sy-color-danger')
        ->and($labels->eq(0)->filter('svg')->count())->toBe(1)
        ->and($labels->eq(1)->filter('svg')->count())->toBe(1);
});

it('renders a tooltip as a native title', function (): void {
    $labels = new Crawler(renderField(
        ToggleButtons::make('status')
            ->options(['draft' => 'Draft', 'published' => 'Published'])
            ->tooltips(['draft' => 'Only you can see this']),
    ))->filter('label.sy-btn');

    expect($labels->eq(0)->attr('title'))->toBe('Only you can see this')
        ->and($labels->eq(1)->attr('title'))->toBeNull();
});

/*
 * A `title` is text, so a `Markup` tooltip's tags reach the user as literal
 * characters rather than as formatting — the visible cost of rendering tooltips
 * natively, characterized here so a future tooltip primitive has something to
 * change against.
 */
it('shows a markup tooltip as literal text', function (): void {
    $label = new Crawler(renderField(
        ToggleButtons::make('status')
            ->options(['draft' => 'Draft'])
            ->tooltips(['draft' => new Markup('<b>Careful</b>', 'UTF-8')]),
    ))->filter('label.sy-btn');

    expect($label->attr('title'))->toBe('<b>Careful</b>');
});

it('replaces the button text with an aria-label when the labels are hidden', function (): void {
    $labels = new Crawler(renderField(
        ToggleButtons::make('status')
            ->options(['draft' => 'Draft'])
            ->icons(['draft' => 'heroicons:star'])
            ->hiddenButtonLabels(),
    ))->filter('label.sy-btn');

    expect($labels->attr('aria-label'))->toBe('Draft')
        ->and($labels->text())->toBe('')
        ->and($labels->filter('svg')->count())->toBe(1);
});

it('strips markup out of a hidden button label', function (): void {
    $labels = new Crawler(renderField(
        ToggleButtons::make('status')
            ->options(['draft' => new Markup('<b>Draft</b> ', 'UTF-8')])
            ->hiddenButtonLabels(),
    ))->filter('label.sy-btn');

    expect($labels->attr('aria-label'))->toBe('Draft');
});

it('disables the input and marks the button aria-disabled for a disabled option', function (): void {
    $crawler = new Crawler(renderField(
        ToggleButtons::make('status')
            ->options(['draft' => 'Draft', 'published' => 'Published'])
            ->disableOptionWhen(fn (string|int $value): bool => $value === 'published'),
    ));

    $inputs = $crawler->filter('input.sy-fo-toggle-buttons-input');
    $labels = $crawler->filter('label.sy-btn');

    expect($inputs->eq(0)->attr('disabled'))->toBeNull()
        ->and($inputs->eq(1)->attr('disabled'))->not->toBeNull()
        ->and($labels->eq(0)->attr('aria-disabled'))->toBeNull()
        ->and($labels->eq(1)->attr('aria-disabled'))->toBe('true')
        ->and($labels->eq(0)->attr('class'))->not->toContain('sy-disabled')
        ->and($labels->eq(1)->attr('class'))->toContain('sy-disabled');
});

it('disables every option when the field is disabled', function (): void {
    $inputs = new Crawler(renderField(
        ToggleButtons::make('status')->options(['draft' => 'Draft', 'published' => 'Published'])->disabled(),
    ))->filter('input.sy-fo-toggle-buttons-input');

    expect($inputs->eq(0)->attr('disabled'))->not->toBeNull()
        ->and($inputs->eq(1)->attr('disabled'))->not->toBeNull();
});

it('marks every input invalid when the field has errors, and valid otherwise', function (): void {
    $valid = new Crawler(renderField(
        ToggleButtons::make('status')->options(['draft' => 'Draft', 'published' => 'Published']),
    ))->filter('input.sy-fo-toggle-buttons-input');

    expect($valid->eq(0)->attr('aria-invalid'))->toBe('false')
        ->and($valid->eq(1)->attr('aria-invalid'))->toBe('false');

    $crawler = new Crawler(renderToHtml(validatedToggleButtonsSchema(
        ToggleButtons::make('status')->options(['draft' => 'Draft', 'published' => 'Published'])->required(),
    )));
    $invalid = $crawler->filter('input.sy-fo-toggle-buttons-input');

    expect($invalid->count())->toBe(2)
        ->and($invalid->eq(0)->attr('aria-invalid'))->toBe('true')
        ->and($invalid->eq(1)->attr('aria-invalid'))->toBe('true')
        ->and($crawler->filter('.sy-fo-field-error-message')->count())->toBe(1);
});

it('merges extra input attributes onto every input and lets them win', function (): void {
    $crawler = new Crawler(renderField(
        ToggleButtons::make('status')
            ->options(['draft' => 'Draft', 'published' => 'Published'])
            ->disabled()
            ->extraInputAttributes([
                'data-extra' => 'yes',
                'disabled' => false,
            ]),
    ));

    $inputs = $crawler->filter('input.sy-fo-toggle-buttons-input');

    expect($inputs->count())->toBe(2)
        ->and($inputs->eq(0)->attr('data-extra'))->toBe('yes')
        ->and($inputs->eq(0)->attr('disabled'))->toBeNull()
        ->and($inputs->eq(1)->attr('disabled'))->toBeNull()
        ->and($crawler->filter('div.sy-fo-toggle-buttons[data-extra]')->count())->toBe(0);
});

it('points aria-labelledby at the wrapper heading the field renders', function (): void {
    $crawler = new Crawler(renderField(
        ToggleButtons::make('status')->label('Status')->options(['draft' => 'Draft']),
    ));

    expect($crawler->filter('label.sy-fo-field-label')->count())->toBe(0)
        ->and($crawler->filter('div.sy-fo-field-label')->attr('id'))->toBe('status-label')
        ->and($crawler->filter('div.sy-fo-toggle-buttons')->attr('aria-labelledby'))->toBe('status-label');
});

it('renders boolean() as Yes and No over the values 1 and 0, coloured and iconed', function (): void {
    $crawler = new Crawler(renderToggleButtonsWithState(ToggleButtons::make('featured')->boolean(), ['featured' => 1]));

    $inputs = $crawler->filter('input.sy-fo-toggle-buttons-input');
    $labels = $crawler->filter('label.sy-btn');

    expect($inputs->count())->toBe(2)
        ->and($inputs->eq(0)->attr('value'))->toBe('1')
        ->and($inputs->eq(1)->attr('value'))->toBe('0')
        ->and($inputs->eq(0)->attr('checked'))->not->toBeNull()
        ->and($inputs->eq(1)->attr('checked'))->toBeNull()
        ->and($labels->eq(0)->text())->toBe('Yes')
        ->and($labels->eq(1)->text())->toBe('No')
        ->and($labels->eq(0)->attr('class'))->toContain('sy-color-success')
        ->and($labels->eq(1)->attr('class'))->toContain('sy-color-danger')
        ->and($labels->eq(0)->filter('svg')->count())->toBe(1)
        ->and($labels->eq(1)->filter('svg')->count())->toBe(1);
});

it('takes the boolean() labels a caller passes', function (): void {
    expect(ToggleButtons::make('featured')->boolean('Live', 'Hidden')->getOptions())->toBe([1 => 'Live', 0 => 'Hidden']);
});

it('defaults the boolean icons to the mini check and x-mark, under their aliases', function (): void {
    $field = ToggleButtons::make('featured')->boolean();

    expect($field->getIcons())->toBe([1 => 'heroicons:check-20-solid', 0 => 'heroicons:x-mark-20-solid'])
        ->and($field->getIconAlias(1))->toBe(FormsIconAlias::COMPONENTS_TOGGLE_BUTTONS_BOOLEAN_TRUE)
        ->and($field->getIconAlias(0))->toBe(FormsIconAlias::COMPONENTS_TOGGLE_BUTTONS_BOOLEAN_FALSE)
        ->and(ToggleButtons::make('status')->options(['a' => 'A'])->icons(['a' => 'heroicons:star'])->getIconAlias('a'))
        ->toBeNull();
});

it('lets a registered alias replace a boolean icon at render time', function (): void {
    $this::getContainer()->get(IconManager::class)->register([
        FormsIconAlias::COMPONENTS_TOGGLE_BUTTONS_BOOLEAN_TRUE => 'heroicons:star',
    ]);

    $labels = new Crawler(renderField(ToggleButtons::make('featured')->boolean()))->filter('label.sy-btn');

    expect($labels->eq(0)->filter('svg')->attr('viewBox'))->toBe('0 0 24 24')
        ->and($labels->eq(1)->filter('svg')->attr('viewBox'))->toBe('0 0 20 20');
});

it('collapses the cast chain to the boolean cast alone and round-trips true', function (): void {
    $field = wireToggleButtons(ToggleButtons::make('featured')->boolean());
    $field->state(true);

    expect($field->getOptions())->toBe([1 => 'Yes', 0 => 'No'])
        ->and($field->getStateCasts())->toHaveCount(1)
        ->and($field->getStateCasts()[0])->toBeInstanceOf(BooleanStateCast::class)
        ->and($field->getRawState())->toBe(1)
        ->and($field->getState())->toBeTrue();
});

it('coerces a boolean default into the option vocabulary', function (): void {
    expect(ToggleButtons::make('featured')->boolean()->default(true)->getDefaultState())->toBe(1)
        ->and(ToggleButtons::make('featured')->boolean()->default(false)->getDefaultState())->toBe(0)
        ->and(ToggleButtons::make('status')->default('draft')->getDefaultState())->toBe('draft')
        ->and(ToggleButtons::make('status')->getDefaultState())->toBeNull();
});

it('declares a nullable boolean state', function (): void {
    expect(ToggleButtons::make('featured')->hasNullableBooleanState())->toBeTrue();
});

it('picks the state cast the multiplicity asks for', function (): void {
    expect(ToggleButtons::make('status')->getDefaultStateCasts()[0])->toBeInstanceOf(OptionStateCast::class)
        ->and(ToggleButtons::make('tags')->multiple()->getDefaultStateCasts()[0])->toBeInstanceOf(OptionsArrayStateCast::class)
        ->and(ToggleButtons::make('status')->enum(OptionStatus::class)->getEnumDefaultStateCast())->toBeInstanceOf(EnumStateCast::class)
        ->and(ToggleButtons::make('tags')->multiple()->enum(OptionStatus::class)->getEnumDefaultStateCast())->toBeInstanceOf(EnumArrayStateCast::class)
        ->and(ToggleButtons::make('status')->getEnumDefaultStateCast())->toBeNull();
});

it('builds a Choice constraint over the enabled options', function (): void {
    $field = wireToggleButtons(ToggleButtons::make('status')->options(['draft' => 'Draft', 'published' => 'Published']));
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
    $field = wireToggleButtons(ToggleButtons::make('status')->options(['draft' => 'Draft', 'published' => 'Published']));
    $field->state('draft');

    $validator = $this::getContainer()->get(ValidatorInterface::class);
    $constraints = $field->getValidationConstraints();

    expect($validator->validate($field->getRawState(), $constraints))->toHaveCount(0)
        ->and($validator->validate('tampered', $constraints))->toHaveCount(1);
});

it('rejects a disabled option server-side', function (): void {
    $field = wireToggleButtons(
        ToggleButtons::make('status')
            ->options(['draft' => 'Draft', 'published' => 'Published'])
            ->disableOptionWhen(fn (string|int $value): bool => $value === 'published'),
    );
    $field->state('published');

    $validator = $this::getContainer()->get(ValidatorInterface::class);

    expect($validator->validate($field->getRawState(), $field->getValidationConstraints()))->toHaveCount(1);
});

it('validates a multiple field item by item', function (): void {
    $field = wireToggleButtons(ToggleButtons::make('tags')->multiple()->options(['a' => 'A', 'b' => 'B']));
    $field->state(['a', 'b']);

    $validator = $this::getContainer()->get(ValidatorInterface::class);
    $constraints = $field->getValidationConstraints();

    $choice = array_values(array_filter($constraints, fn (object $c): bool => $c instanceof Choice));

    expect($choice)->toHaveCount(1)
        ->and($choice[0]->multiple)->toBeTrue()
        ->and($validator->validate($field->getRawState(), $constraints))->toHaveCount(0)
        ->and($validator->validate(['a', 'tampered'], $constraints))->toHaveCount(1);
});

it('fails a multiple field whose raw state is not an array', function (): void {
    $field = wireToggleButtons(ToggleButtons::make('tags')->multiple()->options(['a' => 'A', 'b' => 'B']));
    $field->rawState('a');

    $constraints = $field->getValidationConstraints();
    $type = array_values(array_filter($constraints, fn (object $c): bool => $c instanceof Type));

    expect($type)->toHaveCount(1)
        ->and($constraints)->toHaveCount(1)
        ->and($this::getContainer()->get(ValidatorInterface::class)->validate('a', $constraints))->toHaveCount(1);
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

    $field = ToggleButtons::make('status')->options(OptionStatus::class)->required();
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

it('rejects a tampered element of a multiple enum field', function (): void {
    $host = new FieldRenderHost();
    $host->setSchemaStateValidator($this::getContainer()->get(ValidatorInterface::class));

    $field = ToggleButtons::make('tags')->multiple()->options(OptionStatus::class);
    $schema = Schema::make($host)->statePath('data')->components([$field]);
    $host->cacheSchema('schema', $schema);

    $host->data['tags'] = ['deleted-by-hand'];
    $schema->getComponents();

    expect($field->getState())->toBe([]);

    $host->validateSchema($schema, shouldThrow: false);

    expect($host->getErrorsForSchemaStatePath('data.tags'))
        ->toBe(['One or more of the given values is invalid.']);
});

it('builds no Choice constraint for blank state', function (): void {
    expect(wireToggleButtons(ToggleButtons::make('status')->options(['draft' => 'Draft']))->getValidationConstraints())->toBe([]);
});

it('throws when validating a field with no options', function (): void {
    $field = wireToggleButtons(ToggleButtons::make('status'));
    $field->state('draft');

    $field->getValidationConstraints();
})->throws(LogicException::class);

it('fails a required field with nothing selected, in either multiplicity', function (): void {
    $single = wireToggleButtons(ToggleButtons::make('status')->options(['draft' => 'Draft'])->required());
    $multiple = wireToggleButtons(ToggleButtons::make('tags')->multiple()->options(['a' => 'A'])->required());

    $validator = $this::getContainer()->get(ValidatorInterface::class);

    expect($validator->validate(null, $single->getValidationConstraints()))->toHaveCount(1)
        ->and($validator->validate([], $multiple->getValidationConstraints()))->toHaveCount(1);

    $single->state('draft');
    $multiple->state(['a']);

    expect($validator->validate($single->getRawState(), $single->getValidationConstraints()))->toHaveCount(0)
        ->and($validator->validate($multiple->getRawState(), $multiple->getValidationConstraints()))->toHaveCount(0);
});
