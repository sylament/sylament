<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Actions\Testing\TestAction;
use Sylament\Forms\Components\KeyValue;
use Sylament\Schemas\Exceptions\ValidationException;
use Sylament\Tests\Fixtures\Forms\KeyValueRowsHost;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Schemas\RecordMappingHost;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Validator\Constraints\Count;

describe('rendering', function (): void {
    it('renders one row per pair with both cells, under the field label', function (): void {
        $crawler = $this->renderSchemaComponent(KeyValue::make('pairs'), ['pairs' => [['key' => 'colour', 'value' => 'red'], ['key' => 'size', 'value' => 'L']]]);
        $table = $crawler->filter('table.sy-fo-key-value-table');

        expect($crawler->filter('#' . str_replace('.', '\.', (string) $table->attr('aria-labelledby')))->text())->toBe('Pairs')
            ->and($table->filter('thead th:not(.sy-fo-key-value-action-col)')->each(static fn (Crawler $heading): string => $heading->text()))->toBe(['Key', 'Value'])
            ->and($table->filter('tbody tr')->each(static fn (Crawler $row): array => [
                $row->filter('input[aria-label="Key"]')->attr('value'),
                $row->filter('input[aria-label="Value"]')->attr('value'),
            ]))->toBe([['colour', 'red'], ['size', 'L']])
            ->and($table->filter('tbody input')->each(static fn (Crawler $input): string => $input->attr('data-model') ?? ''))
            ->toBe(['norender|data.pairs.0.key', 'norender|data.pairs.0.value', 'norender|data.pairs.1.key', 'norender|data.pairs.1.value']);
    });

    it('renders the chrome its flags offer', function (Closure $field, array $chromeHeadings, int $handles, int $deleteButtons, array $addLinks): void {
        $crawler = $this->renderSchemaComponent($field(), ['pairs' => [['key' => 'colour', 'value' => 'red'], ['key' => 'size', 'value' => 'L']]]);

        expect($crawler->filter('thead th.sy-fo-key-value-action-col')->each(static fn (Crawler $heading): string => $heading->text()))->toBe($chromeHeadings)
            ->and($crawler->filter('tbody button' . SupportSelectors::SORTABLE_HANDLE))->toHaveCount($handles)
            ->and($crawler->filter('tbody button[aria-label="Delete row"]'))->toHaveCount($deleteButtons)
            ->and($crawler->filter('.sy-fo-key-value-add-action-ctn button')->each(static fn (Crawler $link): string => $link->text()))->toBe($addLinks);
    })->with([
        'by default' => [fn (): KeyValue => KeyValue::make('pairs'), ['Actions'], 0, 2, ['Add row']],
        'reorderable()' => [fn (): KeyValue => KeyValue::make('pairs')->reorderable(), ['Reorder', 'Actions'], 2, 2, ['Add row']],
        'addable(false)' => [fn (): KeyValue => KeyValue::make('pairs')->addable(false), ['Actions'], 0, 2, []],
        'deletable(false)' => [fn (): KeyValue => KeyValue::make('pairs')->deletable(false), [], 0, 0, ['Add row']],
    ]);

    it('labels the columns and their cells and places the placeholders', function (): void {
        $row = ['pairs' => [['key' => 'colour', 'value' => 'red']]];
        $labelled = $this->renderSchemaComponent(KeyValue::make('pairs')->keyLabel('Property')->valueLabel('Setting')->keyPlaceholder('e.g. colour')->valuePlaceholder('e.g. red'), $row);
        $plain = $this->renderSchemaComponent(KeyValue::make('pairs'), $row);

        expect($labelled->filter('thead th:not(.sy-fo-key-value-action-col)')->each(static fn (Crawler $heading): string => $heading->text()))->toBe(['Property', 'Setting'])
            ->and($labelled->filter('tbody input')->each(static fn (Crawler $input): array => [$input->attr('aria-label'), $input->attr('placeholder')]))
            ->toBe([['Property', 'e.g. colour'], ['Setting', 'e.g. red']])
            ->and($plain->filter('tbody input'))->toHaveCount(2)
            ->and($plain->filter('tbody input[placeholder]'))->toHaveCount(0);
    });

    it('disables a column of cells and keeps their binding', function (Closure $field, array $disabled): void {
        $inputs = $this->renderSchemaComponent($field(), ['pairs' => [['key' => 'colour', 'value' => 'red']]])->filter('tbody input');

        expect($inputs->each(static fn (Crawler $input): array => [$input->attr('aria-label'), $input->attr('disabled') !== null, $input->attr('data-model')]))->toBe([
            ['Key', $disabled[0], 'norender|data.pairs.0.key'],
            ['Value', $disabled[1], 'norender|data.pairs.0.value'],
        ]);
    })->with([
        'editableKeys(false)' => [fn (): KeyValue => KeyValue::make('pairs')->editableKeys(false), [true, false]],
        'editableValues(false)' => [fn (): KeyValue => KeyValue::make('pairs')->editableValues(false), [false, true]],
        'disabled()' => [fn (): KeyValue => KeyValue::make('pairs')->disabled(), [true, true]],
    ]);

    it('names the delete and reorder buttons by their labels', function (): void {
        $crawler = $this->renderSchemaComponent(
            KeyValue::make('pairs')->reorderable()->deleteActionLabel('Remove pair')->reorderActionLabel('Move pair'),
            ['pairs' => [['key' => 'colour', 'value' => 'red']]],
        );

        expect($crawler)->toContainButtons('tbody button', ['Move pair', 'Remove pair']);
    });

    it('wires its controller and binds the end of a drag to its reorder action', function (): void {
        $crawler = $this->renderSchemaComponent(KeyValue::make('pairs')->reorderable(), ['pairs' => [['key' => 'colour', 'value' => 'red'], ['key' => 'size', 'value' => 'L']]]);
        $container = $crawler->filter('[data-controller~="sylament--forms--key-value"]');

        expect($container->attr('data-sylament--forms--key-value-key-value'))->toBe('schema.pairs')
            ->and($container->filter('table.sy-fo-key-value-table'))->toHaveCount(1)
            ->and($container->filter('tbody')->attr('data-action'))->toBe('sylament--support--sortable:end->sylament--forms--key-value#reorder')
            ->and($container->filter('tbody tr')->each(static fn (Crawler $row): string => $row->attr('data-sortable-item') ?? ''))->toBe(['0', '1'])
            ->and($container->filter('tbody button' . SupportSelectors::SORTABLE_HANDLE))->toHaveCount(2)
            ->and($container->filter('tbody button' . SupportSelectors::SORTABLE_HANDLE . '[data-action]'))->toHaveCount(0);
    });
});

