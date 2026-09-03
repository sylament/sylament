<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components;

use Sylament\Forms\Components\KeyValue;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Post;
use Sylament\Schemas\Schema;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use function Sylament\Forms\Tests\renderToHtml;

/*
 * The key-value field through the real Twig stack and the real state pipeline.
 * Every case here fills the schema rather than seeding the bag, because the pad
 * that makes the per-cell `data-model` paths real rides `afterStateHydrated` —
 * which only `fill()` calls.
 */

/**
 * Fill a key-value field under a `data`-bag host and render it. `null` fills the
 * field's own `default([])`, an array fills the bag it names.
 *
 * @param array<string, mixed>|null $data
 */
function renderKeyValue(KeyValue $field, ?array $data = null): Crawler
{
    $host = new FieldRenderHost();

    $schema = Schema::make($host)->statePath('data')->components([$field]);
    $host->cacheSchema('schema', $schema);
    $schema->fill($data);

    return new Crawler(renderToHtml($schema));
}

/**
 * Fill a key-value field under a validator-fed host and validate it, so a test
 * reads the constraint outcome off the field's own error messages.
 *
 * @param array<string, mixed>|null $data
 */
function validateKeyValue(KeyValue $field, ?array $data = null): KeyValue
{
    $host = new FieldRenderHost();
    $host->setSchemaStateValidator(test()->getContainer()->get(ValidatorInterface::class));

    $schema = Schema::make($host)->statePath('data')->components([$field]);
    $host->cacheSchema('schema', $schema);
    $schema->fill($data);

    $host->validateSchema($schema, shouldThrow: false);

    return $field;
}

it('renders the table shell over one padded blank row, with the key and value columns and the delete chrome', function (): void {
    $crawler = renderKeyValue(KeyValue::make('meta')->label('Meta'));

    $table = $crawler->filter('table.sy-fo-key-value-table');
    $headers = $table->filter('thead th');
    $rows = $table->filter('tbody tr');

    expect($crawler->filter('.sy-input-wrp.sy-fo-key-value'))->toHaveCount(1)
        ->and($table->attr('id'))->toBe('schema.meta')
        ->and($table->attr('aria-labelledby'))->toBe('schema.meta-label')
        ->and($crawler->filter('[id="schema.meta-label"]')->text())->toContain('Meta')
        ->and($headers)->toHaveCount(3)
        ->and($headers->eq(0)->text())->toBe('Key')
        ->and($headers->eq(1)->text())->toBe('Value')
        ->and($headers->eq(2)->filter('span.sy-sr-only')->text())->toBe('Actions')
        ->and($rows)->toHaveCount(1)
        ->and($rows->filter('td input')->eq(0)->attr('value'))->toBe('')
        ->and($rows->filter('td input')->eq(1)->attr('value'))->toBe('')
        ->and($crawler->filter('.sy-fo-key-value-add-action-ctn button')->text())->toContain('Add row');
});

it('renders one row per raw row, with the column labels, placeholders and values it was given', function (): void {
    $crawler = renderKeyValue(
        KeyValue::make('meta')
            ->keyLabel('Attribute')
            ->valueLabel('Setting')
            ->keyPlaceholder('name')
            ->valuePlaceholder('value'),
        ['meta' => [['key' => 'a', 'value' => 'one'], ['key' => 'b', 'value' => 'two']]],
    );

    $headers = $crawler->filter('thead th');
    $inputs = $crawler->filter('tbody input');

    expect($crawler->filter('tbody tr'))->toHaveCount(2)
        ->and($headers->eq(0)->text())->toBe('Attribute')
        ->and($headers->eq(1)->text())->toBe('Setting')
        ->and($inputs->eq(0)->attr('aria-label'))->toBe('Attribute')
        ->and($inputs->eq(0)->attr('placeholder'))->toBe('name')
        ->and($inputs->eq(0)->attr('value'))->toBe('a')
        ->and($inputs->eq(1)->attr('aria-label'))->toBe('Setting')
        ->and($inputs->eq(1)->attr('placeholder'))->toBe('value')
        ->and($inputs->eq(1)->attr('value'))->toBe('one')
        ->and($inputs->eq(2)->attr('value'))->toBe('b')
        ->and($inputs->eq(3)->attr('value'))->toBe('two');
});

it('carries the reorder column, the sortable chain and the per-row handle only when reorderable', function (): void {
    $crawler = renderKeyValue(KeyValue::make('meta')->reorderable(), ['meta' => [['key' => 'a', 'value' => 'one']]]);

    $headers = $crawler->filter('thead th');
    $tbody = $crawler->filter('tbody');

    expect($headers)->toHaveCount(4)
        ->and($headers->eq(0)->filter('span.sy-sr-only')->text())->toBe('Reorder')
        ->and($tbody->attr('data-controller'))->toBe('sylament--support--sortable')
        ->and($tbody->attr('data-sylament--support--sortable-animation-duration-value'))->toBe('300')
        ->and($tbody->attr('data-action'))->toBe('sylament--support--sortable:end->sylament--forms--key-value#reorder')
        ->and($crawler->filter('tbody tr')->attr('data-sortable-item'))->toBe('0')
        ->and($crawler->filter('td.sy-fo-key-value-action-col .sy-fo-key-value-table-row-sortable-handle button[data-sortable-handle]'))->toHaveCount(1);

    $plain = renderKeyValue(KeyValue::make('meta'), ['meta' => [['key' => 'a', 'value' => 'one']]]);

    expect($plain->filter('tbody')->attr('data-controller'))->toBeNull()
        ->and($plain->filter('tbody tr')->attr('data-sortable-item'))->toBeNull()
        ->and($plain->filter('[data-sortable-handle]'))->toHaveCount(0);
});

