<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Actions\Testing\TestAction;
use Sylament\Forms\Components\DateTimePicker;
use Sylament\Forms\Components\Hidden;
use Sylament\Forms\Components\Repeater;
use Sylament\Forms\Components\Repeater\TableColumn;
use Sylament\Forms\Components\Select;
use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Components\Actions;
use Sylament\Schemas\Components\Group;
use Sylament\Schemas\Exceptions\ValidationException;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\Casts\ObjectStateCast;
use Sylament\Support\Enums\Alignment;
use Sylament\Support\Enums\VerticalAlignment;
use Sylament\Tests\Fixtures\Entity\Atelier;
use Sylament\Tests\Fixtures\Entity\AtelierLease;
use Sylament\Tests\Fixtures\Entity\BenchPot;
use Sylament\Tests\Fixtures\Entity\JarSeal;
use Sylament\Tests\Fixtures\Entity\Larder;
use Sylament\Tests\Fixtures\Entity\LarderBin;
use Sylament\Tests\Fixtures\Entity\LarderBinNote;
use Sylament\Tests\Fixtures\Entity\LarderCrock;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Entity\NurseryBench;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Entity\VettedBinder;
use Sylament\Tests\Fixtures\Entity\VettedBinderSheet;
use Sylament\Tests\Fixtures\Forms\Record\LineItem;
use Sylament\Tests\Fixtures\Forms\Record\LinePart;
use Sylament\Tests\Fixtures\Forms\Record\LineSheet;
use Sylament\Tests\Fixtures\Forms\Record\PackedLineItem;
use Sylament\Tests\Fixtures\Forms\RepeaterHost;
use Sylament\Tests\Fixtures\Forms\Selectors as FormsSelectors;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Schemas\Record\Anthology;
use Sylament\Tests\Fixtures\Schemas\Record\Manuscript;
use Sylament\Tests\Fixtures\Schemas\RecordMappingHost;
use Sylament\Tests\Fixtures\Schemas\StringBackedLetter;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Symfony\Component\DomCrawler\Crawler;
use Twig\Error\RuntimeError;
use Twig\Markup;

use function Symfony\Component\Translation\t;

describe('make() and components()', function (): void {
    it('renders one item per item in the state, each holding its own fields', function (): void {
        $crawler = $this->renderSchemaComponent(
            Repeater::make('lines')->components([TextInput::make('name')]),
            ['lines' => ['a' => ['name' => 'Ada'], 'b' => ['name' => 'Grace']]],
        );

        expect($crawler->filter(FormsSelectors::REPEATER_ITEM)->each(static fn (Crawler $item): string => $item->attr('data-item-key') ?? ''))->toBe(['a', 'b'])
            ->and($crawler->filter(FormsSelectors::repeaterItem('a') . ' input')->attr('value'))->toBe('Ada')
            ->and($crawler->filter(FormsSelectors::repeaterItem('b') . ' input')->attr('value'))->toBe('Grace');
    });
});

describe('item schemas', function (): void {
    it('gives each item its own field ids', function (): void {
        $crawler = $this->renderSchemaComponent(
            Repeater::make('lines')->components([TextInput::make('name')]),
            ['lines' => [['name' => 'Ada'], ['name' => 'Grace']]],
        );

        expect($crawler->filter(FormsSelectors::REPEATER_ITEM . ' input')->each(static fn (Crawler $input): string => $input->attr('id') ?? ''))->toBe(['schema.lines.0.name', 'schema.lines.1.name'])
            ->and($crawler->filter(FormsSelectors::REPEATER_ITEM . ' label')->each(static fn (Crawler $label): string => $label->attr('for') ?? ''))->toBe(['schema.lines.0.name', 'schema.lines.1.name'])
            ->and($crawler->filter(FormsSelectors::REPEATER_ITEM)->each(static fn (Crawler $item): string => $item->attr('id') ?? ''))->toBe(['schema.lines.0', 'schema.lines.1']);
    });

    it('gives each item its own action instances', function (): void {
        $host = new RecordMappingHost();
        $repeater = $host->mapping(Repeater::make('lines')->components([TextInput::make('name'), Actions::make([Action::make('ping')])]))->getComponent('lines');
        $host->data = ['lines' => ['a' => ['name' => 'Ada'], 'b' => ['name' => 'Grace']]];
        $items = $repeater->getItems();

        expect($items['a']->getAction('ping'))->toBeInstanceOf(Action::class)
            ->and($items['a']->getAction('ping'))->not->toBe($items['b']->getAction('ping'))
            ->and($items['b']->getAction('ping'))->not->toBe($repeater->getChildSchema()?->getAction('ping'));

        $this->createLiveComponent(RepeaterHost::class, ['data' => ['lines' => ['a' => ['name' => 'Ada'], 'b' => ['name' => 'Grace']]]])
            ->callAction(TestAction::make('ping')->schemaComponent('lines.b'));

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['ping {"name":"Grace"}']);
    });
});

describe('the item pipeline', function (): void {
    it('keys items by uuid until dehydration', function (): void {
        $host = $this->validatingRecordMappingHost();
        $schema = $host->mapping($repeater = Repeater::make('lines')->components([TextInput::make('name')->maxLength(5)]));

        $schema->fill(['lines' => [['name' => 'Ada'], ['name' => 'Grace']]]);

        $firstKeys = array_keys($host->data['lines']);
        $schema->fill(['lines' => [['name' => 'Ada'], ['name' => 'Grace']]]);
        $secondKeys = array_keys($host->data['lines']);

        expect(array_intersect($firstKeys, $secondKeys))->toBe([])
            ->and(array_keys($repeater->getState()))->toBe($secondKeys)
            ->and($schema->getState())->toBe(['lines' => [['name' => 'Ada'], ['name' => 'Grace']]]);

        $host->data['lines'][$secondKeys[1]]['name'] = 'Grace H.';

        expect(fn () => $host->validateSchema($schema))->toThrow(function (ValidationException $exception) use ($secondKeys): void {
            expect(array_keys($exception->getErrors()))->toBe(["data.lines.{$secondKeys[1]}.name"]);
        });
    });

    it('keys items by position end to end under fake(), its declared default and an added item included', function (): void {
        Repeater::fake();
        $host = new RecordMappingHost();
        $schema = $host->mapping(Repeater::make('lines')->components([TextInput::make('name')]));

        $schema->fill(['lines' => [['name' => 'Ada'], ['name' => 'Grace']]]);

        expect($host->data['lines'])->toBe([['name' => 'Ada'], ['name' => 'Grace']])
            ->and($schema->getState())->toBe(['lines' => [['name' => 'Ada'], ['name' => 'Grace']]]);

        $defaults = new RecordMappingHost();
        $defaults->mapping(Repeater::make('lines')->default([['name' => 'Ada']])->components([TextInput::make('name')]))->fill();

        expect($defaults->data['lines'])->toBe([['name' => 'Ada']])
            ->and($this->createLiveComponent(RepeaterHost::class, ['data' => ['lines' => [['name' => 'Ada']]]])->callAction(TestAction::make('add')->schemaComponent('lines'))->component()->data['lines'])
            ->toBe([['name' => 'Ada'], ['name' => null]]);
    });

    it('fills a simple repeater without clobbering its scalars', function (): void {
        $host = new RecordMappingHost();
        $schema = $host->mapping(Repeater::make('tags')->simple(TextInput::make('tag')));

        $schema->fill(['tags' => ['x', 'y']]);

        expect(array_values($host->data['tags']))->toBe([['tag' => 'x'], ['tag' => 'y']])
            ->and($schema->getState())->toBe(['tags' => ['x', 'y']]);
    });

    it('fills a nested repeater without phantom items', function (): void {
        $host = new RecordMappingHost();
        $schema = $host->mapping(Repeater::make('lines')->components([
            TextInput::make('title'),
            Repeater::make('parts')->components([TextInput::make('label')]),
        ]));

        $schema->fill(['lines' => [['title' => 'One', 'parts' => [['label' => 'a'], ['label' => 'b']]]]]);

        $line = array_first($host->data['lines']);

        expect($host->data['lines'])->toHaveCount(1)
            ->and(array_column(array_values($line['parts']), 'label'))->toBe(['a', 'b'])
            ->and($schema->getState())->toBe(['lines' => [['title' => 'One', 'parts' => [['label' => 'a'], ['label' => 'b']]]]]);
    });
});

describe('getItems()', function (): void {
    beforeEach(function (): void {
        $this->host = new RecordMappingHost();
        $this->repeater = $this->host->mapping(Repeater::make('lines')->components([TextInput::make('name')]))->getComponent('lines');
        $this->host->data = ['lines' => ['a' => ['name' => 'Ada'], 'b' => ['name' => 'Grace']]];
    });

    it('keeps its item schemas until the item structure changes', function (): void {
        $items = $this->repeater->getItems();
        $this->host->data['lines']['a']['name'] = 'Ada L.';

        expect($this->repeater->getItems())->toBe($items);

        $this->repeater->rawState([...$this->host->data['lines'], 'c' => ['name' => 'Hedy']]);

        expect(array_keys($this->repeater->getItems()))->toBe(['a', 'b', 'c'])
            ->and($this->repeater->getItems()['c']->getStatePath())->toBe('data.lines.c');
    });

    it('drops the cache when it is cleared', function (): void {
        $items = $this->repeater->getItems();

        $this->repeater->clearCachedChildSchemas();

        expect($this->repeater->getItems()['a'])->not->toBe($items['a'])
            ->and(array_keys($this->repeater->getItems()))->toBe(['a', 'b']);
    });
});

describe('defaultItems()', function (): void {
    it('starts a new repeater with one empty item, or as many as defaultItems() names', function (Closure $repeater, int $count): void {
        $host = new RecordMappingHost();
        $schema = $host->mapping($repeater()->components([TextInput::make('name')]));

        $schema->fill();

        expect(array_values($host->data['lines']))->toBe(array_fill(0, $count, ['name' => null]))
            ->and($this->renderSchemaComponent($repeater()->components([TextInput::make('name')]), $host->data)->filter(FormsSelectors::REPEATER_ITEM))->toHaveCount($count);
    })->with([
        'by default' => [fn (): Repeater => Repeater::make('lines'), 1],
        'a count from a closure' => [fn (): Repeater => Repeater::make('lines')->defaultItems(static fn (): int => 2), 2],
        'none' => [fn (): Repeater => Repeater::make('lines')->defaultItems(0), 0],
    ]);

    it('starts a new repeater with the items a declared default holds, each under its own key', function (): void {
        $host = new RecordMappingHost();
        $schema = $host->mapping(Repeater::make('lines')->default([['name' => 'Ada'], ['name' => 'Grace']])->components([TextInput::make('name')]));

        $schema->fill();

        expect(array_values($host->data['lines']))->toBe([['name' => 'Ada'], ['name' => 'Grace']])
            ->and(array_unique(array_keys($host->data['lines'])))->toHaveCount(2);
    });

    it('fills from the record instead of the default', function (): void {
        $host = new RecordMappingHost();
        $schema = $host->mapping(Repeater::make('lines')->default([['name' => 'Ada']])->components([TextInput::make('name')]));

        $schema->fill(['lines' => [['name' => 'Hedy']]]);

        expect(array_values($host->data['lines']))->toBe([['name' => 'Hedy']]);
    });

    it("gives each new item its fields' own defaults, beside what a declared default holds", function (Closure $repeater, array $item): void {
        $host = new RecordMappingHost();
        $schema = $host->mapping($repeater());

        $schema->fill();

        expect(array_values($host->data['lines']))->toBe([$item]);
    })->with([
        'defaultItems()' => [fn (): Repeater => Repeater::make('lines')->defaultItems(1)->components([TextInput::make('name'), TextInput::make('unit')->default('kg')]), ['name' => null, 'unit' => 'kg']],
        'a declared default' => [fn (): Repeater => Repeater::make('lines')->default([['name' => 'Ada']])->components([TextInput::make('name'), TextInput::make('unit')->default('kg')]), ['name' => 'Ada', 'unit' => 'kg']],
        "a declared default over an item field's hydration hook" => [fn (): Repeater => Repeater::make('lines')->default([['name' => 'Ada']])->components([TextInput::make('name')->afterStateHydrated(static fn (TextInput $component): TextInput => $component->state('hooked')), TextInput::make('unit')->default('kg')]), ['name' => 'Ada', 'unit' => 'kg']],
        "a declared default over an item field's own default" => [fn (): Repeater => Repeater::make('lines')->default([['name' => 'Ada']])->components([TextInput::make('name')->default('Grace'), TextInput::make('unit')->default('kg')]), ['name' => 'Ada', 'unit' => 'kg']],
    ]);

    it('replaces the items a populated repeater holds with exactly the default ones', function (): void {
        $host = new RecordMappingHost();
        $schema = $host->mapping(Repeater::make('tags')->generateUuidUsing(static fn (): string => 'fresh')->components([TextInput::make('name')]));
        $host->data = ['tags' => [['name' => 'a'], ['name' => 'b']]];

        $schema->fill();

        expect($host->data['tags'])->toBe(['fresh' => ['name' => null]]);
    });
});

describe('simple()', function (): void {
    it('carries one scalar per item, rendered as the simple field', function (): void {
        $host = new RecordMappingHost();
        $schema = $host->mapping(Repeater::make('tags')->simple(TextInput::make('tag')));
        $schema->fill(['tags' => ['x', 'y']]);

        $crawler = $this->renderSchemaComponent(Repeater::make('tags')->simple(TextInput::make('tag')), $host->data);

        expect($crawler->filter(FormsSelectors::SIMPLE_REPEATER_ITEM . ' input')->each(static fn (Crawler $input): string => $input->attr('value') ?? ''))->toBe(['x', 'y'])
            ->and($schema->getState())->toBe(['tags' => ['x', 'y']]);
    });
});

