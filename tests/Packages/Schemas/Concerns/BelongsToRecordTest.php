<?php

declare(strict_types=1);

use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Group;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Exceptions\ValidationException;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\Casts\DateTimeStateCast;
use Sylament\Schemas\State\Casts\EnumStateCast;
use Sylament\Tests\Fixtures\Entity\Invoice;
use Sylament\Tests\Fixtures\Entity\InvoiceLine;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Schemas\Record\CardAuthor;
use Sylament\Tests\Fixtures\Schemas\Record\CardShade;
use Sylament\Tests\Fixtures\Schemas\Record\CatalogueCard;
use Sylament\Tests\Fixtures\Schemas\Record\SealedCard;
use Sylament\Tests\Fixtures\Schemas\Record\VettedCard;
use Sylament\Tests\Fixtures\Schemas\RecordMappingHost;
use Sylament\Tests\Fixtures\Schemas\SubtreeOwningGroup;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

beforeEach(function (): void {
    $this->host = new RecordMappingHost();
    $this->host->setSchemaStateValidator($this::getContainer()->get(ValidatorInterface::class));
});

describe('the binding', function (): void {
    it('answers a bound instance, its class, and the instance itself to map onto', function (): void {
        $card = new CatalogueCard();
        $schema = Schema::make()->record($card);

        expect($schema->getRecord())->toBe($card)
            ->and($schema->getRecordClass())->toBe(CatalogueCard::class)
            ->and($schema->getRecordInstance())->toBe($card);
    });

    it('declares a class without binding an instance, and builds one to map onto', function (): void {
        $schema = Schema::make()->record(CatalogueCard::class);

        expect($schema->getRecord())->toBeNull()
            ->and($schema->getRecordClass())->toBe(CatalogueCard::class)
            ->and($schema->getRecordInstance())->toBeInstanceOf(CatalogueCard::class);
    });

    it('binds raw record data without a class, and has no instance to map onto', function (): void {
        $schema = Schema::make()->record(['title' => 'Raw']);

        expect($schema->getRecord())->toBe(['title' => 'Raw'])
            ->and($schema->getRecordClass())->toBeNull()
            ->and($schema->getRecordInstance())->toBeNull();
    });

    it('runs a closure record once and stays bound to what it first returned', function (): void {
        $calls = 0;
        $schema = Schema::make()->record(function () use (&$calls): Product {
            $calls++;

            return new Product(name: "Read {$calls}");
        });

        expect($schema->getRecord()->name)->toBe('Read 1')
            ->and($schema->getRecordClass())->toBe(Product::class)
            ->and($schema->getRecord()->name)->toBe('Read 1')
            ->and($calls)->toBe(1);
    });

    it("stops claiming a binding of its own once a closure record has resolved to nothing", function (): void {
        $schema = Schema::make()->record(fn (): ?object => null);

        expect($schema->hasOwnRecord())->toBeTrue()
            ->and($schema->getRecord())->toBeNull()
            ->and($schema->hasOwnRecord())->toBeFalse();
    });

    it("answers a nested schema the record of the component it hangs off, unless asked to skip it", function (): void {
        $product = new Product(name: 'Section');
        $section = Section::make()->record($product);
        Schema::make()->record(new CatalogueCard())->components([$section])->getComponents();
        $nested = $section->getChildSchema();

        expect($nested->hasOwnRecord())->toBeFalse()
            ->and($nested->getRecord())->toBe($product)
            ->and($nested->getRecordClass())->toBe(Product::class)
            ->and($nested->getRecord(withParentComponentRecord: false))->toBeNull();
    });

    it('refuses by name a class whose constructor requires arguments', function (): void {
        expect(fn (): ?object => Schema::make()->record(Invoice::class)->getRecordInstance())->toThrow(
            LogicException::class,
            'Cannot map a new record onto the schema: ' . Invoice::class . ' requires constructor arguments, so the mapper cannot build it. Give the constructor defaults, or hand the schema a record built with its arguments, through record().',
        );
    });

    it('surfaces reflection for a class it cannot reach', function (string $class): void {
        expect(fn (): ?object => Schema::make()->record($class)->getRecordInstance())->toThrow(ReflectionException::class);
    })->with([
        'a private constructor' => SealedCard::class,
        'no such class' => 'Sylament\Tests\Fixtures\Schemas\Record\MissingCard',
    ]);
});

