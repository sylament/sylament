<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components;

use Sylament\Forms\Components\Checkbox;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Forms\Tests\Fixtures\Records\Article;
use Sylament\Schemas\Schema;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Validator\Constraints\IsFalse;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use function Sylament\Forms\Tests\renderField;
use function Sylament\Forms\Tests\renderToHtml;
use function Sylament\Forms\Tests\wireField;

/**
 * Wire a checkbox into the same `data`-bag host `renderField()` uses and return the
 * parent schema, so a test can fill defaults or set state before reading it back or
 * rendering. `wireField()` binds no state path prefix, so the host has no property
 * for the field to write through.
 */
function checkboxSchema(Checkbox $field): Schema
{
    return Schema::make(new FieldRenderHost())->statePath('data')->components([$field]);
}

it('renders the checkbox inside the label by default', function (): void {
    $crawler = new Crawler(renderField(
        Checkbox::make('isFeatured')->label('Featured'),
    ));

    $label = $crawler->filter('label.sy-fo-field-label');
    expect($label->count())->toBe(1)
        ->and($label->filter('input.sy-fo-checkbox')->count())->toBe(1)
        ->and($label->filter('.sy-fo-field-label-content')->text())->toContain('Featured');

    $input = $crawler->filter('input.sy-fo-checkbox');
    expect($input->attr('type'))->toBe('checkbox')
        ->and($input->attr('id'))->toBe('isFeatured')
        ->and($input->attr('name'))->toBe('isFeatured')
        ->and($input->attr('data-model'))->toBe('norender|data.isFeatured');
});

it('renders the checkbox in exactly one column, chosen by inline()', function (): void {
    $inline = new Crawler(renderField(
        Checkbox::make('isFeatured')->label('Featured'),
    ));

    expect($inline->filter('input.sy-fo-checkbox')->count())->toBe(1)
        ->and($inline->filter('label.sy-fo-field-label input.sy-fo-checkbox')->count())->toBe(1)
        ->and($inline->filter('.sy-fo-field-content-col input.sy-fo-checkbox')->count())->toBe(0);

    $block = new Crawler(renderField(
        Checkbox::make('isFeatured')->label('Featured')->inline(false),
    ));

    expect($block->filter('input.sy-fo-checkbox')->count())->toBe(1)
        ->and($block->filter('label.sy-fo-field-label input.sy-fo-checkbox')->count())->toBe(0)
        ->and($block->filter('.sy-fo-field-content-col input.sy-fo-checkbox')->count())->toBe(1);
});

it('carries no value attribute so the live binding stays boolean', function (): void {
    $input = new Crawler(renderField(Checkbox::make('isFeatured')))->filter('input.sy-fo-checkbox');

    expect($input->attr('value'))->toBeNull();
});

it('hydrates a false default into the host state', function (): void {
    $field = Checkbox::make('isFeatured');
    checkboxSchema($field)->fill();

    expect($field->getDefaultState())->toBeFalse()
        ->and($field->getRawState())->toBeFalse()
        ->and($field->getState())->toBeFalse();
});

it('casts state to a non-nullable boolean', function (): void {
    $field = Checkbox::make('isFeatured');
    checkboxSchema($field)->fill();

    $field->state('1');
    expect($field->getState())->toBeTrue();

    $field->state(null);
    expect($field->getState())->toBeFalse();
});

it('renders the checked attribute from state', function (): void {
    $unchecked = Checkbox::make('isFeatured');
    $uncheckedSchema = checkboxSchema($unchecked);
    $uncheckedSchema->fill();

    expect(new Crawler(renderToHtml($uncheckedSchema))->filter('input.sy-fo-checkbox')->attr('checked'))->toBeNull();

    $checked = Checkbox::make('isFeatured')->default(true);
    $checkedSchema = checkboxSchema($checked);
    $checkedSchema->fill();

    expect(new Crawler(renderToHtml($checkedSchema))->filter('input.sy-fo-checkbox')->attr('checked'))->not->toBeNull();
});

it('still renders the control when the label is hidden', function (): void {
    $crawler = new Crawler(renderField(
        Checkbox::make('isFeatured')->label('Featured')->hiddenLabel(),
    ));

    $visibleLabel = $crawler->filter('label.sy-fo-field-label:not(.sy-fo-sr-only)');

    expect($crawler->filter('input.sy-fo-checkbox')->count())->toBe(1)
        ->and($visibleLabel->count())->toBe(1)
        ->and($visibleLabel->filter('input.sy-fo-checkbox')->count())->toBe(1)
        ->and($visibleLabel->filter('.sy-fo-field-label-content')->count())->toBe(0);
});

it('builds a boolean Type constraint', function (): void {
    $constraints = wireField(Checkbox::make('isFeatured'))->getValidationConstraints();
    $types = array_values(array_filter($constraints, fn (object $c): bool => $c instanceof Type));

    expect($types)->toHaveCount(1)
        ->and($types[0]->type)->toBe('bool');
});

it('composes the acceptance constraints', function (): void {
    $accepted = wireField(Checkbox::make('terms')->accepted())->getValidationConstraints();
    $declined = wireField(Checkbox::make('terms')->declined())->getValidationConstraints();

    expect(array_filter($accepted, fn (object $c): bool => $c instanceof IsTrue))->toHaveCount(1)
        ->and(array_filter($declined, fn (object $c): bool => $c instanceof IsFalse))->toHaveCount(1);
});

it('validates the raw state rather than the cast one', function (): void {
    $host = new FieldRenderHost();
    $host->setSchemaStateValidator($this::getContainer()->get(ValidatorInterface::class));

    $field = Checkbox::make('isFeatured');
    $schema = Schema::make($host)->statePath('data')->components([$field]);
    $host->cacheSchema('schema', $schema);

    $host->data['isFeatured'] = 'yes';
    $host->validateSchema($schema, shouldThrow: false);

    expect($field->getState())->toBeTrue()
        ->and($host->getErrorsForSchemaStatePath('data.isFeatured'))
        ->toBe(['This value should be of type bool.']);
});

it('renders required, disabled and autofocus', function (): void {
    $input = new Crawler(renderField(
        Checkbox::make('isFeatured')->required()->disabled()->autofocus(),
    ))->filter('input.sy-fo-checkbox');

    expect($input->attr('required'))->not->toBeNull()
        ->and($input->attr('disabled'))->not->toBeNull()
        ->and($input->attr('autofocus'))->not->toBeNull();
});

it('derives no requirement from a non-nullable bool column', function (): void {
    // A checkbox always dehydrates a bool, so the column accepts whatever it produces;
    // a derived requirement would mean "must be ticked" — NotBlank rejects false and
    // the browser blocks the submit on the native attribute.
    $field = Checkbox::make('featured');
    $crawler = new Crawler(renderToHtml(
        Schema::make(new FieldRenderHost())->statePath('data')->record(Article::class)->components([$field]),
    ));

    expect($field->isRequired())->toBeFalse()
        ->and($field->getValidationConstraints())->toHaveCount(1)
        ->and($crawler->filter('input.sy-fo-checkbox')->attr('required'))->toBeNull()
        ->and($crawler->filter('.sy-fo-field-label-required-mark')->count())->toBe(0);
});

it('treats false as a blank state value', function (): void {
    expect(Checkbox::make('flag')->isBlankStateValue(false))->toBeTrue()
        ->and(Checkbox::make('flag')->isBlankStateValue(true))->toBeFalse()
        ->and(Checkbox::make('flag')->isBlankStateValue(null))->toBeTrue();
});