describe('getItemState()', function (): void {
    it("reads one item's state, validated, while getItemStateSnapshot() reads it unvalidated", function (): void {
        $host = $this->validatingRecordMappingHost();
        $repeater = $host->mapping(Repeater::make('lines')->components([
            Select::make('letter')->options(StringBackedLetter::class),
            TextInput::make('name')->required(),
        ]))->getComponent('lines');
        $host->data = ['lines' => ['a' => ['letter' => 'a', 'name' => 'Ada'], 'b' => ['letter' => 'b', 'name' => '']]];

        expect($repeater->getItemState('a'))->toBe(['letter' => StringBackedLetter::A, 'name' => 'Ada'])
            ->and($repeater->getItemStateSnapshot('b'))->toBe(['letter' => StringBackedLetter::B, 'name' => null])
            ->and(fn () => $repeater->getItemState('b'))->toThrow(ValidationException::class);
    });
});

describe('getItemKeys()', function (): void {
    it('answers the state keys in order before any save, and each path the item schema declares once', function (): void {
        $host = new RecordMappingHost();
        $repeater = $host->mapping(Repeater::make('lines')->components([
            TextInput::make('name'),
            Group::make([TextInput::make('city'), TextInput::make('zip')])->statePath('address'),
            TextInput::make('name')->label('Name again'),
        ]))->getComponent('lines');
        $host->data = ['lines' => ['b' => [], 'a' => []]];

        expect($repeater->getItemKeys())->toBe(['b', 'a'])
            ->and($repeater->getItemStatePaths())->toBe(['name', 'address', 'address.city', 'address.zip']);
    });
});

describe('getTemplate()', function (): void {
    it('renders the default, simple and table views, the table outranking simple and an explicit template outranking both', function (Closure $repeater, string $root): void {
        $crawler = $this->renderSchemaComponent($repeater(), ['lines' => ['a' => ['name' => 'Ada']]]);

        expect($crawler->filter($root))->toHaveCount(1)
            ->and($crawler->filter("{$root} [data-item-key=\"a\"] input")->attr('value'))->toBe('Ada')
            ->and($crawler->filter("{$root} button")->last()->text())->toBe('Add to lines');
    })->with([
        'default' => [fn (): Repeater => Repeater::make('lines')->components([TextInput::make('name')]), '.sy-fo-repeater'],
        'simple' => [fn (): Repeater => Repeater::make('lines')->simple(TextInput::make('name')), '.sy-fo-simple-repeater'],
        'table' => [fn (): Repeater => Repeater::make('lines')->table([TableColumn::make('Name')])->components([TextInput::make('name')]), '.sy-fo-table-repeater'],
        'table over simple' => [fn (): Repeater => Repeater::make('lines')->simple(TextInput::make('name'))->table([TableColumn::make('Name')]), '.sy-fo-table-repeater'],
        'a template over a table' => [fn (): Repeater => Repeater::make('lines')->table([TableColumn::make('Name')])->template(Repeater::TEMPLATE)->components([TextInput::make('name')]), '.sy-fo-repeater'],
    ]);
});

describe('the add action', function (): void {
    it('appends one empty item', function (): void {
        $tester = $this->createLiveComponent(RepeaterHost::class, ['data' => ['lines' => ['a' => ['name' => 'Ada']]]])
            ->callAction(TestAction::make('add')->schemaComponent('lines'));

        $lines = $tester->component()->data['lines'];

        expect(array_slice($lines, 0, 1))->toBe(['a' => ['name' => 'Ada']])
            ->and(array_slice($lines, 1))->toHaveCount(1)
            ->and(array_values(array_slice($lines, 1)))->toBe([['name' => null]])
            ->and($tester->render()->crawler()->filter(FormsSelectors::REPEATER_ITEM . '[id^="form.lines."]'))->toHaveCount(2);
    });

    it('labels its add button after the text of a markup field label', function (): void {
        expect($this->renderSchemaComponent(Repeater::make('lines')->label(new Markup('<b>Lines</b> &amp; notes', 'UTF-8'))->components([TextInput::make('name')]), ['lines' => ['a' => ['name' => 'Ada']]]))
            ->toContainElement(FormsSelectors::REPEATER_ADD_BUTTON, 'Add to lines & notes');
    });

    it("renders a button labelled after the field, aligned as asked, and none when the repeater is not addable", function (): void {
        $items = ['lines' => ['a' => ['name' => 'Ada']]];

        expect($this->renderSchemaComponent(Repeater::make('lines')->components([TextInput::make('name')]), $items))
            ->toContainElement(FormsSelectors::REPEATER_ADD_BUTTON, 'Add to lines')
            ->and($this->renderSchemaComponent(Repeater::make('lines')->addActionLabel('Add a line')->addActionAlignment(Alignment::End)->components([TextInput::make('name')]), $items))
            ->toContainElement(FormsSelectors::REPEATER_ADD . SupportSelectors::align('end') . ' button', 'Add a line')
            ->and($this->renderSchemaComponent(Repeater::make('lines')->addActionAlignment('center')->components([TextInput::make('name')]), $items))
            ->toContainElement(FormsSelectors::REPEATER_ADD . SupportSelectors::align('center') . ' button', 'Add to lines');

        $crawler = $this->renderSchemaComponent(Repeater::make('lines')->addable(false)->components([TextInput::make('name')]), $items);

        expect($crawler->filter(FormsSelectors::REPEATER_ITEM))->toHaveCount(1)
            ->and($crawler->filter(FormsSelectors::REPEATER_ADD))->toHaveCount(0);
    });

    it('takes the button an addAction() modifier makes of it', function (): void {
        expect($this->renderSchemaComponent(
            Repeater::make('lines')->addAction(static fn (Action $action): Action => $action->label('New line'))->components([TextInput::make('name')]),
            ['lines' => []],
        ))->toContainElement(FormsSelectors::REPEATER_ADD_BUTTON, 'New line');
    });
});

describe('the delete action', function (): void {
    it('removes the item its trigger names', function (): void {
        $this->createLiveComponent(RepeaterHost::class, ['data' => ['lines' => ['a' => ['name' => 'Ada'], 'b' => ['name' => 'Grace']]]])
            ->callAction(TestAction::make('delete')->schemaComponent('lines'), arguments: ['item' => 'b'])
            ->assertFormSet(['lines' => ['a' => ['name' => 'Ada']]]);
    });

    it('leaves the items alone when its trigger names an item the state no longer holds', function (): void {
        $this->createLiveComponent(RepeaterHost::class, ['data' => ['lines' => ['a' => ['name' => 'Ada']]]])
            ->callAction(TestAction::make('delete')->schemaComponent('lines'), arguments: ['item' => 'gone'])
            ->assertFormSet(['lines' => ['a' => ['name' => 'Ada']]]);
    });

    it('renders a delete button in each item header, and none when the repeater is not deletable', function (): void {
        $items = ['lines' => ['a' => ['name' => 'Ada'], 'b' => ['name' => 'Grace']]];

        expect($this->renderSchemaComponent(Repeater::make('lines')->components([TextInput::make('name')]), $items)->filter('.sy-fo-repeater-item-header button[aria-label="Delete"]'))->toHaveCount(2);

        $crawler = $this->renderSchemaComponent(Repeater::make('lines')->deletable(false)->components([TextInput::make('name')]), $items);

        expect($crawler->filter('.sy-fo-repeater-item-header'))->toHaveCount(2)
            ->and($crawler->filter('button[aria-label="Delete"]'))->toHaveCount(0);
    });
});

describe('the clone action', function (): void {
    it("copies the named item's state under a fresh key", function (): void {
        $lines = $this->createLiveComponent(RepeaterHost::class, ['data' => ['lines' => ['a' => ['name' => 'Ada'], 'b' => ['name' => 'Grace']]]])
            ->callAction(TestAction::make('clone')->schemaComponent('lines'), arguments: ['item' => 'a'])
            ->component()->data['lines'];

        expect(array_slice($lines, 0, 2))->toBe(['a' => ['name' => 'Ada'], 'b' => ['name' => 'Grace']])
            ->and(array_values(array_slice($lines, 2)))->toBe([['name' => 'Ada']])
            ->and(array_key_last($lines))->not->toBeIn(['a', 'b']);
    });

    it('renders a clone button in each item header only when the repeater is cloneable', function (): void {
        $items = ['lines' => ['a' => ['name' => 'Ada'], 'b' => ['name' => 'Grace']]];

        expect($this->renderSchemaComponent(Repeater::make('lines')->cloneable()->components([TextInput::make('name')]), $items)->filter('.sy-fo-repeater-item-header button[aria-label="Clone"]'))->toHaveCount(2);

        $crawler = $this->renderSchemaComponent(Repeater::make('lines')->components([TextInput::make('name')]), $items);

        expect($crawler->filter('.sy-fo-repeater-item-header'))->toHaveCount(2)
            ->and($crawler->filter('button[aria-label="Clone"]'))->toHaveCount(0);
    });
});

describe('the move actions', function (): void {
    it('swaps the named item with its neighbour', function (string $action, array $order): void {
        $tester = $this->createLiveComponent(RepeaterHost::class, ['data' => ['lines' => ['a' => ['name' => 'Ada'], 'b' => ['name' => 'Grace'], 'c' => ['name' => 'Hedy']]]])
            ->callAction(TestAction::make($action)->schemaComponent('lines'), arguments: ['item' => 'b']);

        expect($tester->render()->crawler()->filter(FormsSelectors::REPEATER_ITEM . '[id^="form.lines."]')->each(static fn (Crawler $item): string => $item->attr('data-item-key') ?? ''))->toBe($order);
    })->with([
        'up' => ['moveUp', ['b', 'a', 'c']],
        'down' => ['moveDown', ['a', 'c', 'b']],
    ]);

    it("disables the first item's up and the last item's down button, beside a move button its modifier disables", function (): void {
        $crawler = $this->renderSchemaComponent(
            Repeater::make('lines')->reorderableWithButtons()->moveDownAction(static fn (Action $action): Action => $action->disabled(static fn (array $arguments): bool => $arguments['item'] === 'a'))->components([TextInput::make('name')]),
            ['lines' => ['a' => ['name' => 'Ada'], 'b' => ['name' => 'Grace'], 'c' => ['name' => 'Hedy']]],
        );

        expect($crawler->filter(FormsSelectors::REPEATER_ITEM . ' button[aria-label="Move up"]')->each(static fn (Crawler $button): bool => $button->attr('disabled') !== null))->toBe([true, false, false])
            ->and($crawler->filter(FormsSelectors::REPEATER_ITEM . ' button[aria-label="Move down"]')->each(static fn (Crawler $button): bool => $button->attr('disabled') !== null))->toBe([true, false, true]);
    });

    it('renders the move buttons visually hidden and out of the tab order while only a drag reorders', function (Closure $repeater): void {
        $crawler = $this->renderSchemaComponent($repeater(), ['lines' => ['a' => ['name' => 'Ada'], 'b' => ['name' => 'Grace']]]);

        expect($crawler)->toContainButtons('[data-item-key] .sy-sr-only button', ['Move up', 'Move down', 'Move up', 'Move down'])
            ->and($crawler->filter('[data-item-key] .sy-sr-only button')->each(static fn (Crawler $button): array => [$button->attr('tabindex'), $button->attr('data-reorder-hidden')]))->toBe(array_fill(0, 4, ['-1', 'true']));
    })->with([
        'default' => [fn (): Repeater => Repeater::make('lines')->components([TextInput::make('name')])],
        'simple()' => [fn (): Repeater => Repeater::make('lines')->simple(TextInput::make('name'))],
        'table()' => [fn (): Repeater => Repeater::make('lines')->table([TableColumn::make('Name')])->components([TextInput::make('name')])],
    ]);

    it('renders no move button, item description or status region when nothing reorders the items', function (Closure $repeater): void {
        $crawler = $this->renderSchemaComponent($repeater()->components([TextInput::make('name')]), ['lines' => ['a' => ['name' => 'Ada'], 'b' => ['name' => 'Grace']]]);

        expect($crawler->filter(FormsSelectors::REPEATER_ITEM))->toHaveCount(2)
            ->and($crawler->filter('button[aria-label="Move up"], button[aria-label="Move down"]'))->toHaveCount(0)
            ->and($crawler->filter('[data-reorder-description], [role="status"]'))->toHaveCount(0);
    })->with([
        'not reorderable' => [fn (): Repeater => Repeater::make('lines')->reorderableWithButtons()->reorderable(false)],
        'neither by buttons nor by drag' => [fn (): Repeater => Repeater::make('lines')->reorderableWithDragAndDrop(false)],
    ]);
});