describe('fill() from an object', function (): void {
    it('reads only the paths its components declare, then fills them as an array would', function (): void {
        $card = new CatalogueCard();
        $card->title = 'dune';
        $card->shelfMark = 'Private';

        $schema = $this->host->mapping(
            TextInput::make('title')->afterStateHydrated(fn (TextInput $component, string $state): mixed => $component->state(ucfirst($state))),
            TextInput::make('titel'),
        );

        $schema->fill($card);

        expect($this->host->data)->toBe(['title' => 'Dune', 'titel' => null]);
    });

    it('sources state without binding the record it read', function (): void {
        $card = new CatalogueCard();
        $card->title = 'Dune';

        $schema = $this->host->mapping(TextInput::make('title'));

        $schema->fill($card);

        expect($this->host->data)->toBe(['title' => 'Dune'])
            ->and($schema->getRecord())->toBeNull()
            ->and(fn (): object => $schema->getData())->toThrow(LogicException::class, 'Schema::getData() needs a record it can map onto');
    });

    it('answers the same read as an array, leaving the bag alone', function (): void {
        $card = new CatalogueCard();
        $card->title = 'Dune';
        $card->shelfMark = 'Private';

        $schema = $this->host->mapping(TextInput::make('title'));

        expect($schema->getStateFromRecord($card))->toBe(['title' => 'Dune'])
            ->and($this->host->data)->toBe([]);
    });

    it('reads leniently, filling null where the record cannot supply a value the bag can hold', function (TextInput $field, array $expected): void {
        $card = new CatalogueCard();
        $card->author = new CardAuthor();
        $card->shade = CardShade::Slate;
        $this->host->mapping($field)->fill($card);

        expect($this->host->data)->toBe($expected);
    })->with([
        'a typed property never assigned' => fn (): array => [TextInput::make('unassigned'), ['unassigned' => null]],
        'an object no cast flattens' => fn (): array => [TextInput::make('author'), ['author' => null]],
        'an enum no cast flattens' => fn (): array => [TextInput::make('shade'), ['shade' => null]],
        'a leaf beneath a null intermediate' => fn (): array => [TextInput::make('binding.material'), ['binding' => ['material' => null]]],
    ]);

    it("reads an object the field's own casts flatten", function (TextInput $field, string $path, string $expected): void {
        $card = new CatalogueCard();
        $card->shade = CardShade::Slate;
        $card->catalogued = new DateTimeImmutable('2026-03-04 05:06:07');
        $this->host->mapping($field)->fill($card);

        expect($this->host->data[$path])->toBe($expected);
    })->with([
        'an enum' => fn (): array => [TextInput::make('shade')->stateCast(new EnumStateCast(CardShade::class)), 'shade', 'slate'],
        'a date' => fn (): array => [TextInput::make('catalogued')->stateCast(new DateTimeStateCast('Y-m-d', 'Y-m-d', 'UTC')), 'catalogued', '2026-03-04'],
    ]);
});