describe('state', function (): void {
    it('rests on one blank row that reads as an empty map, and takes the first keystroke into it', function (): void {
        $tester = $this->createLiveComponent(KeyValueRowsHost::class)->assertFormSet(['pairs' => []]);

        expect($tester->component()->data['pairs'])->toBe([['key' => '', 'value' => '']])
            ->and($tester->render()->crawler()->filter('input[data-model$="data.pairs.0.key"]')->attr('value'))->toBe('');

        $tester->set('data.pairs.0.key', 'colour');

        expect($tester->component()->data['pairs'])->toBe([['key' => 'colour', 'value' => '']])
            ->and($tester->render()->crawler()->filter('input[data-model$="data.pairs.0.key"]')->attr('value'))->toBe('colour');
    });

    it('pads an empty fill and normalises a ragged one into a list of pairs', function (array $pairs, array $rows): void {
        $host = new RecordMappingHost();
        $host->mapping(KeyValue::make('pairs'))->fill(['pairs' => $pairs]);

        expect($host->data['pairs'])->toBe($rows);
    })->with([
        'an empty list' => [[], [['key' => '', 'value' => '']]],
        'rows missing a cell, keyed by name' => [
            ['first' => ['key' => 'colour'], 'second' => ['value' => 'L', 'stray' => true]],
            [['key' => 'colour', 'value' => null], ['key' => null, 'value' => 'L']],
        ],
    ]);
});