describe('screen-reader reordering', function (): void {
    it('describes each item by its label, or as an item, with its place, points its move buttons at it, and holds the moved message for an empty status region', function (Closure $repeater): void {
        $crawler = $this->renderSchemaComponent(
            $repeater()->reorderableWithButtons()->itemLabel(static fn (string $uuid): string|Markup => $uuid === 'a' ? new Markup('<em>Ada</em> &amp; Grace', 'UTF-8') : ' '),
            ['lines' => ['a' => ['name' => 'Ada'], 'b' => ['name' => 'Grace']]],
        );
        $descriptions = $crawler->filter('[data-reorder-description]');

        expect($descriptions->each(static fn (Crawler $description): array => [$description->text(), $description->attr('data-reorder-label'), $description->attr('data-reorder-description')]))->toBe([
            ['Ada & Grace, position 1 of 2', 'Ada & Grace', 'a'],
            ['Item, position 2 of 2', 'Item', 'b'],
        ])
            ->and($crawler->filter('[data-item-key] [data-reorder-direction]')->each(static fn (Crawler $button): array => [
                $button->attr('aria-label'),
                $button->attr('data-reorder-direction'),
                $descriptions->filter(sprintf('[id="%s"]', $button->attr('aria-describedby')))->attr('data-reorder-description'),
            ]))->toBe([['Move up', 'up', 'a'], ['Move down', 'down', 'a'], ['Move up', 'up', 'b'], ['Move down', 'down', 'b']])
            ->and($crawler)->toContainElement('[role="status"][aria-live="polite"][aria-atomic="true"][data-live-ignore]', '')
            ->and($crawler->filter('[data-sylament--forms--repeater-moved-message-value]')->attr('data-sylament--forms--repeater-moved-message-value'))->toBe('__LABEL__ moved to position __POSITION__ of __COUNT__');
    })->with([
        'default' => [fn (): Repeater => Repeater::make('lines')->components([TextInput::make('name')])],
        'simple()' => [fn (): Repeater => Repeater::make('lines')->simple(TextInput::make('name'))],
        'table()' => [fn (): Repeater => Repeater::make('lines')->table([TableColumn::make('Name')])->components([TextInput::make('name')])],
    ]);

    it('takes the drag handle out of reach while its item has an enabled move button, unless a reorderAction() modifier reshapes it', function (Closure $repeater, array $items, array $hidden): void {
        $crawler = $this->renderSchemaComponent($repeater()->components([TextInput::make('name')]), ['lines' => $items]);

        expect($crawler->filter(SupportSelectors::SORTABLE_HANDLE)->each(static fn (Crawler $handle): array => [$handle->attr('aria-hidden'), $handle->attr('tabindex')]))->toBe($hidden);
    })->with([
        'two items' => [fn (): Repeater => Repeater::make('lines'), ['a' => ['name' => 'Ada'], 'b' => ['name' => 'Grace']], [['true', '-1'], ['true', '-1']]],
        'one item, both its moves disabled' => [fn (): Repeater => Repeater::make('lines'), ['a' => ['name' => 'Ada']], [[null, null]]],
        'a reorderAction() modifier' => [fn (): Repeater => Repeater::make('lines')->reorderAction(static fn (Action $action): Action => $action->color('primary')), ['a' => ['name' => 'Ada'], 'b' => ['name' => 'Grace']], [[null, null], [null, null]]],
    ]);

    it('hands the controller every move button that still mounts its own action and opens no modal', function (Closure $configure, bool $confirmsEveryAction, array $buttons): void {
        $repeater = $configure(Repeater::make('lines')->reorderableWithButtons()->components([TextInput::make('name')]));
        $render = fn (): Crawler => $this->renderSchemaComponent($repeater, ['lines' => ['a' => ['name' => 'Ada'], 'b' => ['name' => 'Grace'], 'c' => ['name' => 'Hedy']]]);
        $crawler = $confirmsEveryAction ? Action::configureUsing(static fn (Action $action): Action => $action->requiresConfirmation(), during: $render) : $render();

        expect($crawler->filter(FormsSelectors::repeaterItem('b') . ' [data-reorder-direction]')->each(static fn (Crawler $button): array => [$button->attr('data-reorder-direction'), $button->attr('data-reorder-action'), $button->attr('aria-describedby') !== null]))->toBe($buttons);
    })->with([
        'as built' => [fn (Repeater $repeater): Repeater => $repeater, false, [['up', 'moveUp', true], ['down', 'moveDown', true]]],
        'a moveUpAction() modifier that keeps the move' => [fn (Repeater $repeater): Repeater => $repeater->moveUpAction(static fn (Action $action): Action => $action->color('primary')), false, [['up', 'moveUp', true], ['down', 'moveDown', true]]],
        'a moveUpAction() modifier that links away' => [fn (Repeater $repeater): Repeater => $repeater->moveUpAction(static fn (Action $action): Action => $action->url('https://example.com')), false, [['up', null, true], ['down', 'moveDown', true]]],
        'a confirmation every action requires' => [fn (Repeater $repeater): Repeater => $repeater, true, [['up', null, true], ['down', null, true]]],
    ]);
});

describe('the reorder action', function (): void {
    it('marks the drag handle, the items and the controllers that reorder them', function (): void {
        $crawler = $this->renderSchemaComponent(Repeater::make('lines')->components([TextInput::make('name')]), ['lines' => ['a' => ['name' => 'Ada'], 'b' => ['name' => 'Grace']]]);
        $list = $crawler->filter('ul.sy-fo-repeater-items');

        expect($crawler->filter(FormsSelectors::REPEATER_ITEM . ' button' . SupportSelectors::SORTABLE_HANDLE)->each(static fn (Crawler $handle): string => $handle->attr('aria-label') ?? ''))->toBe(['Move', 'Move'])
            ->and($crawler->filter('button' . SupportSelectors::SORTABLE_HANDLE . '[data-live-action-param]'))->toHaveCount(0)
            ->and($crawler->filter('li' . SupportSelectors::SORTABLE_ITEM)->each(static fn (Crawler $item): string => $item->attr('data-sortable-item') ?? ''))->toBe(['a', 'b'])
            ->and($list->matches(SupportSelectors::SORTABLE_CONTROLLER))->toBeTrue()
            ->and($list->attr('data-action'))->toBe('sylament--support--sortable:end->sylament--forms--repeater#reorder')
            ->and($crawler->filter('.sy-fo-repeater')->attr('data-controller'))->toBe('sylament--forms--repeater');
    });

    it('hands the animation duration to the sortable controller', function (): void {
        $crawler = $this->renderSchemaComponent(Repeater::make('lines')->reorderAnimationDuration(150)->components([TextInput::make('name')]), ['lines' => ['a' => ['name' => 'Ada']]]);

        expect($crawler)->toContainElement('ul.sy-fo-repeater-items' . SupportSelectors::sortableAnimationDuration('150'));
    });

    it('drops the handle, the control and the markers together', function (Closure $repeater): void {
        $crawler = $this->renderSchemaComponent($repeater()->components([TextInput::make('name')]), ['lines' => ['a' => ['name' => 'Ada'], 'b' => ['name' => 'Grace']]]);

        expect($crawler->filter(FormsSelectors::REPEATER_ITEM))->toHaveCount(2)
            ->and($crawler->filter(SupportSelectors::SORTABLE_HANDLE . ', ' . SupportSelectors::SORTABLE_ITEM . ', ' . SupportSelectors::SORTABLE_CONTROLLER))->toHaveCount(0)
            ->and($crawler->filter('.sy-fo-repeater[data-controller]'))->toHaveCount(0);
    })->with([
        'not reorderable' => [fn (): Repeater => Repeater::make('lines')->reorderable(false)],
        'not reorderable by drag' => [fn (): Repeater => Repeater::make('lines')->reorderableWithDragAndDrop(false)],
        'disabled' => [fn (): Repeater => Repeater::make('lines')->disabled()],
    ]);

    it('writes back the order the client sent', function (): void {
        $tester = $this->createLiveComponent(RepeaterHost::class, ['data' => ['lines' => ['a' => ['name' => 'Ada'], 'b' => ['name' => 'Grace']]]])
            ->callAction(TestAction::make('reorder')->schemaComponent('lines'), arguments: ['items' => ['b', 'a']]);

        expect(array_keys($tester->component()->data['lines']))->toBe(['b', 'a'])
            ->and($tester->render()->crawler()->filter(FormsSelectors::REPEATER_ITEM . '[id^="form.lines."]')->each(static fn (Crawler $item): string => $item->attr('data-item-key') ?? ''))->toBe(['b', 'a']);
    });

    it('drops an unknown key and keeps an omitted item after the sent ones', function (): void {
        $tester = $this->createLiveComponent(RepeaterHost::class, ['data' => ['lines' => ['a' => ['name' => 'Ada'], 'b' => ['name' => 'Grace'], 'c' => ['name' => 'Hedy']]]])
            ->callAction(TestAction::make('reorder')->schemaComponent('lines'), arguments: ['items' => ['c', 'gone']]);

        expect($tester->component()->data['lines'])->toBe(['c' => ['name' => 'Hedy'], 'a' => ['name' => 'Ada'], 'b' => ['name' => 'Grace']]);
    });

    it('finds a positional item by the numeric string the client sends', function (): void {
        $tester = $this->createLiveComponent(RepeaterHost::class, ['data' => ['lines' => [['name' => 'Ada'], ['name' => 'Grace']]]])
            ->callAction(TestAction::make('reorder')->schemaComponent('lines'), arguments: ['items' => ['1', '0']]);

        expect($tester->component()->data['lines'])->toBe([1 => ['name' => 'Grace'], 0 => ['name' => 'Ada']]);
    });

    it('refuses an items argument that is not a list of keys, naming the argument', function (mixed $items): void {
        $tester = $this->createLiveComponent(RepeaterHost::class, ['data' => ['lines' => ['a' => ['name' => 'Ada']]]]);

        expect(fn () => $tester->callAction(TestAction::make('reorder')->schemaComponent('lines'), arguments: ['items' => $items]))
            ->toThrow(LogicException::class, 'without an "items" argument listing the item keys');
    })->with([
        'a map' => [['first' => 'a']],
        'a string' => ['a'],
    ]);
});

describe('addBetween', function (): void {
    it('renders the between-item button only once it is made visible, between items and never after the last', function (): void {
        $items = ['lines' => ['a' => ['name' => 'Ada'], 'b' => ['name' => 'Grace'], 'c' => ['name' => 'Hedy']]];
        $crawler = $this->renderSchemaComponent(Repeater::make('lines')->addBetweenAction(static fn (Action $action): Action => $action->visible())->components([TextInput::make('name')]), $items);

        expect($crawler->filter('.sy-fo-repeater-add-between-items button')->each(static fn (Crawler $button): string => $button->text()))->toBe(['Insert between', 'Insert between'])
            ->and($crawler->filter(FormsSelectors::repeaterItem('c') . ' ~ li .sy-fo-repeater-add-between-items'))->toHaveCount(0)
            ->and($crawler->filter(SupportSelectors::SORTABLE_ITEM))->toHaveCount(3)
            ->and($this->renderSchemaComponent(Repeater::make('lines')->addBetweenActionLabel('Add here')->addBetweenAction(static fn (Action $action): Action => $action->visible())->components([TextInput::make('name')]), $items))
            ->toContainElement('.sy-fo-repeater-add-between-items button', 'Add here');

        $hidden = $this->renderSchemaComponent(Repeater::make('lines')->components([TextInput::make('name')]), $items);

        expect($hidden->filter(FormsSelectors::REPEATER_ITEM))->toHaveCount(3)
            ->and($hidden->filter('.sy-fo-repeater-add-between-items'))->toHaveCount(0);
    });

    it('shows the label between items where no button shows, and the button instead when both are declared', function (): void {
        $items = ['lines' => ['a' => ['name' => 'Ada'], 'b' => ['name' => 'Grace']]];
        $labelled = $this->renderSchemaComponent(Repeater::make('lines')->labelBetweenItems('or')->components([TextInput::make('name')]), $items);
        $both = $this->renderSchemaComponent(Repeater::make('lines')->labelBetweenItems('or')->addBetweenAction(static fn (Action $action): Action => $action->visible())->components([TextInput::make('name')]), $items);

        expect($labelled->filter('.sy-fo-repeater-label-between-items')->each(static fn (Crawler $label): string => $label->text()))->toBe(['or'])
            ->and($labelled->filter(SupportSelectors::SORTABLE_ITEM))->toHaveCount(2)
            ->and($both->filter('.sy-fo-repeater-add-between-items button'))->toHaveCount(1)
            ->and($both->filter('.sy-fo-repeater-label-between-items'))->toHaveCount(0);
    });

    it('inserts an empty item after the named item', function (): void {
        $lines = $this->createLiveComponent(RepeaterHost::class, ['data' => ['lines' => ['a' => ['name' => 'Ada'], 'b' => ['name' => 'Grace']]]])
            ->callAction(TestAction::make('addBetween')->schemaComponent('lines'), arguments: ['afterItem' => 'a'])
            ->component()->data['lines'];

        expect(array_keys($lines)[0])->toBe('a')
            ->and(array_keys($lines)[2])->toBe('b')
            ->and(array_values($lines)[1])->toBe(['name' => null]);
    });

    it('mounts nothing while hidden', function (): void {
        $this->createLiveComponent(RepeaterHost::class, ['data' => ['unbounded' => ['a' => ['name' => 'Ada'], 'b' => ['name' => 'Grace']]]])
            ->mountAction(TestAction::make('addBetween')->schemaComponent('unbounded'), arguments: ['afterItem' => 'a'])
            ->callMountedAction()
            ->assertFormSet(['unbounded' => ['a' => ['name' => 'Ada'], 'b' => ['name' => 'Grace']]]);
    });
});

describe('structural actions', function (): void {
    it('fires afterStateUpdated once per action', function (string $action, array $arguments): void {
        $this->createLiveComponent(RepeaterHost::class, ['data' => ['lines' => ['a' => ['name' => 'Ada'], 'b' => ['name' => 'Grace']]]])
            ->callAction(TestAction::make($action)->schemaComponent('lines'), arguments: $arguments);

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['lines updated']);
    })->with([
        'add' => ['add', []],
        'delete' => ['delete', ['item' => 'a']],
        'clone' => ['clone', ['item' => 'a']],
        'move up' => ['moveUp', ['item' => 'b']],
        'move up at the top' => ['moveUp', ['item' => 'a']],
        'move down' => ['moveDown', ['item' => 'a']],
        'add between' => ['addBetween', ['afterItem' => 'a']],
        'reorder' => ['reorder', ['items' => ['b', 'a']]],
    ]);
});