describe('getData()', function (): void {
    it('validates, writes every declared leaf onto the bound record and answers it', function (): void {
        $card = new CatalogueCard();
        $card->title = 'Dune';
        $card->shelfMark = 'Kept';

        $schema = $this->host->mapping(TextInput::make('title')->required())->record($card);
        $schema->fill($card);
        $this->host->data['title'] = 'Dune Messiah';

        expect($schema->getData())->toBe($card)
            ->and($card->title)->toBe('Dune Messiah')
            ->and($card->shelfMark)->toBe('Kept');
    });

    it('builds the record a class-string declares and writes onto it', function (): void {
        $schema = $this->host->mapping(TextInput::make('title'))->record(CatalogueCard::class);
        $this->host->data['title'] = 'Dune';

        expect($schema->getData())->toBeInstanceOf(CatalogueCard::class)
            ->title->toBe('Dune');
    });

    it('refuses a schema with no record it can map onto', function (?array $record): void {
        $schema = $this->host->mapping(TextInput::make('title'))->record($record);

        expect(fn (): object => $schema->getData())->toThrow(
            LogicException::class,
            'Schema::getData() needs a record it can map onto — record() with a bound instance or a class-string to construct; an array record only supplies state and cannot be mapped onto.',
        );
    })->with([
        'raw record data' => [['title' => 'Raw']],
        'no record at all' => [null],
    ]);

    it('throws a failing validation before it touches the record', function (): void {
        $card = new CatalogueCard();
        $card->author = new CardAuthor();
        $card->author->name = 'Ada';

        $schema = $this->host->mapping(
            TextInput::make('title')->required(),
            Section::make()->statePath('author')->components([TextInput::make('name')]),
        )->record($card);
        $schema->fill($card);
        $this->host->data['author']['name'] = 'Grace';

        expect(fn (): object => $schema->getData())->toThrow(ValidationException::class)
            ->and($this->host->getErrorsForSchemaStatePath('data.title'))->toBe(['This value should not be blank.'])
            ->and($card->author->name)->toBe('Ada');
    });

    it("validates the mapped record after writing it, filing each violation under the field that maps it", function (): void {
        $card = new VettedCard();
        $schema = $this->host->mapping(
            TextInput::make('title'),
            Section::make()->statePath('author')->components([TextInput::make('name')]),
        )->record($card);
        $schema->fill($card);
        $this->host->data['title'] = VettedCard::WITHDRAWN;
        $this->host->data['author']['name'] = 'Al';

        expect(fn (): object => $schema->getData())->toThrow(ValidationException::class)
            ->and($this->host->getErrorsForSchemaStatePath('data.title'))->toBe(['That title is withdrawn.'])
            ->and($this->host->getErrorsForSchemaStatePath('data.author.name'))->toBe(['The author name is too short.'])
            ->and($card->title)->toBe(VettedCard::WITHDRAWN);
    });

    it("maps a state handed in without validating it, and still validates the record", function (): void {
        $card = new VettedCard();
        $schema = $this->host->mapping(
            TextInput::make('title')->required(),
            Section::make()->statePath('author')->components([TextInput::make('name')]),
        )->record($card);

        expect(fn (): object => $schema->getData(['title' => '', 'author' => ['name' => 'Al']]))->toThrow(ValidationException::class)
            ->and($card->title)->toBe('')
            ->and($this->host->getErrorsForSchemaStatePath('data.title'))->toBe([])
            ->and($this->host->getErrorsForSchemaStatePath('data.author.name'))->toBe(['The author name is too short.']);
    });

    it('writes strictly, refusing a path the record cannot take by name', function (string $path, array $state, string $exception, string $message): void {
        $schema = $this->host->mapping(TextInput::make($path))->record(new CatalogueCard());
        $this->host->data = $state;

        expect(fn (): object => $schema->getData())->toThrow($exception, $message);
    })->with([
        'a misspelled property' => ['titel', ['titel' => 'Dune'], NoSuchPropertyException::class, 'titel'],
        'a null intermediate no class can fill' => ['label.text', ['label' => ['text' => 'Spine']], LogicException::class, 'Cannot map state path "label.text" onto ' . CatalogueCard::class . ': "label" declares no object the mapper can construct'],
        'a null intermediate whose class needs arguments' => ['binding.material', ['binding' => ['material' => 'Cloth']], LogicException::class, 'requires constructor arguments'],
        'an object collection' => ['contributors.0.name', ['contributors' => [['name' => 'Ada']]], LogicException::class, 'contributors'],
    ]);
});