it('drops the add trigger with addable(false) and the actions column with deletable(false)', function (): void {
    $addable = renderKeyValue(KeyValue::make('meta')->addable(false));

    expect($addable->filter('.sy-fo-key-value-add-action-ctn'))->toHaveCount(0)
        ->and($addable->filter('tbody tr'))->toHaveCount(1)
        ->and($addable->filter('thead th'))->toHaveCount(3);

    $deletable = renderKeyValue(KeyValue::make('meta')->deletable(false));

    expect($deletable->filter('thead th'))->toHaveCount(2)
        ->and($deletable->filter('[data-live-name-param="delete"]'))->toHaveCount(0)
        ->and($deletable->filter('.sy-fo-key-value-add-action-ctn'))->toHaveCount(1);
});

it('drops both chrome columns and the add trigger when disabled, and disables every cell', function (): void {
    $crawler = renderKeyValue(
        KeyValue::make('meta')->reorderable()->disabled(),
        ['meta' => [['key' => 'a', 'value' => 'one']]],
    );

    $inputs = $crawler->filter('tbody input');

    expect($crawler->filter('thead th'))->toHaveCount(2)
        ->and($crawler->filter('tbody')->attr('data-controller'))->toBeNull()
        ->and($crawler->filter('.sy-fo-key-value-add-action-ctn'))->toHaveCount(0)
        ->and($crawler->filter('.sy-input-wrp')->attr('class'))->toContain('sy-disabled')
        ->and($crawler->filter('tbody input[disabled]'))->toHaveCount(2)
        ->and($inputs->eq(0)->attr('data-model'))->toBe('norender|data.meta.0.key')
        ->and($inputs->eq(1)->attr('data-model'))->toBe('norender|data.meta.0.value');
});

it('disables one cell at a time under editableKeys(false) and editableValues(false), keeping its binding', function (): void {
    $keys = renderKeyValue(KeyValue::make('meta')->editableKeys(false), ['meta' => [['key' => 'a', 'value' => 'one']]]);

    expect($keys->filter('tbody input[disabled]'))->toHaveCount(1)
        ->and($keys->filter('tbody input')->eq(0)->attr('disabled'))->toBe('')
        ->and($keys->filter('tbody input')->eq(0)->attr('data-model'))->toBe('norender|data.meta.0.key')
        ->and($keys->filter('tbody input')->eq(1)->attr('disabled'))->toBeNull();

    $values = renderKeyValue(KeyValue::make('meta')->editableValues(false), ['meta' => [['key' => 'a', 'value' => 'one']]]);

    expect($values->filter('tbody input[disabled]'))->toHaveCount(1)
        ->and($values->filter('tbody input')->eq(0)->attr('disabled'))->toBeNull()
        ->and($values->filter('tbody input')->eq(1)->attr('data-model'))->toBe('norender|data.meta.0.value');
});

it("binds every cell at the field's own liveness, over that cell's row index", function (KeyValue $field, string $prefix): void {
    $crawler = renderKeyValue($field, ['meta' => [['key' => 'a', 'value' => 'one'], ['key' => 'b', 'value' => 'two']]]);

    $inputs = $crawler->filter('tbody input');

    expect($inputs->eq(0)->attr('data-model'))->toBe($prefix . 'data.meta.0.key')
        ->and($inputs->eq(1)->attr('data-model'))->toBe($prefix . 'data.meta.0.value')
        ->and($inputs->eq(2)->attr('data-model'))->toBe($prefix . 'data.meta.1.key')
        ->and($inputs->eq(3)->attr('data-model'))->toBe($prefix . 'data.meta.1.value');
})->with([
    'plain' => [fn (): KeyValue => KeyValue::make('meta'), 'norender|'],
    'live' => [fn (): KeyValue => KeyValue::make('meta')->live(), ''],
    'debounced' => [fn (): KeyValue => KeyValue::make('meta')->live(debounce: 300), 'debounce(300)|'],
    'lazy' => [fn (): KeyValue => KeyValue::make('meta')->lazy(), 'on(change)|'],
]);