describe('collapsing', function (): void {
    it('renders the collapse and expand pair in each item header of a collapsible repeater', function (): void {
        $items = ['lines' => ['a' => ['name' => 'Ada'], 'b' => ['name' => 'Grace']]];
        $collapsible = $this->renderSchemaComponent(Repeater::make('lines')->collapsible()->components([TextInput::make('name')]), $items);
        $plain = $this->renderSchemaComponent(Repeater::make('lines')->components([TextInput::make('name')]), $items);

        expect($collapsible)->toContainButtons('.sy-fo-repeater-item-header-collapsible-actions button', ['Collapse', 'Expand', 'Collapse', 'Expand'])
            ->and($collapsible->filter('.sy-fo-repeater')->attr('data-controller'))->toBe('sylament--forms--repeater')
            ->and($collapsible->filter('[data-sylament--forms--repeater-persist-value]'))->toHaveCount(0)
            ->and($collapsible->filter('li' . SupportSelectors::COLLAPSED))->toHaveCount(0)
            ->and($plain->filter('.sy-fo-repeater-item-header'))->toHaveCount(2)
            ->and($plain->filter('button[aria-label="Collapse"]'))->toHaveCount(0);
    });

    it('seeds the items of a collapsed() repeater collapsed, and cloaks a persistCollapsed() one until its controller replays the state', function (): void {
        $items = ['lines' => ['a' => ['name' => 'Ada']]];
        $collapsed = $this->renderSchemaComponent(Repeater::make('lines')->collapsed()->components([TextInput::make('name')]), $items);
        $persisted = $this->renderSchemaComponent(Repeater::make('lines')->collapsible()->persistCollapsed()->components([TextInput::make('name')]), $items);

        expect($collapsed->filter(FormsSelectors::repeaterItem('a') . SupportSelectors::COLLAPSED))->toHaveCount(1)
            ->and($collapsed->filter(FormsSelectors::repeaterItem('a') . ' input')->attr('value'))->toBe('Ada')
            ->and($persisted->filter('.sy-fo-repeater' . SupportSelectors::CLOAK)->attr('data-sylament--forms--repeater-persist-value'))->toBe('true');
    });

    it('hides the collapse-all and expand-all row below two items', function (): void {
        $repeater = static fn (): Repeater => Repeater::make('lines')->collapsible()->components([TextInput::make('name')]);
        $one = $this->renderSchemaComponent($repeater(), ['lines' => ['a' => ['name' => 'Ada']]]);
        $two = $this->renderSchemaComponent($repeater(), ['lines' => ['a' => ['name' => 'Ada'], 'b' => ['name' => 'Grace']]]);

        expect($two)->toContainButtons('.sy-fo-repeater-actions:not(' . SupportSelectors::HIDDEN . ') button', ['Collapse all', 'Expand all'])
            ->and($one->filter('.sy-fo-repeater-actions' . SupportSelectors::HIDDEN . ' button'))->toHaveCount(2);
    });

    it('marks a compact table view', function (): void {
        expect($this->renderSchemaComponent(Repeater::make('lines')->table([TableColumn::make('Name')])->compact()->components([TextInput::make('name')]), ['lines' => ['a' => ['name' => 'Ada']]]))
            ->toContainElement('.sy-fo-table-repeater' . SupportSelectors::COMPACT);
    });

    it('reopens a collapsed repeater when an item is added, cloned or added between', function (string $action, array $arguments): void {
        $tester = $this->createLiveComponent(RepeaterHost::class, ['data' => ['folded' => ['a' => ['name' => 'Ada'], 'b' => ['name' => 'Grace']]]]);

        expect($tester->render()->crawler()->filter('li[id^="form.folded."]' . SupportSelectors::COLLAPSED))->toHaveCount(2);

        $crawler = $tester->callAction(TestAction::make($action)->schemaComponent('folded'), arguments: $arguments)->render()->crawler();

        expect($crawler->filter(FormsSelectors::REPEATER_ITEM . '[id^="form.folded."]'))->toHaveCount(3)
            ->and($crawler->filter('li[id^="form.folded."]' . SupportSelectors::COLLAPSED))->toHaveCount(0)
            ->and($crawler->filter('li[id^="form.folded."] button[aria-label="Collapse"]'))->toHaveCount(3);
    })->with([
        'add' => ['add', []],
        'clone' => ['clone', ['item' => 'a']],
        'add between' => ['addBetween', ['afterItem' => 'a']],
    ]);

    it('leaves a repeater that is not collapsible uncollapsible when an item is added', function (): void {
        $crawler = $this->createLiveComponent(RepeaterHost::class, ['data' => ['lines' => ['a' => ['name' => 'Ada']]]])
            ->callAction(TestAction::make('add')->schemaComponent('lines'))
            ->render()->crawler();

        expect($crawler->filter(FormsSelectors::REPEATER_ITEM . '[id^="form.lines."]'))->toHaveCount(2)
            ->and($crawler->filter('li[id^="form.lines."] button[aria-label="Collapse"]'))->toHaveCount(0);
    });

    it("keeps the native constraints of a collapsible repeater's controls", function (): void {
        expect($this->renderSchemaComponent(Repeater::make('lines')->collapsible()->components([TextInput::make('name')->required()->maxLength(5)]), ['lines' => ['a' => ['name' => '']]]))
            ->toContainElement(FormsSelectors::repeaterItem('a') . ' input[required][maxlength="5"]');
    });
});

describe('item labels', function (): void {
    it('shows the item label and its number in each header, truncated unless told otherwise', function (): void {
        $items = ['lines' => ['a' => ['name' => 'Ada'], 'b' => ['name' => 'Grace']]];
        $numbered = $this->renderSchemaComponent(Repeater::make('lines')->itemLabel('Line')->itemNumbers()->components([TextInput::make('name')]), $items);
        $untruncated = $this->renderSchemaComponent(Repeater::make('lines')->itemLabel('Line')->truncateItemLabel(false)->components([TextInput::make('name')]), $items);

        expect($numbered->filter('.sy-fo-repeater-item-header-label.sy-truncated')->each(static fn (Crawler $label): string => $label->text()))->toBe(['Line 1', 'Line 2'])
            ->and($untruncated->filter('.sy-fo-repeater-item-header-label:not(.sy-truncated)')->each(static fn (Crawler $label): string => $label->text()))->toBe(['Line', 'Line']);
    });

    it("labels each item from a closure given the item's state, key and index", function (): void {
        $crawler = $this->renderSchemaComponent(
            Repeater::make('lines')
                ->itemLabel(static fn (array $state, string $uuid, int $index): string => "{$state['name']} ({$uuid}, {$index})")
                ->components([TextInput::make('name')]),
            ['lines' => ['a' => ['name' => 'Ada'], 'b' => ['name' => 'Grace']]],
        );

        expect($crawler->filter('.sy-fo-repeater-item-header-label')->each(static fn (Crawler $label): string => $label->text()))->toBe(['Ada (a, 0)', 'Grace (b, 1)']);
    });

    it('renders no item header under itemHeaders(false), nor when nothing would fill it', function (Closure $repeater): void {
        $items = ['lines' => ['a' => ['name' => 'Ada']]];

        expect($this->renderSchemaComponent(Repeater::make('lines')->components([TextInput::make('name')]), $items)->filter('.sy-fo-repeater-item-header'))->toHaveCount(1);

        $crawler = $this->renderSchemaComponent($repeater()->components([TextInput::make('name')]), $items);

        expect($crawler->filter(FormsSelectors::repeaterItem('a') . ' input')->attr('value'))->toBe('Ada')
            ->and($crawler->filter('.sy-fo-repeater-item-header'))->toHaveCount(0);
    })->with([
        'itemHeaders(false)' => [fn (): Repeater => Repeater::make('lines')->itemLabel('Line')->itemHeaders(false)],
        'nothing to show' => [fn (): Repeater => Repeater::make('lines')->reorderable(false)->deletable(false)],
    ]);
});

describe('item action icons', function (): void {
    it('renders each item trigger with its icon', function (): void {
        $crawler = $this->renderSchemaComponent(
            Repeater::make('lines')->cloneable()->collapsible()->reorderableWithButtons()->components([TextInput::make('name')]),
            ['lines' => ['a' => ['name' => 'Ada'], 'b' => ['name' => 'Grace']]],
        );
        $iconOf = static fn (string $name): string => $crawler->filter(FormsSelectors::repeaterItem('a') . " button[aria-label=\"{$name}\"] svg")->text();

        expect(array_map($iconOf, ['Move', 'Move down', 'Clone', 'Collapse', 'Expand']))->toBe(['arrows-up-down-20-solid', 'arrow-down-20-solid', 'square-2-stack-20-solid', 'chevron-up-20-solid', 'chevron-down-20-solid'])
            ->and($crawler->filter(FormsSelectors::repeaterItem('b') . ' button[aria-label="Move up"] svg')->text())->toBe('arrow-up-20-solid')
            ->and($crawler->filter(FormsSelectors::repeaterItem('a') . ' button[aria-label="Delete"] svg')->text())->toBe('trash-20-solid');
    });
});

describe('grid()', function (): void {
    it("lays the items out on support's grid, in one column by default", function (Closure $repeater, string $list, array $columns): void {
        $crawler = $this->renderSchemaComponent($repeater(), ['lines' => ['a' => ['name' => 'Ada'], 'b' => ['name' => 'Grace']]]);

        foreach ($columns as $breakpoint => $count) {
            expect($crawler)->toContainElement($list . SupportSelectors::GRID . SupportSelectors::columns($breakpoint, $count));
        }
    })->with([
        'without grid()' => [fn (): Repeater => Repeater::make('lines')->components([TextInput::make('name')]), 'ul.sy-fo-repeater-items', ['default' => 1]],
        'a column count' => [fn (): Repeater => Repeater::make('lines')->grid(3)->components([TextInput::make('name')]), 'ul.sy-fo-repeater-items', ['default' => 1, 'lg' => 3]],
        'counts per breakpoint' => [fn (): Repeater => Repeater::make('lines')->grid(['md' => 2, 'lg' => 4])->components([TextInput::make('name')]), 'ul.sy-fo-repeater-items', ['md' => 2, 'lg' => 4]],
        'counts per breakpoint, merged across calls' => [fn (): Repeater => Repeater::make('lines')->grid(['md' => 2])->grid(['lg' => 4])->components([TextInput::make('name')]), 'ul.sy-fo-repeater-items', ['md' => 2, 'lg' => 4]],
        'a list entry, at lg' => [fn (): Repeater => Repeater::make('lines')->grid(['md' => 2, 3])->components([TextInput::make('name')]), 'ul.sy-fo-repeater-items', ['md' => 2, 'lg' => 3]],
        'a closure answering counts per breakpoint' => [fn (): Repeater => Repeater::make('lines')->grid(static fn (): array => ['sm' => 2])->components([TextInput::make('name')]), 'ul.sy-fo-repeater-items', ['sm' => 2]],
        'the simple view' => [fn (): Repeater => Repeater::make('lines')->grid(3)->simple(TextInput::make('name')), 'ul.sy-fo-simple-repeater-items', ['lg' => 3]],
    ]);

    it('lays the table view out in rows, without the grid', function (): void {
        $crawler = $this->renderSchemaComponent(Repeater::make('lines')->grid(3)->table([TableColumn::make('Name')])->components([TextInput::make('name')]), ['lines' => ['a' => ['name' => 'Ada']]]);

        expect($crawler->filter('.sy-fo-table-repeater ' . FormsSelectors::tableRepeaterRow('a')))->toHaveCount(1)
            ->and($crawler->filter('.sy-fo-table-repeater [style*="--cols-"]'))->toHaveCount(0);
    });
});