describe('the record path map', function (): void {
    it('round-trips a nested object in place', function (): void {
        $card = new CatalogueCard();
        $card->author = new CardAuthor();
        $author = $card->author;

        $author->name = 'Ada';
        $schema = $this->host->mapping(Section::make()->statePath('author')->components([TextInput::make('name')]))->record($card);

        $schema->fill($card);

        $filled = $this->host->data;
        $this->host->data['author']['name'] = 'Grace';
        $schema->getData();

        expect($filled)->toBe(['author' => ['name' => 'Ada']])
            ->and($card->author)->toBe($author)
            ->and($author->name)->toBe('Grace');
    });

    it('reads an array branch leaf by leaf and writes it back keeping the keys it does not map', function (): void {
        $card = new CatalogueCard();
        $card->meta = ['note' => 'First', 'unmanaged' => 'Kept'];

        $schema = $this->host->mapping(Section::make()->statePath('meta')->components([TextInput::make('note')]))->record($card);

        $schema->fill($card);

        $filled = $this->host->data;
        $this->host->data['meta']['note'] = 'Second';
        $schema->getData();

        expect($filled)->toBe(['meta' => ['note' => 'First']])
            ->and($card->meta)->toBe(['note' => 'Second', 'unmanaged' => 'Kept']);
    });

    it('builds a missing nested object from its declared type', function (): void {
        $schema = $this->host->mapping(Section::make()->statePath('author')->components([TextInput::make('name')]))->record(CatalogueCard::class);
        $this->host->data = ['author' => ['name' => 'Ada']];

        expect($schema->getData()->author)->toBeInstanceOf(CardAuthor::class)
            ->name->toBe('Ada');
    });

    it('keeps a field a leaf when a path-less decoration hangs off it', function (): void {
        $card = new CatalogueCard();
        $card->title = 'Dune';
        $this->host->mapping(TextInput::make('title')->helperText('As printed on the spine'))->fill($card);

        expect($this->host->data)->toBe(['title' => 'Dune']);
    });

    it('lets two fields that own no subtree share one path, both writing it', function (): void {
        $card = new CatalogueCard();
        $schema = $this->host->mapping(TextInput::make('title'), TextInput::make('title'))->record($card);
        $this->host->data['title'] = 'Dune';

        expect($schema->getData()->title)->toBe('Dune');
    });
});

describe('a component owning its record subtree', function (): void {
    it('reads its property whole and writes its value back whole, speaking for the fields inside it', function (): void {
        $card = new CatalogueCard();
        $card->meta = ['note' => 'First', 'undeclared' => 'Kept'];

        $schema = $this->host->mapping(SubtreeOwningGroup::make([TextInput::make('note')])->statePath('meta'))->record($card);

        $schema->fill($card);

        $filled = $this->host->data;
        $this->host->data['meta']['note'] = 'Second';
        $schema->getData();

        expect($filled)->toBe(['meta' => ['note' => 'First', 'undeclared' => 'Kept']])
            ->and($card->meta)->toBe(['note' => 'Second', 'undeclared' => 'Kept']);
    });

    it('refuses a component outside its tree whose path nests under its own', function (): void {
        $schema = $this->host->mapping(
            SubtreeOwningGroup::make([TextInput::make('note')])->statePath('meta'),
            TextInput::make('meta.extra'),
        )->record(new CatalogueCard());

        expect(fn (): Schema => $schema->fill(new CatalogueCard()))->toThrow(
            LogicException::class,
            TextInput::class . ' declares state path "meta.extra", which nests under "meta" — a path ' . SubtreeOwningGroup::class . ' maps wholesale, so this component would neither fill nor save. Move it inside that component\'s schema, or move its state path out from under "meta".',
        );
    });

    it('refuses a second component claiming its path', function (): void {
        $schema = $this->host->mapping(
            SubtreeOwningGroup::make([TextInput::make('note')])->statePath('meta'),
            TextInput::make('meta'),
        )->record(new CatalogueCard());

        expect(fn (): Schema => $schema->fill(new CatalogueCard()))->toThrow(
            LogicException::class,
            SubtreeOwningGroup::class . ' and ' . TextInput::class . ' both map record path "meta", which one of them maps wholesale',
        );
    });
});

