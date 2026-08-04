<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components;

use LogicException;
use Sylament\Forms\Components\Select;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Forms\Tests\Fixtures\Records\Article;
use Sylament\Schemas\Schema;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