it('carries the controller on the table container whether or not the field is reorderable or disabled', function (KeyValue $field): void {
    $container = renderKeyValue($field)->filter('.sy-fo-key-value-table-ctn');

    expect($container->attr('data-controller'))->toBe('sylament--forms--key-value')
        ->and($container->attr('data-sylament--forms--key-value-key-value'))->toBe('schema.meta');
})->with([
    'plain' => fn (): KeyValue => KeyValue::make('meta'),
    'reorderable' => fn (): KeyValue => KeyValue::make('meta')->reorderable(),
    'disabled' => fn (): KeyValue => KeyValue::make('meta')->disabled(),
]);

it('points the table at the heading the wrapper minted, whether the label is visible or screen-reader only', function (): void {
    $visible = renderKeyValue(KeyValue::make('meta')->label('Meta'));

    expect($visible->filter('table')->attr('aria-labelledby'))->toBe('schema.meta-label')
        ->and($visible->filter('div[id="schema.meta-label"]')->text())->toContain('Meta');

    $hidden = renderKeyValue(KeyValue::make('meta')->label('Meta')->hiddenLabel());

    expect($hidden->filter('table')->attr('aria-labelledby'))->toBe('schema.meta-label')
        ->and($hidden->filter('div[id="schema.meta-label"]')->attr('class'))->toContain('sy-sr-only');
});

it("binds each row's delete trigger to that row's index", function (): void {
    $crawler = renderKeyValue(KeyValue::make('meta'), ['meta' => [['key' => 'a', 'value' => 'one'], ['key' => 'b', 'value' => 'two']]]);

    $triggers = $crawler->filter('[data-live-name-param="delete"]');

    expect($triggers)->toHaveCount(2)
        ->and($triggers->eq(0)->attr('data-live-arguments-param'))->toBe('{"row":0}')
        ->and($triggers->eq(1)->attr('data-live-arguments-param'))->toBe('{"row":1}')
        ->and($triggers->eq(0)->attr('data-live-context-param'))->toBe('{"schemaComponent":"schema.meta"}');
});

it('answers the map its rows encode, dropping the blank row', function (): void {
    $host = new FieldRenderHost();

    $schema = Schema::make($host)->statePath('data')->components([KeyValue::make('meta')]);
    $host->cacheSchema('schema', $schema);
    $schema->fill(['meta' => ['a' => 'one']]);

    expect($host->data['meta'])->toBe([['key' => 'a', 'value' => 'one']]);

    $host->data['meta'][] = ['key' => '', 'value' => ''];

    expect($schema->getState()['meta'])->toBe(['a' => 'one']);
});

it('fails required() on a bag holding only the blank row, and passes on one keyed row', function (): void {
    expect(validateKeyValue(KeyValue::make('meta')->required())->getErrorMessages())->not->toBeEmpty();

    $filled = validateKeyValue(KeyValue::make('meta')->required(), ['meta' => ['a' => 'one']]);

    expect($filled->getErrorMessages())->toBeEmpty();
});

it('counts real rows rather than the padding under a Count constraint', function (): void {
    // The raw bag holds the blank row here, so a Count reading it would see one.
    $padded = validateKeyValue(KeyValue::make('meta')->constraint(new Count(min: 1)));

    expect($padded->getErrorMessages())->not->toBeEmpty();

    $one = validateKeyValue(KeyValue::make('meta')->constraint(new Count(min: 1)), ['meta' => ['a' => 'one']]);

    expect($one->getErrorMessages())->toBeEmpty();

    $two = validateKeyValue(KeyValue::make('meta')->constraint(new Count(min: 2)), ['meta' => ['a' => 'one']]);

    expect($two->getErrorMessages())->not->toBeEmpty();
});

it('fills its rows off a record JSON column and writes the map back', function (): void {
    $post = new Post();
    $post->setTitle('t');
    $post->setMeta(['colour' => 'red']);

    $host = new FieldRenderHost();
    $host->setSchemaStateValidator($this::getContainer()->get(ValidatorInterface::class));

    $schema = Schema::make($host)->statePath('data')->record($post)->components([KeyValue::make('meta')]);
    $host->cacheSchema('schema', $schema);
    $schema->getComponents();

    $schema->fill($post);

    expect($host->data['meta'])->toBe([['key' => 'colour', 'value' => 'red']]);

    $host->data['meta'][] = ['key' => 'size', 'value' => 'L'];
    $host->data['meta'][] = ['key' => '', 'value' => ''];

    expect($schema->getData())->toBe($post)
        ->and($post->getMeta())->toBe(['colour' => 'red', 'size' => 'L']);
});

it('pads an empty record column with the blank row and writes an empty map back', function (): void {
    $post = new Post();
    $post->setTitle('t');

    $host = new FieldRenderHost();
    $host->setSchemaStateValidator($this::getContainer()->get(ValidatorInterface::class));

    $schema = Schema::make($host)->statePath('data')->record($post)->components([KeyValue::make('meta')]);
    $host->cacheSchema('schema', $schema);
    $schema->getComponents();

    $schema->fill($post);

    expect($host->data['meta'])->toBe([['key' => '', 'value' => '']])
        ->and($schema->getData())->toBe($post)
        ->and($post->getMeta())->toBe([]);
});