describe('the nullable branch pass', function (): void {
    it('nulls a blank branch whole, writing none of its leaves', function (): void {
        $card = new CatalogueCard();
        $card->author = new CardAuthor();
        $card->author->name = 'Ada';

        $schema = $this->host->mapping(Section::make()->statePath('author')->nullWhenBlank()->components([TextInput::make('name')]))->record($card);
        $schema->fill($card);
        $this->host->data['author']['name'] = null;

        expect($schema->getData()->author)->toBeNull();
    });

    it('nulls the outer of two blank branches first, so the inner one is never asked', function (): void {
        $card = new CatalogueCard();
        $card->author = new CardAuthor();

        $schema = $this->host->mapping(
            Section::make()->statePath('author.address')->nullWhenBlank()->components([TextInput::make('city')]),
            Section::make()->statePath('author')->nullWhenBlank()->components([TextInput::make('name')]),
        )->record($card);
        $this->host->data = ['author' => ['name' => null, 'address' => ['city' => null]]];

        expect($schema->getData()->author)->toBeNull();
    });

    it('leaves a branch alone that the submission no longer carries', function (): void {
        $card = new CatalogueCard();
        $card->author = new CardAuthor();
        $author = $card->author;

        $schema = $this->host->mapping(
            TextInput::make('title'),
            Section::make()->statePath('author')->nullWhenBlank()->hidden()->components([TextInput::make('name')]),
        )->record($card);
        $this->host->data = ['title' => 'Dune', 'author' => ['name' => null]];

        expect($schema->getData())->title->toBe('Dune')
            ->author->toBe($author);
    });
});