describe('table()', function (): void {
    it('switches to a table with one header cell per column, and back to the default view for no columns', function (): void {
        $items = ['lines' => ['a' => ['name' => 'Ada', 'code' => 'A1']]];
        $table = $this->renderSchemaComponent(Repeater::make('lines')->reorderable(false)->deletable(false)->table([TableColumn::make('Name'), TableColumn::make('Code')])->components([TextInput::make('name'), TextInput::make('code')]), $items);
        $none = $this->renderSchemaComponent(Repeater::make('lines')->table([])->components([TextInput::make('name')]), $items);

        expect($table->filter(FormsSelectors::TABLE_REPEATER_HEADER_CELL)->each(static fn (Crawler $cell): string => $cell->text()))->toBe(['Name', 'Code'])
            ->and($table->filter(FormsSelectors::tableRepeaterRow('a') . ' td input')->each(static fn (Crawler $input): string => $input->attr('value') ?? ''))->toBe(['Ada', 'A1'])
            ->and($none->filter('.sy-fo-repeater ' . FormsSelectors::repeaterItem('a')))->toHaveCount(1)
            ->and($none->filter('table'))->toHaveCount(0);
    });

    it('refuses an item schema holding anything but schema components, naming it', function (): void {
        $repeater = Repeater::make('lines')->table([TableColumn::make('Name')])->components([TextInput::make('name'), Action::make('ping')]);

        expect(fn () => $this->renderSchemaComponent($repeater, ['lines' => ['a' => ['name' => 'Ada']]]))
            ->toThrow(RuntimeError::class, sprintf('Table repeaters must only contain schema components, but [%s] was used.', Action::class));
    });

    it('pairs columns to components by position', function (): void {
        $crawler = $this->renderSchemaComponent(
            Repeater::make('lines')
                ->reorderable(false)
                ->deletable(false)
                ->table([TableColumn::make('Name')->verticalAlignment(VerticalAlignment::Center), TableColumn::make('Code'), TableColumn::make('Note')])
                ->components([
                    TextInput::make('name'),
                    Hidden::make('id'),
                    TextInput::make('code')->visible(false),
                    TextInput::make('note'),
                    TextInput::make('dropped'),
                    Hidden::make('trailing'),
                ]),
            ['lines' => ['a' => ['name' => 'Ada', 'id' => '7', 'code' => 'A1', 'note' => 'n', 'dropped' => 'd', 'trailing' => 't']]],
        );
        $cells = $crawler->filter(FormsSelectors::tableRepeaterRow('a') . ' > td');

        expect($cells)->toHaveCount(4)
            ->and($cells->eq(0)->filter('input')->attr('value'))->toBe('Ada')
            ->and($cells->eq(0)->matches('.sy-vertical-align-center'))->toBeTrue()
            ->and($cells->eq(1)->matches(SupportSelectors::HIDDEN))->toBeTrue()
            ->and($cells->eq(1)->children())->toHaveCount(0)
            ->and($cells->eq(2)->filter('input')->attr('value'))->toBe('n')
            ->and($crawler->filter('input[value="d"]'))->toHaveCount(0);
    });

    it('renders hidden fields in a trailing cell inside their row, dropping one past the last column', function (): void {
        $crawler = $this->renderSchemaComponent(
            Repeater::make('lines')->table([TableColumn::make('Name')])->components([Hidden::make('id'), TextInput::make('name'), Hidden::make('trailing')]),
            ['lines' => ['a' => ['id' => '7', 'name' => 'Ada', 'trailing' => 't']]],
        );

        expect($crawler->filter(FormsSelectors::tableRepeaterRow('a') . ' > td.sy-fo-table-repeater-hidden-cell input[type="hidden"]')->each(static fn (Crawler $input): string => $input->attr('value') ?? ''))->toBe(['7'])
            ->and($crawler->filter('input[value="t"]'))->toHaveCount(0);
    });

    it('shows the reorder and actions header cells only when a row can use them', function (): void {
        $columns = [TableColumn::make('Name')];
        $two = ['lines' => ['a' => ['name' => 'Ada'], 'b' => ['name' => 'Grace']]];
        $headers = fn (Repeater $repeater, array $items): array => $this->renderSchemaComponent($repeater->table($columns)->components([TextInput::make('name')]), $items)
            ->filter(FormsSelectors::TABLE_REPEATER_HEADER_CELL)->each(static fn (Crawler $cell): string => $cell->text());

        expect($headers(Repeater::make('lines'), $two))->toBe(['Reorder', 'Name', 'Actions'])
            ->and($headers(Repeater::make('lines'), ['lines' => ['a' => ['name' => 'Ada']]]))->toBe(['Name', 'Actions'])
            ->and($headers(Repeater::make('lines')->reorderable(false), $two))->toBe(['Name', 'Actions'])
            ->and($headers(Repeater::make('lines')->deletable(false), $two))->toBe(['Reorder', 'Name'])
            ->and($headers(Repeater::make('lines')->deletable(false)->cloneable(), $two))->toBe(['Reorder', 'Name', 'Actions'])
            ->and($this->renderSchemaComponent(Repeater::make('lines')->table($columns)->components([TextInput::make('name')]), $two)->filter(FormsSelectors::TABLE_REPEATER_HEADER_CELL . '.sy-fo-table-repeater-empty-header-cell ' . SupportSelectors::SCREEN_READER_ONLY))->toHaveCount(2);
    });
});

describe('nullWhenBlank()', function (): void {
    it('writes an empty repeater as an empty list, or as null under nullWhenBlank()', function (Closure $repeater, ?array $meta): void {
        $host = new RecordMappingHost();
        $schema = $host->mapping($repeater()->components([TextInput::make('name')]))->record($manuscript = new Manuscript());
        $schema->fill($manuscript);

        $schema->getData();

        expect($manuscript->getMeta())->toBe($meta);
    })->with([
        'by default' => [fn (): Repeater => Repeater::make('meta'), []],
        'nullWhenBlank()' => [fn (): Repeater => Repeater::make('meta')->nullWhenBlank(), null],
    ]);

    it('judges a repeater by its own slice', function (): void {
        $host = $this->validatingRecordMappingHost();
        $schema = $host->mapping(Repeater::make('meta')->required()->nullWhenBlank()->components([TextInput::make('name')]))->record($manuscript = new Manuscript());
        $host->data = ['meta' => ['a' => ['name' => null]]];

        $host->validateSchema($schema);

        $schema->getData();

        expect($manuscript->getMeta())->toBe([['name' => null]]);

        $host->data = ['meta' => []];

        expect(fn () => $host->validateSchema($schema))->toThrow(function (ValidationException $exception): void {
            expect(array_keys($exception->getErrors()))->toBe(['data.meta']);
        });
    });

    it('refuses to write an empty repeater onto a record property that cannot hold null', function (): void {
        $host = new RecordMappingHost();
        $schema = $host->mapping(Repeater::make('lines')->nullWhenBlank()->components([TextInput::make('name')]))->record($sheet = new LineSheet());
        $schema->fill($sheet);

        expect(fn (): object => $schema->getData())->toThrow(LogicException::class, sprintf('Cannot map state path "lines" onto %s: the record declares that property non-nullable, so the nullWhenBlank() on the component that owns it can never apply.', LineSheet::class));
    });
});

describe('requirement', function (): void {
    it('derives no requirement for an item field of a plain array, and validates an item field declared required() in each item', function (): void {
        $host = $this->validatingRecordMappingHost();
        $plain = $host->mapping(Repeater::make('lines')->components([TextInput::make('name')]))->record(new LineSheet());
        $host->data = ['lines' => ['a' => ['name' => null], 'b' => ['name' => null]]];

        $host->validateSchema($plain);

        $required = $host->mapping(Repeater::make('lines')->components([TextInput::make('name')->required()]))->record(new LineSheet());

        expect(fn () => $host->validateSchema($required))->toThrow(function (ValidationException $exception): void {
            expect($exception->getErrors())->toBe([
                'data.lines.a.name' => ['This value should not be blank.'],
                'data.lines.b.name' => ['This value should not be blank.'],
            ]);
        });

        $host->data = ['lines' => []];

        $host->validateSchema($plain);
    });
});

describe('record ownership', function (): void {
    it("maps the repeater's list onto the record as one value", function (): void {
        $host = new RecordMappingHost();
        $schema = $host->mapping(Repeater::make('meta')->components([TextInput::make('name')]))->record($manuscript = new Manuscript());
        $manuscript->setMeta([['name' => 'Ada'], ['name' => 'Grace']]);
        $schema->fill($manuscript);
        $host->data['meta'][array_key_first($host->data['meta'])]['name'] = 'Ada L.';

        $schema->getData();

        expect($manuscript->getMeta())->toBe([['name' => 'Ada L.'], ['name' => 'Grace']]);
    });

    it("refuses a path-bearing component nested under the repeater's path, naming both", function (): void {
        $host = new RecordMappingHost();
        $schema = $host->mapping(Repeater::make('meta')->components([TextInput::make('name')]), TextInput::make('meta.extra'))->record($manuscript = new Manuscript());

        expect(fn (): Schema => $schema->fill($manuscript))->toThrow(LogicException::class, sprintf('%s declares state path "meta.extra", which nests under "meta" — a path %s maps wholesale', TextInput::class, Repeater::class));
    });
});

describe('an object collection', function (): void {
    it('refuses early, naming relationship(), and takes a list whose objects only a docblock names as plain items', function (): void {
        $host = new RecordMappingHost();
        $refused = $host->mapping(Repeater::make('contributors')->components([TextInput::make('name')]))->record($anthology = new Anthology());

        expect(fn (): Schema => $refused->fill($anthology))->toThrow(LogicException::class, sprintf('Cannot map state path "contributors" onto the %s framing it: "contributors" is an object collection. Collection items need identity mapping (which state item is which entity), which only relationship() supplies', Anthology::class));

        $drafts = $host->mapping(Repeater::make('drafts')->components([TextInput::make('name')]))->record($anthology);
        $drafts->fill($anthology);
        $host->data['drafts'] = ['a' => ['name' => 'Ada']];
        $drafts->getData();

        expect($anthology->drafts)->toBe([['name' => 'Ada']]);
    });

    it("refuses early inside a relationship item, naming the path the item's child holds the collection at", function (): void {
        $larder = new Larder();
        $larder->addBin(new LarderBin('dry'));
        $this->seed($larder);
        $larder = $this->reload($larder);
        $host = new RecordMappingHost();
        $schema = $host->mapping(
            Repeater::make('bins')->relationship()->components([
                TextInput::make('code'),
                Repeater::make('notes')->components([TextInput::make('text')]),
            ]),
        )->record($larder);

        expect(fn (): Schema => $schema->fill($larder))->toThrow(LogicException::class, sprintf('Cannot map state path "notes" onto the %s framing it: "notes" is an object collection.', LarderBin::class));
    });
});

describe('unmanaged item keys', function (): void {
    it('carries a key no item field declares through a save', function (): void {
        $host = new RecordMappingHost();
        $schema = $host->mapping(Repeater::make('meta')->components([TextInput::make('name')]))->record($manuscript = new Manuscript());
        $manuscript->setMeta([['name' => 'Ada', 'legacy' => true]]);

        $schema->fill($manuscript);

        expect(array_values($host->data['meta']))->toBe([['name' => 'Ada', 'legacy' => true]]);

        $schema->getData();

        expect($manuscript->getMeta())->toBe([['name' => 'Ada', 'legacy' => true]]);
    });

    it('sends the key to the client and back through the structural actions', function (): void {
        $tester = $this->createLiveComponent(RepeaterHost::class, ['data' => ['lines' => ['a' => ['name' => 'Ada', 'legacy' => true], 'b' => ['name' => 'Grace'], 'c' => ['name' => 'Hedy']]]])
            ->callAction(TestAction::make('reorder')->schemaComponent('lines'), arguments: ['items' => ['c', 'b', 'a']])
            ->callAction(TestAction::make('delete')->schemaComponent('lines'), arguments: ['item' => 'b'])
            ->callAction(TestAction::make('add')->schemaComponent('lines'));

        expect(array_slice($tester->component()->data['lines'], 0, 2))->toBe(['c' => ['name' => 'Hedy'], 'a' => ['name' => 'Ada', 'legacy' => true]]);
    });
});

describe('itemsAs()', function (): void {
    it('builds DTOs only at the pipeline boundaries, from the item class or a cast built over it', function (Closure $items): void {
        $host = new RecordMappingHost();
        $schema = $host->mapping($repeater = Repeater::make('lines')->itemsAs($items())->components([TextInput::make('title')]))->record($sheet = new LineSheet());
        $sheet->setLines([new LineItem('One', note: 'kept'), new LineItem('Two')]);

        $schema->fill($sheet);

        expect(array_values($host->data['lines']))->toBe([['title' => 'One', 'note' => 'kept'], ['title' => 'Two', 'note' => null]])
            ->and(array_values($repeater->getState()))->toBe([['title' => 'One', 'note' => 'kept'], ['title' => 'Two', 'note' => null]]);

        $host->data['lines'][array_key_last($host->data['lines'])]['title'] = 'Three';
        $schema->getData();

        expect($sheet->getLines())->toEqual([new LineItem('One', note: 'kept'), new LineItem('Three')]);
    })->with([
        'the item class' => [fn (): string => LineItem::class],
        'a prebuilt cast' => [fn (): ObjectStateCast => new ObjectStateCast(LineItem::class)],
    ]);

    it('is schema-complete: a key the item class does not declare is dropped at construction', function (): void {
        $host = new RecordMappingHost();
        $schema = $host->mapping(Repeater::make('lines')->itemsAs(LineItem::class)->components([TextInput::make('title')]))->record($sheet = new LineSheet());
        $host->data = ['lines' => ['a' => ['title' => 'One', 'tampered' => 'x']]];

        $schema->getData();

        expect($sheet->getLines())->toEqual([new LineItem('One')]);
    });

    it('nulls an empty cast repeater under nullWhenBlank() and keeps a filled one', function (): void {
        $host = new RecordMappingHost();
        $schema = $host->mapping(Repeater::make('meta')->itemsAs(LineItem::class)->nullWhenBlank()->components([TextInput::make('title')]))->record($manuscript = new Manuscript());
        $host->data = ['meta' => []];

        $schema->getData();

        expect($manuscript->getMeta())->toBeNull();

        $host->data = ['meta' => ['a' => ['title' => 'One']]];
        $schema->getData();

        expect($manuscript->getMeta())->toEqual([new LineItem('One')]);
    });

    it('composes nested repeaters, cast or plain, in either position', function (bool $outerCast, bool $innerCast, Closure $lines): void {
        $parts = Repeater::make('parts')->components([TextInput::make('label')]);
        $repeater = Repeater::make('lines')->components([TextInput::make('title'), $innerCast ? $parts->itemsAs(LinePart::class) : $parts]);
        $host = new RecordMappingHost();
        $schema = $host->mapping($outerCast ? $repeater->itemsAs(PackedLineItem::class) : $repeater)->record($sheet = new LineSheet());
        $sheet->setLines($lines());

        $schema->fill($sheet);
        $sheet->setLines([]);
        $schema->getData();

        expect($sheet->getLines())->toEqual($lines());
    })->with([
        'plain in plain' => [false, false, fn (): array => [['title' => 'One', 'parts' => [['label' => 'a']]]]],
        'cast in plain' => [false, true, fn (): array => [['title' => 'One', 'parts' => [new LinePart('a')]]]],
        'plain in cast' => [true, false, fn (): array => [new PackedLineItem('One', [['label' => 'a']])]],
        'cast in cast' => [true, true, fn (): array => [new PackedLineItem('One', [new LinePart('a')])]],
    ]);

    it('refuses simple() beside it in either order', function (): void {
        expect(fn (): Repeater => Repeater::make('lines')->simple(TextInput::make('title'))->itemsAs(LineItem::class))
            ->toThrow(LogicException::class, 'itemsAs() cannot compose with simple()')
            ->and(fn (): Repeater => Repeater::make('lines')->itemsAs(LineItem::class)->simple(TextInput::make('title')))
            ->toThrow(LogicException::class, 'simple() cannot compose with itemsAs()');
    });

    it('refuses a prebuilt cast beside conversion closures, declared or resolved from a closure', function (): void {
        $denormalize = static fn (array $item): LineItem => new LineItem((string) $item['title']);

        expect(fn (): Repeater => Repeater::make('lines')->itemsAs(new ObjectStateCast(LineItem::class), denormalizeUsing: $denormalize))
            ->toThrow(LogicException::class, 'itemsAs() received a prebuilt ObjectStateCast beside conversion closures')
            ->and(fn (): ?ObjectStateCast => Repeater::make('lines')->itemsAs(static fn (): ObjectStateCast => new ObjectStateCast(LineItem::class), denormalizeUsing: $denormalize)->getItemCast())
            ->toThrow(LogicException::class, 'itemsAs() resolved a prebuilt ObjectStateCast beside conversion closures');
    });

    it('names required() when a non-optional constructor parameter meets null', function (): void {
        $host = new RecordMappingHost();
        $schema = $host->mapping(Repeater::make('lines')->itemsAs(LineItem::class)->components([TextInput::make('title')]))->record(new LineSheet());
        $host->data = ['lines' => ['a' => ['title' => null]]];

        expect(fn (): object => $schema->getData())->toThrow(LogicException::class, sprintf('Cannot rebuild %s: constructor parameter $title is non-optional and the dehydrated item supplies null for it', LineItem::class));
    });
});

