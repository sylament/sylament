<?php

declare(strict_types=1);

use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Group;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Entity\Article;
use Sylament\Tests\Fixtures\Entity\Invoice;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Schemas\Record\CardAuthor;
use Sylament\Tests\Fixtures\Schemas\Record\CatalogueCard;
use Sylament\Tests\Fixtures\Schemas\RecordMappingHost;

describe('the binding', function (): void {
    it("answers a record bound on the component over its container's", function (): void {
        $article = new Article(title: 'Own');
        $section = Section::make()->record($article);
        Schema::make()->record(new Product(name: 'Container'))->components([$section])->getComponents();

        expect($section->getRecord())->toBe($article)
            ->and($section->getRecordClass())->toBe(Article::class)
            ->and($section->getRecordInstance())->toBe($article);
    });

    it("falls back to its container's record, unless asked to stop at the component", function (): void {
        $product = new Product(name: 'Container');
        $section = Section::make();
        Schema::make()->record($product)->components([$section])->getComponents();

        expect($section->getRecord())->toBe($product)
            ->and($section->getRecordClass())->toBe(Product::class)
            ->and($section->getRecordInstance())->toBe($product)
            ->and($section->getRecord(withContainerRecord: false))->toBeNull();
    });

    it('declares a class without binding an instance, and builds one when an instance is asked for', function (): void {
        $section = Section::make()->record(CardAuthor::class);
        Schema::make()->record(new Product(name: 'Container'))->components([$section])->getComponents();

        expect($section->getRecord())->toBeNull()
            ->and($section->getRecordClass())->toBe(CardAuthor::class)
            ->and($section->getRecordInstance())->toBeInstanceOf(CardAuthor::class);
    });

    it("binds raw record data without a class, and builds no instance of its own from it", function (): void {
        $product = new Product(name: 'Container');
        $section = Section::make()->record(['title' => 'Raw']);
        Schema::make()->record($product)->components([$section])->getComponents();

        expect($section->getRecord())->toBe(['title' => 'Raw'])
            ->and($section->getRecordClass())->toBeNull()
            ->and($section->getRecordInstance())->toBe($product);
    });

    it('refuses by name a class whose constructor requires arguments', function (): void {
        $section = Section::make()->record(Invoice::class);
        Schema::make()->components([$section])->getComponents();

        expect(fn (): ?object => $section->getRecordInstance())->toThrow(
            LogicException::class,
            'Cannot map a new record for ' . Section::class . ': ' . Invoice::class . ' requires constructor arguments, so the mapper cannot build it. Give the constructor defaults, or hand the component a record built with its arguments, through record().',
        );
    });

    it('evaluates a closure record again on every read', function (): void {
        $calls = 0;
        $section = Section::make()->record(function () use (&$calls): Article {
            $calls++;

            return new Article(title: "Read {$calls}");
        });
        Schema::make()->components([$section])->getComponents();

        expect($section->getRecord()->title)->toBe('Read 1')
            ->and($section->getRecord()->title)->toBe('Read 2')
            ->and($calls)->toBe(2);
    });

    it('tells a binding of its own from an inherited one without evaluating a closure binding', function (): void {
        $calls = 0;
        $bound = Section::make()->record(function () use (&$calls): Article {
            $calls++;

            return new Article(title: 'Own');
        });
        $inheriting = Section::make();
        Schema::make()->record(new Product(name: 'Container'))->components([$bound, $inheriting])->getComponents();

        expect($bound->hasOwnRecord())->toBeTrue()
            ->and($inheriting->hasOwnRecord())->toBeFalse()
            ->and($calls)->toBe(0);
    });
});

describe('getRecordStatePath()', function (): void {
    it('answers the path relative to the root record', function (Component $component, ?string $expected): void {
        expect($component->getRecordStatePath())->toBe($expected);
    })->with([
        'the root state path stripped' => function (): array {
            $field = TextInput::make('author.name');
            Schema::make()->statePath('data')->components([$field])->getComponents();

            return [$field, 'author.name'];
        },
        'the whole path under a root without one' => function (): array {
            $field = TextInput::make('author.name');
            Schema::make()->components([$field])->getComponents();

            return [$field, 'author.name'];
        },
        'none for a component without a path' => function (): array {
            $section = Section::make();
            Schema::make()->components([$section])->getComponents();

            return [$section, null];
        },
        "none for a path-less component, whose state path is the root's" => function (): array {
            $section = Section::make();
            Schema::make()->statePath('data')->components([$section])->getComponents();

            return [$section, null];
        },
        'none for a path of nothing but a control character' => function (): array {
            $field = TextInput::make("\x01");
            Schema::make()->components([$field])->getComponents();

            return [$field, null];
        },
        "the root's own path for that path under a root state path" => function (): array {
            $field = TextInput::make("\x01");
            Schema::make()->statePath('data')->components([$field])->getComponents();

            return [$field, 'data'];
        },
        'the whole remembered path when a later root state path does not prefix it' => function (): array {
            $field = TextInput::make('title');
            $schema = Schema::make()->components([$field]);
            $schema->getComponents();

            $field->getStatePath();
            $schema->statePath('data');

            return [$field, 'title'];
        },
    ]);
});