describe('the relationship hooks', function (): void {
    it("fills a hooked path from its load hook, where the record's own read would skip the object", function (): void {
        $card = new CatalogueCard();
        $card->author = new CardAuthor();
        $card->author->name = 'Ada';
        $this->host->mapping(TextInput::make('author')->loadStateFromRelationshipsUsing(fn (CatalogueCard $record): ?string => $record->author?->name))->fill($card);

        expect($this->host->data)->toBe(['author' => 'Ada']);
    });

    it('hands a hooked path to its save hook, which owns the write, even on a record never saved', function (): void {
        $card = new CatalogueCard();
        $card->author = new CardAuthor();
        $author = $card->author;

        $received = [];
        $schema = $this->host->mapping(TextInput::make('author')->saveRelationshipsUsing(function (CatalogueCard $record, mixed $state) use (&$received): void {
            $received[] = [$record, $state];
        }))->record($card);
        $this->host->data['author'] = 'Grace';

        $schema->getData();

        expect($received)->toBe([[$card, 'Grace']])
            ->and($card->author)->toBe($author);
    });

    it('lets a hook own an object collection the record would refuse', function (): void {
        $card = new CatalogueCard();
        $schema = $this->host->mapping(TextInput::make('contributors')->saveRelationshipsUsing(function (CatalogueCard $record, array $state): void {
            foreach ($state as $name) {
                $contributor = new CardAuthor();
                $contributor->name = $name;
                $record->contributors->add($contributor);
            }
        }))->record($card);
        $this->host->data['contributors'] = ['Ada'];

        expect($schema->getData()->contributors->first()->name)->toBe('Ada');
    });

    it('runs no save hook when the validation fails', function (): void {
        $calls = 0;
        $schema = $this->host->mapping(
            TextInput::make('title')->required(),
            TextInput::make('author')->saveRelationshipsUsing(function () use (&$calls): void {
                $calls++;
            }),
        )->record(new CatalogueCard());
        $this->host->data = ['title' => '', 'author' => 'Ada'];

        expect(fn (): object => $schema->getData())->toThrow(ValidationException::class)
            ->and($calls)->toBe(0);
    });

    it('fires a hook only where the dehydrated state carries its path', function (): void {
        $fired = [];
        $hook = function (TextInput $component) use (&$fired): void {
            $fired[] = $component->getName();
        };
        $schema = $this->host->mapping(
            TextInput::make('title')->saveRelationshipsUsing($hook),
            TextInput::make('shelfMark')->hidden()->saveRelationshipsUsing($hook),
            TextInput::make('author')->hidden()->dehydratedWhenHidden()->saveRelationshipsUsing($hook),
        )->record(new CatalogueCard());
        $this->host->data = ['title' => 'Dune', 'shelfMark' => 'A1', 'author' => 'Ada'];

        $schema->getData();

        expect($fired)->toBe(['title', 'author']);
    });

    it('files a record violation raised beneath a subtree owner under the field inside it', function (): void {
        $card = new VettedCard();
        $schema = $this->host->mapping(
            SubtreeOwningGroup::make([TextInput::make('name')])->statePath('author')->saveRelationshipsUsing(function (VettedCard $record, array $state): void {
                $record->author->name = $state['name'];
            }),
        )->record($card);
        $this->host->data['author']['name'] = 'Al';

        expect(fn (): object => $schema->getData())->toThrow(ValidationException::class)
            ->and($this->host->getErrorsForSchemaStatePath('data.author.name'))->toBe(['The author name is too short.']);
    });

    it('refuses a hook the record path map does not carry, on fill as on save', function (Component $component, string $reason): void {
        $schema = $this->host->mapping($component)->record(new CatalogueCard());

        expect(fn (): Schema => $schema->fill(new CatalogueCard()))->toThrow(LogicException::class, $reason)
            ->and(fn (): object => $schema->getData())->toThrow(LogicException::class, $reason);
    })->with([
        'a component without a state path' => fn (): array => [
            Section::make()->saveRelationshipsUsing(fn (): null => null),
            Section::class . ' declares a relationship hook, but the record path map does not carry it, so the hook could never fire — it names no record path. Give it the statePath() of the association it maps, or drop the hook.',
        ],
        'a container whose fields carry the paths' => fn (): array => [
            Section::make()->statePath('author')->components([TextInput::make('name')])->loadStateFromRelationshipsUsing(fn (): array => []),
            Section::class . ' declares a relationship hook, but the record path map does not carry it, so the hook could never fire — the map takes the paths of the components nested inside it instead of its own. Declare ownsRecordSubtree() so it maps whole, or drop the hook.',
        ],
        'a field beneath an owner that does not route it' => fn (): array => [
            SubtreeOwningGroup::make([TextInput::make('name')->saveRelationshipsUsing(fn (): null => null)])->statePath('meta'),
            TextInput::class . ' declares a relationship hook, but the record path map does not carry it, so the hook could never fire — a component above it owns its record subtree without routing the hooks beneath it. Declare relationship() on that component, move this one out from under it, or drop the hook.',
        ],
    ]);

    it('leaves a hook to the nearest owner that routes its path', function (): void {
        $card = new CatalogueCard();
        $card->meta = ['note' => 'First'];

        $schema = $this->host->mapping(
            SubtreeOwningGroup::make([TextInput::make('note')->saveRelationshipsUsing(fn (): null => null)])->statePath('meta')->routeHooksAt('note'),
        )->record($card);

        $schema->fill($card);
        $this->host->data['meta']['note'] = 'Second';

        expect($schema->getData()->meta)->toBe(['note' => 'Second']);
    });

    it('keeps refusing a hook beneath a routing owner that the owner does not take', function (SubtreeOwningGroup $owner): void {
        $schema = $this->host->mapping($owner)->record(new CatalogueCard());

        expect(fn (): Schema => $schema->fill(new CatalogueCard()))->toThrow(LogicException::class, 'declares a relationship hook, but the record path map does not carry it');
    })->with([
        'a path it does not route' => fn (): SubtreeOwningGroup => SubtreeOwningGroup::make([
            TextInput::make('note')->saveRelationshipsUsing(fn (): null => null),
        ])->statePath('meta')->routeHooksAt('summary'),
        'beneath an inner owner that does not route it' => fn (): SubtreeOwningGroup => SubtreeOwningGroup::make([
            SubtreeOwningGroup::make([TextInput::make('note')->saveRelationshipsUsing(fn (): null => null)])->statePath('inner'),
        ])->statePath('meta')->routeHooksAt('inner.note'),
        'the earlier of two hooked fields sharing its path' => fn (): SubtreeOwningGroup => SubtreeOwningGroup::make([
            TextInput::make('note')->saveRelationshipsUsing(fn (): null => null),
            TextInput::make('note')->saveRelationshipsUsing(fn (): null => null),
        ])->statePath('meta')->routeHooksAt('note'),
        'a component without a state path' => fn (): SubtreeOwningGroup => SubtreeOwningGroup::make([
            TextInput::make('note'),
            Section::make()->saveRelationshipsUsing(fn (): null => null),
        ])->statePath('meta')->routeHooksAt('note'),
        'a container whose fields carry the paths' => fn (): SubtreeOwningGroup => SubtreeOwningGroup::make([
            Section::make()->statePath('detail')->components([TextInput::make('note')])->saveRelationshipsUsing(fn (): null => null),
        ])->statePath('meta')->routeHooksAt('detail'),
    ]);

    it('names every hook beneath it that no nested owner speaks for, relative to the schema asked', function (): void {
        $title = TextInput::make('title')->saveRelationshipsUsing(fn (): null => null);
        $note = TextInput::make('note')->loadStateFromRelationshipsUsing(fn (): null => null);
        $name = TextInput::make('name')->saveRelationshipsUsing(fn (): null => null);
        $owner = SubtreeOwningGroup::make([$name])->statePath('author')->saveRelationshipsUsing(fn (): null => null);
        $schema = $this->host->mapping($title, Section::make()->statePath('meta')->components([$note]), $owner, TextInput::make('shelfMark'));

        expect($schema->getNestedRelationshipHooks())->toBe(['title' => $title, 'meta.note' => $note, 'author' => $owner])
            ->and($owner->getChildSchema()->getNestedRelationshipHooks())->toBe(['name' => $name]);
    });
});