describe('relationship() decoration and hooks', function (): void {
    beforeEach(function (): void {
        $binder = new VettedBinder();
        $binder->addSheet(new VettedBinderSheet('One'));
        $this->seed($binder);
        $this->binder = $this->reload($binder);
    });

    it('hands a closure in a decoration schema the owner record', function (): void {
        $seen = null;
        $host = new RecordMappingHost();
        $schema = $host->mapping(
            Repeater::make('sheets')
                ->relationship()
                ->belowContent([TextInput::make('note')->label(static function (?object $record) use (&$seen): string {
                    $seen = $record;

                    return 'Note';
                })])
                ->components([TextInput::make('heading')]),
        )->record($this->binder);
        $schema->fill($this->binder);

        $repeater = $schema->getComponent('sheets');

        $repeater->getChildSchema(Repeater::BELOW_CONTENT_SCHEMA_KEY)?->getComponents(withActions: false)[0]->getLabel();

        expect($seen)->toBe($this->binder);
    });

    it('refuses a hooked field in a decoration schema, at a plain path or a dotted one whose remainder names an item hook', function (string $path): void {
        $host = new RecordMappingHost();
        $schema = $host->mapping(
            Repeater::make('sheets')
                ->relationship()
                ->belowContent([TextInput::make($path)->loadStateFromRelationshipsUsing(static fn (): string => 'decoration')])
                ->components([TextInput::make('heading')->loadStateFromRelationshipsUsing(static fn (): string => 'item')]),
        )->record($this->binder);

        expect(fn (): Schema => $schema->fill($this->binder))->toThrow(LogicException::class, sprintf('%s declares a relationship hook, but the record path map does not carry it, so the hook could never fire', TextInput::class));
    })->with([
        'a plain path' => ['note'],
        'a dotted path whose remainder names an item hook' => ['meta.heading'],
    ]);

    it('refuses a hooked field beneath a repeater without relationship() as it fills, in its decoration and in an item the record fills, handed the record or the state read off it', function (): void {
        $manuscript = new Manuscript();
        $manuscript->setMeta([['heading' => 'One']]);

        $refusal = sprintf('%s declares a relationship hook, but the record path map does not carry it, so the hook could never fire', TextInput::class);
        $decorated = new RecordMappingHost()->mapping(
            Repeater::make('meta')->belowContent([TextInput::make('note')->loadStateFromRelationshipsUsing(static fn (): string => 'decoration')])->components([TextInput::make('heading')]),
        )->record($manuscript);
        $hooked = static fn (): Schema => new RecordMappingHost()->mapping(
            Repeater::make('meta')->components([TextInput::make('heading')->loadStateFromRelationshipsUsing(static fn (): string => 'item')]),
        )->record($manuscript);
        $readOff = $hooked();

        expect(fn (): Schema => $decorated->fill($manuscript))->toThrow(LogicException::class, $refusal)
            ->and(fn (): Schema => $hooked()->fill($manuscript))->toThrow(LogicException::class, $refusal)
            ->and(fn (): Schema => $readOff->fill($readOff->getStateFromRecord($manuscript)))->toThrow(LogicException::class, $refusal);
    });

    it('routes the save hooks of an item a caller hands getData() as it routes a rendered one', function (): void {
        $host = new RecordMappingHost();
        $schema = $host->mapping(
            Repeater::make('sheets')
                ->relationship()
                ->components([
                    TextInput::make('heading'),
                    TextInput::make('status')->saveRelationshipsUsing(static function (VettedBinderSheet $record, ?string $state): void {
                        $record->status = "Hooked {$state}";
                    }),
                ]),
        )->record($this->binder);
        $schema->fill($this->binder);

        $state = $host->data;
        $state['sheets']['handed'] = ['heading' => 'Two', 'status' => 'Draft'];

        $schema->getData($state);

        expect(array_map(static fn (VettedBinderSheet $sheet): array => [$sheet->heading, $sheet->status], array_values($this->binder->sheets->toArray())))
            ->toBe([['One', 'Hooked Filed'], ['Two', 'Hooked Draft']]);
    });
});

