<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components;

use LogicException;
use Sylament\Forms\Components\Select;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\Casts\OptionsArrayStateCast;
use Sylament\Schemas\State\Casts\OptionStateCast;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use function Sylament\Forms\Tests\renderToHtml;

/**
 * Bind a select into a `data`-bag-bound parent schema, as `SelectTest`'s
 * `wireSelect()` does — repeated here rather than shared so this file runs on
 * its own.
 */
function wireMultiSelect(Select $field): Select
{
    Schema::make(new FieldRenderHost())->statePath('data')->components([$field])->getComponents();

    return $field;
}

/**
 * Render a multi-value select over a host whose `data` bag already carries the
 * raw state a payload would have written, so the template's selected-state
 * comparison runs against a real filled field rather than a hand-set property.
 *
 * @param array<mixed> $state
 */
function renderSelectWithState(Select $field, array $state): Crawler
{
    $host = new FieldRenderHost();
    $host->data[$field->getName()] = $state;

    return new Crawler(renderToHtml(
        Schema::make($host)->statePath('data')->components([$field]),
    ));
}

it('is not multiple by default and turns multiple on', function (): void {
    expect(Select::make('tags')->isMultiple())->toBeFalse()
        ->and(Select::make('tags')->multiple()->isMultiple())->toBeTrue()
        ->and(Select::make('tags')->multiple(false)->isMultiple())->toBeFalse()
        ->and(Select::make('tags')->multiple(fn (): bool => true)->isMultiple())->toBeTrue();
});

it('selects the options array cast when multiple', function (): void {
    $casts = Select::make('tags')->multiple()->getDefaultStateCasts();

    expect($casts)->toHaveCount(1)
        ->and($casts[0])->toBeInstanceOf(OptionsArrayStateCast::class);
});

it('keeps the single-option cast when not multiple', function (): void {
    expect(Select::make('tags')->getDefaultStateCasts()[0])->toBeInstanceOf(OptionStateCast::class);
});

it('lets a custom state cast win over the multiple cast', function (): void {
    $field = Select::make('tags')->multiple()->stateCast(new OptionStateCast());

    expect($field->getDefaultStateCasts())->toBe([]);
});

it('casts multi-option state through the options array cast', function (): void {
    $field = wireMultiSelect(Select::make('tags')->multiple()->options([1 => 'One', 2 => 'Two']));
    $field->state(['1', 2]);

    expect($field->getRawState())->toBe(['1', '2'])
        ->and($field->getState())->toBe([1, 2]);
});

it('builds a multiple Choice constraint over the enabled options', function (): void {
    $field = wireMultiSelect(Select::make('tags')->multiple()->options(['draft' => 'Draft', 'published' => 'Published']));
    $field->state(['draft']);

    $choice = array_values(array_filter(
        $field->getValidationConstraints(),
        fn (object $c): bool => $c instanceof Choice,
    ));

    expect($choice)->toHaveCount(1)
        ->and($choice[0]->choices)->toBe(['draft', 'published'])
        ->and($choice[0]->multiple)->toBeTrue()
        ->and($choice[0]->strict)->toBeTrue();
});

it('builds no Choice constraint for an empty multiple selection', function (): void {
    $field = wireMultiSelect(Select::make('tags')->multiple()->options(['draft' => 'Draft']));
    $field->state([]);

    expect($field->getValidationConstraints())->toBe([]);
});

it('rejects a tampered element of a multiple selection server-side', function (): void {
    $field = wireMultiSelect(Select::make('tags')->multiple()->options(['draft' => 'Draft', 'published' => 'Published']));
    $field->state(['draft', 'published']);

    $validator = $this::getContainer()->get(ValidatorInterface::class);
    $constraints = $field->getValidationConstraints();

    expect($validator->validate($field->getRawState(), $constraints))->toHaveCount(0)
        ->and($validator->validate(['draft', 'tampered'], $constraints))->toHaveCount(1);
});

it('fails a non-array raw state instead of crashing the Choice validator', function (): void {
    $field = wireMultiSelect(Select::make('tags')->multiple()->options(['draft' => 'Draft', 'published' => 'Published']));
    $field->rawState('draft');

    $constraints = $field->getValidationConstraints();
    $validator = $this::getContainer()->get(ValidatorInterface::class);

    expect($constraints)->toHaveCount(1)
        ->and($constraints[0])->toBeInstanceOf(Type::class)
        ->and($constraints[0]->type)->toBe('array')
        ->and($validator->validate($field->getRawState(), $constraints))->toHaveCount(1);
});

it('throws when validating a multiple select with no options', function (): void {
    $field = wireMultiSelect(Select::make('tags')->multiple());
    $field->state(['draft']);

    $field->getValidationConstraints();
})->throws(LogicException::class);

it('renders the multiple attribute and marks every selected option', function (): void {
    $crawler = renderSelectWithState(
        Select::make('tags')->multiple()->options([
            'draft' => 'Draft',
            'published' => 'Published',
            'archived' => 'Archived',
        ]),
        ['draft', 'archived'],
    );

    $select = $crawler->filter('select.sy-fo-select');
    expect($select->attr('multiple'))->not->toBeNull();

    $options = $select->filter('option');
    expect($options->eq(1)->attr('selected'))->not->toBeNull()
        ->and($options->eq(2)->attr('selected'))->toBeNull()
        ->and($options->eq(3)->attr('selected'))->not->toBeNull();
});

it('renders no multiple attribute on a single select and marks its one selected option', function (): void {
    $host = new FieldRenderHost();
    $host->data['status'] = 'published';

    $crawler = new Crawler(renderToHtml(
        Schema::make($host)->statePath('data')->components([
            Select::make('status')->options(['draft' => 'Draft', 'published' => 'Published']),
        ]),
    ));

    $select = $crawler->filter('select.sy-fo-select');
    $options = $select->filter('option');

    expect($select->attr('multiple'))->toBeNull()
        ->and($options->eq(1)->attr('selected'))->toBeNull()
        ->and($options->eq(2)->attr('selected'))->not->toBeNull();
});

it('marks a selected option inside a group', function (): void {
    $crawler = renderSelectWithState(
        Select::make('tags')->multiple()->options([
            'Live' => ['published' => 'Published'],
            'Not live' => ['draft' => 'Draft'],
        ]),
        ['draft'],
    );

    $groups = $crawler->filter('select.sy-fo-select optgroup');

    expect($groups->eq(0)->filter('option')->attr('selected'))->toBeNull()
        ->and($groups->eq(1)->filter('option')->attr('selected'))->not->toBeNull();
});