describe('findUnmappedAdditions()', function (): void {
    it('answers the leaf paths a hook added that no component maps', function (array $before, array $after, array $expected): void {
        $schema = $this->host->mapping(
            TextInput::make('title'),
            TextInput::make('shelfMark')->hidden(),
            TextInput::make('catalogued')->dehydrated(false),
            Section::make()->statePath('author')->components([TextInput::make('name')]),
            SubtreeOwningGroup::make([TextInput::make('note')])->statePath('meta'),
        );

        expect($schema->findUnmappedAdditions($before, $after))->toBe($expected);
    })->with([
        'a key nothing maps' => [['title' => 'Dune'], ['title' => 'Dune', 'stamp' => 'x', 'flags' => ['a' => 1]], ['stamp', 'flags.a']],
        'a key the state already held' => [['title' => 'Dune', 'typed' => 'client'], ['title' => 'Dune', 'typed' => 'hook'], []],
        'an overwrite of a mapped key' => [['title' => 'Dune'], ['title' => 'Messiah'], []],
        "a hidden or dehydrated(false) field's key" => [[], ['shelfMark' => 'A1', 'catalogued' => '2026-03-04'], []],
        "a key inside an owner's value" => [['meta' => ['note' => 'a']], ['meta' => ['note' => 'a', 'extra' => 'b']], []],
        'a dotted key naming a mapped path, read as one key' => [[], ['author.name' => 'Ada'], []],
        'a removed key' => [['title' => 'Dune', 'author' => ['name' => 'Ada']], ['title' => 'Dune'], []],
        'a mapped branch emptied' => [['author' => ['name' => 'Ada']], ['author' => []], []],
    ]);

    it('answers a mapped branch a hook replaced with null', function (): void {
        $schema = $this->host->mapping(Section::make()->statePath('author')->components([TextInput::make('name')]));

        expect($schema->findUnmappedAdditions(['author' => ['name' => 'Ada']], ['author' => null]))->toBe(['author']);
    });

    it("writes a hidden field's key a hook put back, since the map carries it", function (): void {
        $schema = $this->host->mapping(TextInput::make('title'), TextInput::make('shelfMark')->hidden())->record(new CatalogueCard());
        $this->host->data['title'] = 'Dune';
        $state = $schema->getState();

        expect($state)->toBe(['title' => 'Dune'])
            ->and($schema->getData([...$state, 'shelfMark' => 'A1'])->shelfMark)->toBe('A1');
    });
});