describe('relationship()', function (): void {
    it('fills one item per child, keyed by its record key, and saves the diff for the one flush that commits it', function (Closure $owner, string $collection, string $ownerProperty, array $keys, string $removed, string $kept, array $keptItem, array $addedItem, array $rows): void {
        $owner = $owner();
        $this->seed($owner);
        $owner = $this->reload($owner);
        $fields = array_keys($addedItem);
        $host = $this->validatingRecordMappingHost();
        $schema = $host->mapping(Repeater::make($collection)->relationship()->components(array_map(TextInput::make(...), $fields)))->record($owner)->fill($owner);
        $childClass = $owner->{$collection}->first()::class;
        $keptChild = array_find($owner->{$collection}->toArray(), static fn (object $child): bool => array_all($fields, static fn (string $field): bool => $child->{$field} === $host->data[$collection][$kept][$field]));

        expect(array_keys($host->data[$collection]))->toEqualCanonicalizing($keys);

        unset($host->data[$collection][$removed]);
        $host->data[$collection][$kept] = $keptItem;
        $host->data[$collection]['added'] = $addedItem;
        $statements = count($this->statementsRun());
        $schema->getData();

        $added = array_find($owner->{$collection}->toArray(), static fn (object $child): bool => $child !== $keptChild);

        expect(array_filter(array_slice($this->statementsRun(), $statements), static fn (string $statement): bool => preg_match('/^(INSERT|UPDATE|DELETE)\b/', $statement) === 1))->toBe([])
            ->and($owner->{$collection})->toHaveCount(2)
            ->and($owner->{$collection}->contains($keptChild))->toBeTrue()
            ->and($added?->{$ownerProperty})->toBe($owner);

        $this->entityManager()->flush();
        $this->entityManager()->clear();

        $stored = array_map(static fn (object $child): array => array_map(static fn (string $field): mixed => $child->{$field}, $fields), $this->reload($owner)->{$collection}->toArray());
        sort($stored);

        expect($stored)->toBe($rows)
            ->and($this->entityManager()->getRepository($childClass)->count([]))->toBe(2);
    })->with([
        'a key of two scalars' => [
            function (): NurseryBench {
                $bench = new NurseryBench();
                $bench->addPot(new BenchPot(bay: 'north', slot: '1', plant: 'basil'));
                $bench->addPot(new BenchPot(bay: 'south', slot: '2', plant: 'thyme'));

                return $bench;
            },
            'pots',
            'bench',
            ['record-north~1', 'record-south~2'],
            'record-south~2',
            'record-north~1',
            ['bay' => 'north', 'slot' => '1', 'plant' => 'mint'],
            ['bay' => 'east', 'slot' => '3', 'plant' => 'sage'],
            [['east', '3', 'sage'], ['north', '1', 'mint']],
        ],
        'a key of an association and a scalar' => [
            function (): Larder {
                $larder = new Larder('Cellar');
                $larder->addCrock(new LarderCrock(shelf: 'top', label: 'Plums'));
                $larder->addCrock(new LarderCrock(shelf: 'low', label: 'Figs'));

                return $larder;
            },
            'crocks',
            'larder',
            ['record-1~top', 'record-1~low'],
            'record-1~low',
            'record-1~top',
            ['shelf' => 'top', 'label' => 'Damsons'],
            ['shelf' => 'mid', 'label' => 'Pears'],
            [['mid', 'Pears'], ['top', 'Damsons']],
        ],
    ]);

    it('keys an added item by its new identifier once the host reloads, so the next save writes nothing', function (Closure $owner, string $collection, array $addedItem, array $keys): void {
        $owner = $owner();
        $this->seed($owner);
        $owner = $this->reload($owner);
        $host = $this->validatingRecordMappingHost();
        $schema = $host->mapping(Repeater::make($collection)->relationship()->components(array_map(TextInput::make(...), array_keys($addedItem))))->record($owner)->fill($owner);
        $host->data[$collection]['added'] = $addedItem;
        $schema->getData();
        $this->entityManager()->flush();

        expect(array_keys($host->data[$collection]))->toContain('added');

        $schema->loadStateFromRelationships();
        $statements = count($this->statementsRun());
        $schema->getData();
        $this->entityManager()->flush();

        expect(array_keys($host->data[$collection]))->toEqualCanonicalizing($keys)
            ->and(array_slice($this->statementsRun(), $statements))->toBe([]);
    })->with([
        'a generated id' => [
            function (): VettedBinder {
                $binder = new VettedBinder();
                $binder->addSheet(new VettedBinderSheet('One'));

                return $binder;
            },
            'sheets',
            ['heading' => 'Two'],
            ['record-1', 'record-2'],
        ],
        'an assigned key of two scalars' => [
            function (): NurseryBench {
                $bench = new NurseryBench();
                $bench->addPot(new BenchPot(bay: 'north', slot: '1', plant: 'basil'));

                return $bench;
            },
            'pots',
            ['bay' => 'south', 'slot' => '2', 'plant' => 'thyme'],
            ['record-north~1', 'record-south~2'],
        ],
        'an assigned key of an association and a scalar' => [
            function (): Larder {
                $larder = new Larder('Cellar');
                $larder->addCrock(new LarderCrock(shelf: 'top', label: 'Plums'));

                return $larder;
            },
            'crocks',
            ['shelf' => 'mid', 'label' => 'Pears'],
            ['record-1~top', 'record-1~mid'],
        ],
    ]);

    it("saves a new item on a blank single assigned key, which the host's reload then refuses to key", function (): void {
        $larder = new Larder('Cellar');
        $larder->addBin(new LarderBin('dry'));
        $this->seed($larder);
        $larder = $this->reload($larder);
        $host = $this->validatingRecordMappingHost();
        $schema = $host->mapping(Repeater::make('bins')->relationship()->components([
            Repeater::make('notes')->relationship()->components([TextInput::make('text')]),
        ]))->record($larder)->fill($larder);
        $host->data['bins']['added'] = ['notes' => []];
        $schema->getData();
        $this->entityManager()->flush();

        expect($this->entityManager()->getRepository(LarderBin::class)->count(['code' => '']))->toBe(1)
            ->and(fn (): Schema => $schema->loadStateFromRelationships())->toThrow(LogicException::class, sprintf('The identifier of [%s] is not a printable value. It holds code "".', LarderBin::class));
    });

    it("reads the association it names, and refuses a name that resolves to none in a field's words", function (): void {
        $binder = new VettedBinder();
        $binder->addSheet(new VettedBinderSheet('One'));
        $this->seed($binder);
        $binder = $this->reload($binder);
        $host = new RecordMappingHost();

        $host->mapping(Repeater::make('folios')->relationship('sheets')->components([TextInput::make('heading')]))->record($binder)->fill($binder);

        expect($host->data['folios'])->toBe(['record-1' => ['heading' => 'One']]);

        $schema = $host->mapping(Repeater::make('sheets')->relationship(static fn (): ?string => null)->components([TextInput::make('heading')]))->record($binder);

        expect(fn (): Schema => $schema->fill($binder))->toThrow(LogicException::class, "The [sheets] field was asked for its relationship, but relationship() named none, and the field's own name stands in only when relationship() is called without a name.");
    });

    it('refuses an association it cannot repeat over as it fills, naming what to map instead', function (string $association, string $message): void {
        $atelier = new Atelier('North');
        $schema = new RecordMappingHost()->mapping(Repeater::make($association)->relationship()->components([TextInput::make('name')]))->record($atelier);

        expect(fn (): Schema => $schema->fill($atelier))->toThrow(LogicException::class, $message);
    })->with([
        'a to-one association' => ['lease', sprintf('The [lease] repeater declares relationship("lease"), but that association holds a single %s rather than a collection, so there is nothing to repeat. Use a Select for a to-one association.', AtelierLease::class)],
        'a collection not mapped orphanRemoval' => ['apprentices', 'The [apprentices] repeater declares relationship("apprentices"), but that association is not mapped orphanRemoval, so an item the user deletes would only be detached and would survive the flush. Map the association orphanRemoval: true — a repeater edits records the parent owns — or drop relationship() and diff the collection by hand after getData().'],
    ]);

    it('refuses simple() and itemsAs() beside it in either order, and a query scope', function (): void {
        expect(fn (): Repeater => Repeater::make('sheets')->relationship()->simple(TextInput::make('heading')))
            ->toThrow(LogicException::class, "simple() cannot compose with relationship(): a related record is mapped by the item schema's own fields, which the simple pluck would reduce to one scalar. Declare that one field with components() instead.")
            ->and(fn (): Repeater => Repeater::make('sheets')->simple(TextInput::make('heading'))->relationship())
            ->toThrow(LogicException::class, "relationship() cannot compose with simple(): a related record is mapped by the item schema's own fields, which the simple pluck would reduce to one scalar. Declare that one field with components() instead.")
            ->and(fn (): Repeater => Repeater::make('sheets')->relationship()->itemsAs(LineItem::class))
            ->toThrow(LogicException::class, "itemsAs() cannot compose with relationship(): the relationship already constructs each item as the association's target entity, and a freshly built value object would lose its identity. Drop itemsAs().")
            ->and(fn (): Repeater => Repeater::make('sheets')->itemsAs(LineItem::class)->relationship())
            ->toThrow(LogicException::class, "relationship() cannot compose with itemsAs(): the relationship already constructs each item as the association's target entity, and a freshly built value object would lose its identity. Drop itemsAs().")
            ->and(fn (): Repeater => Repeater::make('sheets')->relationship(modifyQueryUsing: static fn (mixed $query): mixed => $query))
            ->toThrow(LogicException::class, "relationship() was passed a modifyQueryUsing scope, but the items come from the collection the record already holds rather than from a query of their own, so the scope would silently never run. Scope the association itself with Doctrine's #[ORM\\OrderBy] or a filtered collection.");
    });

    describe('orderColumn()', function (): void {
        it('stamps each child with its 0-based place in state order after the callbacks, a declined save keeping its old place and a declined create taking none', function (): void {
            $larder = new Larder('Cellar');
            $larder->addJar(new LarderJar('Plums', position: 0));
            $larder->addJar(new LarderJar('Figs', position: 1));
            $larder->addJar(new LarderJar('Pears', position: 2));
            $this->seed($larder);
            $larder = $this->reload($larder);
            $host = $this->validatingRecordMappingHost();
            $schema = $host->mapping(
                Repeater::make('jars')
                    ->relationship()
                    ->orderColumn('position')
                    ->mutateRelationshipDataBeforeCreateUsing(static fn (): ?array => null)
                    ->mutateRelationshipDataBeforeSaveUsing(static fn (array $state): ?array => ($state['label'] === 'Plums') ? null : [...$state, 'position' => 99])
                    ->components([TextInput::make('label')]),
            )->record($larder)->fill($larder);
            $host->data['jars'] = [
                'record-3' => $host->data['jars']['record-3'],
                'added' => ['label' => 'Quinces'],
                'record-1' => $host->data['jars']['record-1'],
                'record-2' => $host->data['jars']['record-2'],
            ];
            $schema->getData();
            $this->entityManager()->flush();
            $this->entityManager()->clear();

            expect(array_column(array_map(static fn (LarderJar $jar): array => [$jar->label, $jar->position], $this->reload($larder)->jars->toArray()), 1, 0))
                ->toEqual(['Pears' => 0, 'Plums' => 0, 'Figs' => 2]);
        });

        it('turns reordering on only for a column declared after relationship() that resolves to one', function (Closure $repeater, int $handles): void {
            $crawler = $this->renderSchemaComponent($repeater()->components([TextInput::make('label')]), ['jars' => ['a' => ['label' => 'Plums'], 'b' => ['label' => 'Figs']]]);

            expect($crawler->filter(FormsSelectors::REPEATER_ITEM))->toHaveCount(2)
                ->and($crawler->filter(FormsSelectors::REPEATER_ITEM . ' button' . SupportSelectors::SORTABLE_HANDLE))->toHaveCount($handles);
        })->with([
            'orderColumn() after relationship()' => [fn (): Repeater => Repeater::make('jars')->relationship()->orderColumn('position'), 2],
            'orderColumn() before relationship()' => [fn (): Repeater => Repeater::make('jars')->orderColumn('position')->relationship(), 0],
            'a column closure resolving to none' => [fn (): Repeater => Repeater::make('jars')->relationship()->orderColumn(static fn (): ?string => null), 0],
        ]);
    });

    describe('the mutate callbacks', function (): void {
        beforeEach(function (): void {
            $binder = new VettedBinder();
            $binder->addSheet(new VettedBinderSheet('One'));
            $binder->addSheet(new VettedBinderSheet('Two'));
            $this->seed($binder);
            $this->binder = $this->reload($binder);
        });

        it('hands each callback the item as its state, and the save callback the child it names, typed by its class', function (): void {
            $handed = [];
            $host = $this->validatingRecordMappingHost();
            $schema = $host->mapping(
                Repeater::make('sheets')
                    ->relationship()
                    ->mutateRelationshipDataBeforeFillUsing(static fn (array $state): array => [...$state, 'heading' => mb_strtoupper($state['heading'])])
                    ->mutateRelationshipDataBeforeCreateUsing(static fn (array $state): array => [...$state, 'status' => 'Added'])
                    ->mutateRelationshipDataBeforeSaveUsing(static function (array $state, VettedBinderSheet $sheet, object $record) use (&$handed): array {
                        $handed[] = [$sheet, $record];

                        return [...$state, 'heading' => "{$state['heading']}!"];
                    })
                    ->components([TextInput::make('heading'), TextInput::make('status')]),
            )->record($this->binder)->fill($this->binder);

            expect($host->data['sheets'])->toEqual([
                'record-1' => ['heading' => 'ONE', 'status' => 'Filed'],
                'record-2' => ['heading' => 'TWO', 'status' => 'Filed'],
            ]);

            $one = array_find($this->binder->sheets->toArray(), static fn (VettedBinderSheet $sheet): bool => $sheet->heading === 'One');
            unset($host->data['sheets']['record-2']);
            $host->data['sheets']['added'] = ['heading' => 'Three', 'status' => 'Filed'];
            $schema->getData();
            $this->entityManager()->flush();
            $this->entityManager()->clear();

            $stored = array_map(static fn (VettedBinderSheet $sheet): array => [$sheet->heading, $sheet->status], $this->reload($this->binder)->sheets->toArray());
            sort($stored);

            expect($handed)->toBe([[$one, $one]])
                ->and($stored)->toBe([['ONE!', 'Filed'], ['Three', 'Added']]);
        });

        it('declines an item a create or save callback returns null for, keeping the declined child in the collection', function (): void {
            $host = $this->validatingRecordMappingHost();
            $schema = $host->mapping(
                Repeater::make('sheets')
                    ->relationship()
                    ->mutateRelationshipDataBeforeCreateUsing(static fn (): ?array => null)
                    ->mutateRelationshipDataBeforeSaveUsing(static fn (array $state, VettedBinderSheet $sheet): ?array => ($sheet->heading === 'One') ? null : $state)
                    ->components([TextInput::make('heading')]),
            )->record($this->binder)->fill($this->binder);
            $host->data['sheets']['record-1']['heading'] = 'Uno';
            $host->data['sheets']['record-2']['heading'] = 'Dos';
            $host->data['sheets']['added'] = ['heading' => 'Tres'];
            $schema->getData();
            $this->entityManager()->flush();
            $this->entityManager()->clear();

            expect(array_map(static fn (VettedBinderSheet $sheet): string => $sheet->heading, $this->reload($this->binder)->sheets->toArray()))->toEqualCanonicalizing(['One', 'Dos'])
                ->and($this->entityManager()->getRepository(VettedBinderSheet::class)->count([]))->toBe(2);
        });

        it('refuses a callback that returns anything but the item or null, naming the repeater', function (): void {
            $host = $this->validatingRecordMappingHost();
            $schema = $host->mapping(Repeater::make('sheets')->relationship()->mutateRelationshipDataBeforeSaveUsing(static fn (): string => 'One')->components([TextInput::make('heading')]))->record($this->binder)->fill($this->binder);

            expect(fn (): object => $schema->getData())->toThrow(LogicException::class, 'A relationship mutation callback on the [sheets] repeater returned string where the mutated item array was expected. Return the array the callback was handed, or null to skip the item.');
        });

        it('refuses a fill callback that declines an item', function (): void {
            $schema = new RecordMappingHost()->mapping(Repeater::make('sheets')->relationship()->mutateRelationshipDataBeforeFillUsing(static fn (): ?array => null)->components([TextInput::make('heading')]))->record($this->binder);

            expect(fn (): Schema => $schema->fill($this->binder))->toThrow(LogicException::class, "The [sheets] repeater's mutateRelationshipDataBeforeFillUsing callback returned null, but a fill has no item to skip — only the create and save callbacks may decline one. Return the array the callback was handed.");
        });
    });

    describe('the fill and save arms', function (): void {
        it('fills an item past a field its child does not declare, leaving that field empty', function (): void {
            $larder = new Larder('Cellar');
            $larder->addJar(new LarderJar('Plums'));
            $this->seed($larder);
            $larder = $this->reload($larder);
            $host = new RecordMappingHost();
            $host->mapping(Repeater::make('jars')->relationship()->components([TextInput::make('label'), TextInput::make('confirm')->dehydrated(false)]))->record($larder)->fill($larder);

            expect(array_values($host->data['jars']))->toBe([['label' => 'Plums', 'confirm' => null]]);
        });

        it('fills a date and an enum the item fields cast from the child, so a save that touches neither keeps them', function (): void {
            $larder = new Larder('Cellar');
            $larder->addJar(new LarderJar('Plums', new DateTimeImmutable('2026-05-01'), JarSeal::Wax));
            $this->seed($larder);
            $larder = $this->reload($larder);
            $host = $this->validatingRecordMappingHost();
            $schema = $host->mapping(Repeater::make('jars')->relationship()->components([
                TextInput::make('label'),
                DateTimePicker::make('sealedOn')->time(false),
                Select::make('seal')->options(JarSeal::class),
            ]))->record($larder)->fill($larder);

            expect($host->data['jars'])->toBe(['record-1' => ['label' => 'Plums', 'sealedOn' => '2026-05-01', 'seal' => 'wax']]);

            $host->data['jars']['record-1']['label'] = 'Damsons';
            $schema->getData();
            $this->entityManager()->flush();
            $this->entityManager()->clear();
            $jar = $this->reload($larder)->jars->first();

            expect([$jar?->label, $jar?->sealedOn?->format('Y-m-d'), $jar?->seal])->toBe(['Damsons', '2026-05-01', JarSeal::Wax]);
        });

        it('refuses an item naming a child the collection no longer holds', function (): void {
            $binder = new VettedBinder();
            $binder->addSheet(new VettedBinderSheet('One'));
            $this->seed($binder);
            $binder = $this->reload($binder);
            $host = $this->validatingRecordMappingHost();
            $schema = $host->mapping(Repeater::make('sheets')->relationship()->components([TextInput::make('heading')]))->record($binder)->fill($binder);
            $binder->removeSheet($binder->sheets->first());

            expect(fn (): object => $schema->getData())->toThrow(LogicException::class, 'The [sheets] repeater carries item "record-1", but the [sheets] association no longer holds a record with that identifier — it was deleted or detached since the schema was filled. Fill the schema from the record again before saving.');
        });

        it('keys a child whose identifier holds a dot without one, apart from a child whose identifier spells that key, and hands and saves each item its own child', function (): void {
            $larder = new Larder('Pantry');
            $dotted = new LarderBin('dry.goods');
            $dotted->addNote(new LarderBinNote('rice'));

            $larder->addBin($dotted);
            $larder->addBin(new LarderBin('dry=2Egoods'));
            $this->seed($larder);
            $larder = $this->reload($larder);
            $handed = [];
            $host = $this->validatingRecordMappingHost();
            $schema = $host->mapping(Repeater::make('bins')->relationship()->components([
                TextInput::make('code')->label(static function (TextInput $component, ?object $record) use (&$handed): string {
                    $handed[$component->getStatePath()] = ($record instanceof LarderBin) ? $record->code : null;

                    return 'Code';
                }),
                Repeater::make('notes')->relationship()->components([TextInput::make('text')]),
            ]))->record($larder)->fill($larder);
            $bins = $host->data['bins'];
            ksort($bins);

            expect($bins)->toBe([
                'record-dry=2Egoods' => ['notes' => [], 'code' => 'dry=2Egoods'],
                'record=dry=2Egoods' => ['notes' => ['record-1' => ['text' => 'rice']], 'code' => 'dry.goods'],
            ]);

            $read = [];

            foreach ($schema->getComponent('bins')->getItems() as $item) {
                $code = $item->getComponents(withActions: false)[0];
                $code->getLabel();
                $read[$code->getStatePath()] = $code->getState();
            }

            ksort($handed);
            ksort($read);

            expect($read)->toBe(['data.bins.record-dry=2Egoods.code' => 'dry=2Egoods', 'data.bins.record=dry=2Egoods.code' => 'dry.goods'])
                ->and($handed)->toBe($read);

            $host->data['bins']['record=dry=2Egoods']['notes']['record-1']['text'] = 'oats';
            $host->data['bins']['record-dry=2Egoods']['notes']['added'] = ['text' => 'salt'];
            $schema->getData();
            $this->entityManager()->flush();
            $this->entityManager()->clear();
            $stored = [];

            foreach ($this->reload($larder)->bins as $bin) {
                $stored[$bin->code] = array_map(static fn (LarderBinNote $note): string => $note->text, $bin->notes->toArray());
            }

            ksort($stored);

            expect($stored)->toBe(['dry.goods' => ['oats'], 'dry=2Egoods' => ['salt']]);
        });
    });

    describe('an incomplete identifier', function (): void {
        it('refuses a new item whose key half no component supplies, naming the repeater and the class', function (Closure $owner, string $collection, array $addedItem, string $class): void {
            $owner = $owner();
            $this->seed($owner);
            $owner = $this->reload($owner);
            $host = $this->validatingRecordMappingHost();
            $schema = $host->mapping(Repeater::make($collection)->relationship()->components(array_map(TextInput::make(...), array_keys($addedItem))))->record($owner)->fill($owner);
            $host->data[$collection]['added'] = $addedItem;

            expect(fn (): object => $schema->getData())->toThrow(LogicException::class, sprintf("The [%s] repeater is saving an item of [%s] whose identifier is incomplete, so no record key can be minted for it. Put every identifier field in the repeater's components() — enabled and dehydrated for a new item — or build the child in mutateRelationshipDataBeforeCreateUsing().", $collection, $class));
        })->with([
            'a key of two scalars' => [fn (): NurseryBench => new NurseryBench(), 'pots', ['bay' => 'north', 'plant' => 'basil'], BenchPot::class],
            'a key of an association and a scalar' => [fn (): Larder => new Larder('Cellar'), 'crocks', ['label' => 'Plums'], LarderCrock::class],
        ]);

        it('refuses a new item whose key half is hidden or disabled', function (Closure $slot): void {
            $this->seed($bench = new NurseryBench());
            $bench = $this->reload($bench);
            $host = $this->validatingRecordMappingHost();
            $schema = $host->mapping(Repeater::make('pots')->relationship()->components([TextInput::make('bay'), $slot(), TextInput::make('plant')]))->record($bench)->fill($bench);
            $host->data['pots']['added'] = ['bay' => 'north', 'slot' => '1', 'plant' => 'basil'];

            expect(fn (): object => $schema->getData())->toThrow(LogicException::class, sprintf('The [pots] repeater is saving an item of [%s] whose identifier is incomplete', BenchPot::class));
        })->with([
            'hidden' => [fn (): TextInput => TextInput::make('slot')->hidden()],
            'disabled' => [fn (): TextInput => TextInput::make('slot')->disabled()],
        ]);

        it('saves a new item whose key half is disabled but dehydrated', function (): void {
            $this->seed($bench = new NurseryBench());
            $bench = $this->reload($bench);
            $host = $this->validatingRecordMappingHost();
            $schema = $host->mapping(Repeater::make('pots')->relationship()->components([TextInput::make('bay'), TextInput::make('slot')->disabled()->dehydrated(), TextInput::make('plant')]))->record($bench)->fill($bench);
            $host->data['pots']['added'] = ['bay' => 'north', 'slot' => '1', 'plant' => 'basil'];
            $schema->getData();
            $this->entityManager()->flush();
            $this->entityManager()->clear();

            expect(array_map(static fn (BenchPot $pot): array => [$pot->bay, $pot->slot, $pot->plant], $this->reload($bench)->pots->toArray()))->toBe([['north', '1', 'basil']]);
        });

        it('saves an existing item whose key half is disabled', function (): void {
            $bench = new NurseryBench();
            $bench->addPot(new BenchPot(bay: 'north', slot: '1', plant: 'basil'));
            $this->seed($bench);
            $bench = $this->reload($bench);
            $host = $this->validatingRecordMappingHost();
            $schema = $host->mapping(Repeater::make('pots')->relationship()->components([TextInput::make('bay'), TextInput::make('slot')->disabled(), TextInput::make('plant')]))->record($bench)->fill($bench);
            $host->data['pots']['record-north~1']['plant'] = 'mint';
            $schema->getData();
            $this->entityManager()->flush();
            $this->entityManager()->clear();

            expect($this->reload($bench)->pots->first()?->plant)->toBe('mint');
        });

        it("saves a new item under an owner not yet flushed, whose identifier waits on the owner's", function (): void {
            $larder = new Larder('Cellar');
            $host = $this->validatingRecordMappingHost();
            $schema = $host->mapping(Repeater::make('crocks')->relationship()->components([TextInput::make('shelf'), TextInput::make('label')]))->record($larder)->fill($larder);
            $host->data['crocks'] = ['added' => ['shelf' => 'top', 'label' => 'Plums']];
            $schema->getData();
            $this->entityManager()->persist($larder);
            $this->entityManager()->flush();
            $this->entityManager()->clear();

            expect(array_map(static fn (LarderCrock $crock): array => [$crock->shelf, $crock->label], $this->reload($larder)->crocks->toArray()))->toBe([['top', 'Plums']]);
        });

        it("refuses an existing item's edited key half as unchangeable, and puts it back", function (): void {
            $bench = new NurseryBench();
            $bench->addPot(new BenchPot(bay: 'north', slot: '1', plant: 'basil'));
            $this->seed($bench);
            $bench = $this->reload($bench);
            $host = $this->validatingRecordMappingHost();
            $schema = $host->mapping(Repeater::make('pots')->relationship()->components([TextInput::make('bay'), TextInput::make('slot'), TextInput::make('plant')]))->record($bench)->fill($bench);
            $host->data['pots']['record-north~1']['bay'] = 'east';

            expect(fn (): object => $schema->getData())->toThrow(function (ValidationException $exception): void {
                expect($exception->getErrors())->toBe(['data.pots.record-north~1.bay' => ['This value cannot be changed.']]);
            })
                ->and($bench->pots->first()?->bay)->toBe('north');
        });
    });

    describe('a relation-backed field inside an item', function (): void {
        beforeEach(function (): void {
            $larder = new Larder('Cellar');
            $larder->supplier = new Product('Larder Co');

            $own = $larder->supplier;
            $larder->addJar(new LarderJar('Plums', supplier: $jars = new Product('Jar Co')));
            $larder->addJar(new LarderJar('Figs', supplier: $jars));
            $this->seed($own, $jars, new Product('Third Co'), $larder);
            $this->larder = $this->reload($larder);
        });

        it("fills and saves the field against the child its item maps, never the owner's association of that name", function (): void {
            $host = $this->validatingRecordMappingHost();
            $schema = $host->mapping(Repeater::make('jars')->relationship()->components([
                TextInput::make('label'),
                Select::make('supplier')->relationship(titleAttribute: 'name'),
            ]))->record($this->larder)->fill($this->larder);

            expect(array_column(array_values($host->data['jars']), 'supplier', 'label'))->toEqual(['Plums' => '2', 'Figs' => '2']);

            $plumsKey = array_find_key($host->data['jars'], static fn (array $item): bool => $item['label'] === 'Plums');
            $host->data['jars'][$plumsKey]['supplier'] = '3';
            $host->data['jars']['added'] = ['label' => 'Pears', 'supplier' => '3'];
            $schema->getData();
            $this->entityManager()->flush();
            $this->entityManager()->clear();
            $larder = $this->reload($this->larder);

            expect(array_column(array_map(static fn (LarderJar $jar): array => [$jar->label, $jar->supplier?->name], $larder->jars->toArray()), 1, 0))->toEqual(['Plums' => 'Third Co', 'Figs' => 'Jar Co', 'Pears' => 'Third Co'])
                ->and($larder->supplier?->name)->toBe('Larder Co');
        });

        it('hands the field what the save callback returned, and skips it with an item the callback declines', function (): void {
            $host = $this->validatingRecordMappingHost();
            $schema = $host->mapping(
                Repeater::make('jars')
                    ->relationship()
                    ->mutateRelationshipDataBeforeSaveUsing(static fn (array $state): ?array => ($state['label'] === 'Plums') ? [...$state, 'supplier' => '1'] : null)
                    ->components([TextInput::make('label'), Select::make('supplier')->relationship(titleAttribute: 'name')]),
            )->record($this->larder)->fill($this->larder);

            foreach (array_keys($host->data['jars']) as $key) {
                $host->data['jars'][$key]['supplier'] = '3';
            }

            $schema->getData();
            $this->entityManager()->flush();
            $this->entityManager()->clear();

            expect(array_column(array_map(static fn (LarderJar $jar): array => [$jar->label, $jar->supplier?->name], $this->reload($this->larder)->jars->toArray()), 1, 0))->toEqual(['Plums' => 'Larder Co', 'Figs' => 'Jar Co']);
        });

        it('saves a relationship repeater inside an item against the child its item maps', function (): void {
            $larder = new Larder('Pantry');
            $bin = new LarderBin('dry');
            $bin->addNote(new LarderBinNote('rice'));

            $larder->addBin($bin);
            $this->seed($larder);
            $larder = $this->reload($larder);
            $host = $this->validatingRecordMappingHost();
            $schema = $host->mapping(Repeater::make('bins')->relationship()->components([
                TextInput::make('code'),
                Repeater::make('notes')->relationship()->components([TextInput::make('text')]),
            ]))->record($larder)->fill($larder);

            expect($host->data['bins'])->toBe(['record-dry' => ['notes' => ['record-1' => ['text' => 'rice']], 'code' => 'dry']]);

            $host->data['bins']['record-dry']['notes']['record-1']['text'] = 'oats';
            $host->data['bins']['record-dry']['notes']['added'] = ['text' => 'beans'];
            $host->data['bins']['added'] = ['code' => 'wet', 'notes' => ['added' => ['text' => 'lentils']]];
            $schema->getData();
            $this->entityManager()->flush();
            $this->entityManager()->clear();

            $stored = [];

            foreach ($this->reload($larder)->bins as $bin) {
                $notes = array_map(static fn (LarderBinNote $note): string => $note->text, $bin->notes->toArray());
                sort($notes);
                $stored[$bin->code] = $notes;
            }

            ksort($stored);

            expect($stored)->toBe(['dry' => ['beans', 'oats'], 'wet' => ['lentils']]);
        });
    });

    describe('the item schema record', function (): void {
        it('hands a closure in a loaded item its child, and one in an added item or in an item naming a child the record no longer holds no record rather than the owner', function (): void {
            $larder = new Larder('Cellar');
            $larder->addJar(new LarderJar('Plums'));
            $this->seed($larder);
            $larder = $this->reload($larder);
            $handed = [];
            $host = new RecordMappingHost();
            $schema = $host->mapping(Repeater::make('jars')->relationship()->components([
                TextInput::make('label')->label(static function (?object $record) use (&$handed): string {
                    $handed[] = $record;

                    return 'Label';
                }),
            ]))->record($larder)->fill($larder);
            $host->data['jars']['added'] = ['label' => 'Figs'];
            $host->data['jars']['record-' . ($larder->jars->first()->id + 1)] = ['label' => 'Gone'];

            foreach ($schema->getComponent('jars')->getItems() as $item) {
                $item->getComponents(withActions: false)[0]->getLabel();
            }

            expect($handed)->toBe([$larder->jars->first(), null, null]);
        });

        it("probes a unique() field in an item against its child's class, ignoring the child a loaded item maps", function (): void {
            $pantry = new Larder('Pantry');
            $pantry->addJar(new LarderJar('Plums'));

            $cellar = new Larder('Cellar');
            $cellar->addJar(new LarderJar('Figs', position: 0));
            $cellar->addJar(new LarderJar('Pears', position: 1));
            $this->seed($pantry, $cellar);
            $cellar = $this->reload($cellar);
            $figs = $cellar->jars->first();
            $host = $this->validatingRecordMappingHost();
            $schema = $host->mapping(Repeater::make('jars')->relationship()->components([TextInput::make('label')->unique()]))->record($cellar)->fill($cellar);
            $host->data['jars']["record-{$figs->id}"]['label'] = 'Plums';
            $host->data['jars']['added'] = ['label' => 'Plums'];

            expect(fn (): object => $schema->getData())->toThrow(function (ValidationException $exception) use ($figs): void {
                expect($exception->getErrors())->toBe([
                    "data.jars.record-{$figs->id}.label" => ['This value is already taken.'],
                    'data.jars.added.label' => ['This value is already taken.'],
                ]);
            });
        });
    });
});