describe('add', function (): void {
    it('appends one blank row after the rows the field holds', function (): void {
        $tester = $this->createLiveComponent(KeyValueRowsHost::class, ['record' => ['pairs' => ['colour' => 'red']]])
            ->callAction(TestAction::make('add')->schemaComponent('pairs'));

        expect($tester->component()->data['pairs'])->toBe([['key' => 'colour', 'value' => 'red'], ['key' => '', 'value' => '']])
            ->and($tester->render()->crawler()->filter('input[data-model*="data.pairs."][aria-label="Key"]'))->toHaveCount(2);
    });

    it('relabels the add link, or lets addAction() modify it', function (Closure $field, string $label): void {
        expect($this->renderSchemaComponent($field())->filter('.sy-fo-key-value-add-action-ctn button')->text())->toBe($label);
    })->with([
        'addActionLabel()' => [fn (): KeyValue => KeyValue::make('pairs')->addActionLabel('Add pair'), 'Add pair'],
        'addAction()' => [fn (): KeyValue => KeyValue::make('pairs')->addAction(static fn (Action $action): Action => $action->label('Append')), 'Append'],
    ]);

    it('offers the add link a flag allows when the flag changes between requests', function (): void {
        $tester = $this->createLiveComponent(KeyValueRowsHost::class);
        $addLinks = static fn (): Crawler => $tester->render()->crawler()->filter('.sy-fo-key-value-add-action-ctn button');

        expect($addLinks())->toHaveCount(1);

        $tester->set('data.allowAdd', true);

        expect($addLinks())->toHaveCount(2);

        $tester->callAction(TestAction::make('add')->schemaComponent('gated'));

        expect($tester->component()->data['gated'])->toBe([['key' => '', 'value' => ''], ['key' => '', 'value' => '']]);
    });
});

describe('delete', function (): void {
    it('removes the row its trigger names and keeps the list a list', function (int|string $row): void {
        $tester = $this->createLiveComponent(KeyValueRowsHost::class, ['record' => ['pairs' => ['colour' => 'red', 'size' => 'L', 'fit' => 'slim']]])
            ->callAction(TestAction::make('delete')->schemaComponent('pairs'), arguments: ['row' => $row]);

        expect($tester->component()->data['pairs'])->toBe([['key' => 'colour', 'value' => 'red'], ['key' => 'fit', 'value' => 'slim']]);
    })->with([
        'an index' => [1],
        'the string a JSON round trip makes of one' => ['1'],
    ]);

    it('deletes the last row down to the blank row', function (): void {
        $tester = $this->createLiveComponent(KeyValueRowsHost::class, ['record' => ['pairs' => ['colour' => 'red']]])
            ->callAction(TestAction::make('delete')->schemaComponent('pairs'), arguments: ['row' => 0]);

        expect($tester->component()->data['pairs'])->toBe([['key' => '', 'value' => '']])
            ->and($tester->render()->crawler()->filter('input[data-model*="data.pairs."][aria-label="Key"]'))->toHaveCount(1);
    });

    it('keeps a value typed into a later row in the delete request itself, moving it up with its row', function (): void {
        $tester = $this->createLiveComponent(KeyValueRowsHost::class, ['record' => ['pairs' => ['colour' => 'red', 'size' => 'L']]])
            ->submitForm(['data' => ['pairs' => [1 => ['value' => 'XL']]]], 'deleteFirstPair');

        expect($tester->component()->data['pairs'])->toBe([['key' => 'size', 'value' => 'XL']]);
    });

    it('refuses a row argument that names no row', function (string|int $row): void {
        $tester = $this->createLiveComponent(KeyValueRowsHost::class, ['record' => ['pairs' => ['colour' => 'red']]]);

        expect(fn () => $tester->callAction(TestAction::make('delete')->schemaComponent('pairs'), arguments: ['row' => $row]))
            ->toThrow(LogicException::class, 'The key-value delete action was mounted without a "row" argument naming the row.');
    })->with([
        'an index past the rows' => [9],
        'a string that is no index' => ['colour'],
    ]);
});

describe('reorder', function (): void {
    it('permutes the rows by the order the drag names, never losing or inventing a row', function (array $items, array $keys): void {
        $tester = $this->createLiveComponent(KeyValueRowsHost::class, ['record' => ['pairs' => ['colour' => 'red', 'size' => 'L', 'fit' => 'slim']]])
            ->callAction(TestAction::make('reorder')->schemaComponent('pairs'), arguments: ['items' => $items]);

        expect(array_column($tester->component()->data['pairs'], 'key'))->toBe($keys)
            ->and(array_is_list($tester->component()->data['pairs']))->toBeTrue();
    })->with([
        'every index' => [[2, 0, 1], ['fit', 'colour', 'size']],
        'indexes as the strings a JSON round trip makes' => [['2', '0', '1'], ['fit', 'colour', 'size']],
        'an index left out, which follows in its old order' => [[2], ['fit', 'colour', 'size']],
        'an index the rows do not hold' => [[1, 7, 0], ['size', 'colour', 'fit']],
    ]);

    it('keeps a value typed into a row in the drag request itself, moving it with its row', function (): void {
        $tester = $this->createLiveComponent(KeyValueRowsHost::class, ['record' => ['pairs' => ['colour' => 'red', 'size' => 'L']]])
            ->submitForm(['data' => ['pairs' => [0 => ['value' => 'crimson']]]], 'swapPairs');

        expect($tester->component()->data['pairs'])->toBe([['key' => 'size', 'value' => 'L'], ['key' => 'colour', 'value' => 'crimson']]);
    });

    it('refuses an order that is not a list', function (mixed $items): void {
        $tester = $this->createLiveComponent(KeyValueRowsHost::class, ['record' => ['pairs' => ['colour' => 'red', 'size' => 'L']]]);

        expect(fn () => $tester->callAction(TestAction::make('reorder')->schemaComponent('pairs'), arguments: ['items' => $items]))
            ->toThrow(LogicException::class, 'The key-value reorder action was mounted without an "items" argument listing the row indexes in their new order.');
    })->with([
        'a string' => ['1,0'],
        'a map' => [['first' => 1, 'second' => 0]],
    ]);
});