describe('loadStateFromRelationships()', function (): void {
    it('reads every hooked path back off the record without a statement, and leaves the rest alone', function (): void {
        $this->seed(new InvoiceLine(new Invoice(7), line: 1, note: 'Stored'));
        $line = $this->entityManager()->find(InvoiceLine::class, ['invoice' => 7, 'line' => 1]);
        $hydrated = 0;
        $schema = $this->host->mapping(
            TextInput::make('invoice')
                ->default(99)
                ->loadStateFromRelationshipsUsing(fn (InvoiceLine $record): int => $record->invoice->id)
                ->afterStateHydrated(function () use (&$hydrated): void {
                    $hydrated++;
                }),
            TextInput::make('note'),
        )->record($line);
        $schema->fill($line);
        $this->host->data['note'] = 'Typed';
        $line->invoice = new Invoice(8);
        $statements = count($this->statementsRun());

        $schema->loadStateFromRelationships();

        expect($this->host->data)->toBe(['invoice' => 8, 'note' => 'Typed'])
            ->and($hydrated)->toBe(2)
            ->and($this->statementsRun())->toHaveCount($statements);
    });

    it('hands a relationship back as the flushed record gives it, so a field the save never wrote reads what the record holds', function (): void {
        $this->seed(new InvoiceLine(new Invoice(7), line: 1, note: 'Stored'));
        $line = $this->entityManager()->find(InvoiceLine::class, ['invoice' => 7, 'line' => 1]);
        $schema = $this->host->mapping(
            TextInput::make('note'),
            Group::make([TextInput::make('id'), TextInput::make('nickname')->dehydrated(false)])->relationship('invoice'),
        )->record($line);
        $schema->fill($line);
        $this->host->data['note'] = 'Edited';
        $this->host->data['invoice']['nickname'] = 'Typed';
        $schema->getData();
        $this->entityManager()->flush();

        $schema->loadStateFromRelationships();

        expect($this->host->data)->toBe(['note' => 'Edited', 'invoice' => ['id' => 7, 'nickname' => null]])
            ->and($this->reload($line)->note)->toBe('Edited');
    });

    it('reads nothing for a schema bound to no record', function (?string $record): void {
        $calls = 0;
        $schema = $this->host->mapping(TextInput::make('title')->loadStateFromRelationshipsUsing(function () use (&$calls): string {
            $calls++;

            return 'Read';
        }))->record($record);
        $this->host->data['title'] = 'Typed';

        $schema->loadStateFromRelationships();

        expect($this->host->data)->toBe(['title' => 'Typed'])
            ->and($calls)->toBe(0);
    })->with([
        'unbound' => [null],
        'a class-string binding' => [CatalogueCard::class],
    ]);
});