it('translates its item label, the label between items and its add labels given as messages', function (): void {
    $items = ['lines' => ['a' => ['name' => 'Ada'], 'b' => ['name' => 'Grace']]];
    $render = $this->renderSchemaComponent(Repeater::make('lines')
        ->itemLabel(t('repeater.item', [], 'fixtures-forms'))
        ->labelBetweenItems(t('repeater.between', [], 'fixtures-forms'))
        ->addActionLabel(t('repeater.add', [], 'fixtures-forms'))
        ->components([TextInput::make('name')]), $items);

    expect($render->filter('.sy-fo-repeater-item-header-label')->each(static fn (Crawler $label): string => $label->text()))->toBe(['Line (translated)', 'Line (translated)'])
        ->and($render->filter('.sy-fo-repeater-label-between-items')->each(static fn (Crawler $label): string => $label->text()))->toBe(['or (translated)'])
        ->and($render)->toContainElement(FormsSelectors::REPEATER_ADD_BUTTON, 'Add a line (translated)');

    expect($this->renderSchemaComponent(Repeater::make('lines')->addBetweenActionLabel(t('repeater.insert', [], 'fixtures-forms'))->addBetweenAction(static fn (Action $action): Action => $action->visible())->components([TextInput::make('name')]), $items)->filter('.sy-fo-repeater-add-between-items button')->text())->toBe('Slot in (translated)');
});