describe('hooks', function (): void {
    it('runs afterStateUpdated once per structural action, handing it the map', function (): void {
        $tester = $this->createLiveComponent(KeyValueRowsHost::class, ['record' => ['pairs' => ['colour' => 'red', 'size' => 'L']]]);
        $hooks = fn (): array => $this::getContainer()->get(HookLog::class)->hooks;

        $tester->callAction(TestAction::make('add')->schemaComponent('pairs'));

        expect($hooks())->toBe(['pairs: {"colour":"red","size":"L"}']);

        $tester->callAction(TestAction::make('reorder')->schemaComponent('pairs'), arguments: ['items' => [1, 0]]);

        expect($hooks())->toBe(['pairs: {"size":"L","colour":"red"}']);

        $tester->callAction(TestAction::make('delete')->schemaComponent('pairs'), arguments: ['row' => 0]);

        expect($hooks())->toBe(['pairs: {"colour":"red"}']);
    });
});

describe('validation', function (): void {
    it('validates the map, not the padded row list', function (array $pairs, ?string $message): void {
        $host = $this->validatingRecordMappingHost();
        $schema = $host->mapping(KeyValue::make('pairs')->required()->constraint(new Count(min: 2)));
        $host->data = ['pairs' => $pairs];

        if ($message === null) {
            expect($host->validateSchema($schema))->toBeNull();

            return;
        }

        expect(fn () => $host->validateSchema($schema))->toThrow(function (ValidationException $exception) use ($message): void {
            expect($exception->getErrors())->toHaveKey('data.pairs')
                ->and($exception->getErrors()['data.pairs'])->toContain($message);
        });
    })->with([
        'only the blank row' => [[['key' => '', 'value' => '']], 'This value should not be blank.'],
        'one real row beside the blank one' => [[['key' => 'colour', 'value' => 'red'], ['key' => '', 'value' => '']], 'This collection should contain 2 elements or more.'],
        'two real rows beside the blank one' => [[['key' => 'colour', 'value' => 'red'], ['key' => 'size', 'value' => 'L'], ['key' => '', 'value' => '']], null],
    ]);
});

describe('disabled()', function (): void {
    it('offers no structural action and leaves the rows alone under a hand-made call to one', function (): void {
        $crawler = $this->renderSchemaComponent(KeyValue::make('pairs')->reorderable()->disabled(), ['pairs' => [['key' => 'colour', 'value' => 'red']]]);

        expect($crawler->filter('tbody input'))->toHaveCount(2)
            ->and($crawler->filter('thead th.sy-fo-key-value-action-col'))->toHaveCount(0)
            ->and($crawler->filter('button'))->toHaveCount(0);

        $rows = [['key' => 'colour', 'value' => 'red'], ['key' => 'size', 'value' => 'L']];
        $tester = $this->createLiveComponent(KeyValueRowsHost::class, ['record' => ['locked' => ['colour' => 'red', 'size' => 'L']]]);

        $tester->mountAction(TestAction::make('add')->schemaComponent('locked'));
        $tester->mountAction(TestAction::make('delete')->schemaComponent('locked'), arguments: ['row' => 0]);
        $tester->mountAction(TestAction::make('reorder')->schemaComponent('locked'), arguments: ['items' => [1, 0]]);

        expect($tester->component()->data['locked'])->toBe($rows)
            ->and($tester->component()->getMountedActions())->toBe([]);
    });
});