describe('the nearest record frame', function (): void {
    it('is the root when nothing between binds, so its path is the root-relative one', function (): void {
        $field = TextInput::make('author.name');
        Schema::make()->statePath('data')->record(new CatalogueCard())->components([$field])->getComponents();

        expect($field->getNearestRecordClass())->toBe(CatalogueCard::class)
            ->and($field->getNearestRecordStatePath())->toBe('author.name')
            ->and($field->getRecordStatePath())->toBe('author.name');
    });

    it('is the nearest component binding a record, even a class-string that binds no instance', function (): void {
        $field = TextInput::make('name');
        $section = Section::make()->statePath('author')->record(CardAuthor::class)->components([$field]);
        Schema::make()->statePath('data')->record(new CatalogueCard())->components([$section])->getFlatComponents();

        expect($section->getRecord())->toBeNull()
            ->and($section->hasOwnRecord())->toBeTrue()
            ->and($field->getNearestRecordClass())->toBe(CardAuthor::class)
            ->and($field->getNearestRecordStatePath())->toBe('name')
            ->and($field->getRecordStatePath())->toBe('author.name');
    });

    it('is a nested schema binding a record of its own', function (): void {
        $field = TextInput::make('name');
        $group = Group::make(Schema::make()->statePath('author')->record(CardAuthor::class)->components([$field]));
        Schema::make()->statePath('data')->record(new CatalogueCard())->components([$group])->getFlatComponents();

        expect($field->getNearestRecordClass())->toBe(CardAuthor::class)
            ->and($field->getNearestRecordStatePath())->toBe('name');
    });

    it('excludes the component itself', function (): void {
        $section = Section::make()->statePath('author')->record(CardAuthor::class);
        Schema::make()->statePath('data')->record(new CatalogueCard())->components([$section])->getComponents();

        expect($section->getNearestRecordClass())->toBe(CatalogueCard::class)
            ->and($section->getNearestRecordStatePath())->toBe('author');
    });

    it('names no path for a component without one, though it still names the class', function (): void {
        $section = Section::make();
        Schema::make()->statePath('data')->record(new CatalogueCard())->components([$section])->getComponents();

        expect($section->getNearestRecordClass())->toBe(CatalogueCard::class)
            ->and($section->getNearestRecordStatePath())->toBeNull();
    });

    it("names no path where the component's path does not nest under the frame's", function (): void {
        $field = TextInput::make('name');
        $section = Section::make()->record(CardAuthor::class)->components([$field]);
        Schema::make()->record(new CatalogueCard())->components([$section])->getFlatComponents();
        $field->getStatePath();
        $section->statePath('author');

        expect($field->getNearestRecordStatePath())->toBeNull()
            ->and($field->getRecordStatePath())->toBe('name');
    });
});

describe('the relationship hooks', function (): void {
    it('runs the load hook with the record injected by name and by type, and answers its value, on a record never persisted', function (): void {
        $article = new Article(title: 'Unsaved');
        $section = Section::make()->statePath('author')->loadStateFromRelationshipsUsing(
            fn (object $record, Article $typed, Component $component): array => ['name' => $record->title, 'same' => $typed === $record, 'component' => $component::class],
        );
        Schema::make()->components([$section])->getComponents();

        expect($section->loadStateFromRelationships($article))->toBe(['name' => 'Unsaved', 'same' => true, 'component' => Section::class]);
    });

    it('runs the save hook with the record and the state injected', function (): void {
        $article = new Article(title: 'Unsaved');
        $section = Section::make()->statePath('author')->saveRelationshipsUsing(
            function (Article $record, mixed $state): void {
                $record->title = $state['name'];
            },
        );
        Schema::make()->components([$section])->getComponents();

        $section->saveRelationships($article, ['name' => 'Written']);

        expect($article->title)->toBe('Written');
    });
});

it("keeps the record path map on the root record when a component binds one of its own", function (): void {
    $card = new CatalogueCard();
    $card->author = new CardAuthor();
    $card->author->name = 'From the root';

    $own = new CardAuthor();
    $own->name = 'From the section';

    $host = new RecordMappingHost();
    $schema = $host->mapping(Section::make()->statePath('author')->record($own)->components([TextInput::make('name')]))->record($card);
    $schema->fill($card);
    $host->data['author']['name'] = 'Edited';
    $schema->getData();

    expect($card->author->name)->toBe('Edited')
        ->and($own->name)->toBe('From the section');
});
