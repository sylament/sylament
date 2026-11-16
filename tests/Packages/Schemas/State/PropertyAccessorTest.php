<?php

declare(strict_types=1);

use Sylament\Schemas\State\PropertyAccessor;
use Sylament\Tests\Fixtures\Entity\Invoice;
use Sylament\Tests\Fixtures\Schemas\Record\Anthology;
use Sylament\Tests\Fixtures\Schemas\Record\Manuscript;
use Sylament\Tests\Fixtures\Schemas\Record\ManuscriptKey;
use Sylament\Tests\Fixtures\Schemas\Record\ManuscriptSignatures;
use Sylament\Tests\Fixtures\Schemas\Record\ManuscriptStateBag;
use Sylament\Tests\Fixtures\Schemas\Record\ManuscriptWriter;
use Sylament\Tests\Fixtures\Schemas\Record\UnmappedHostPathRedirect;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\Exception\UninitializedPropertyException;

describe('host state bag', function (): void {
    it('reads a bag by dotted path, the whole property for one segment and null past an absent key, a null or a scalar', function (string $path, mixed $expected): void {
        $bag = new ManuscriptStateBag(['writer' => ['name' => 'Ada'], 'editor' => null, 'title' => 'Dune']);

        expect($this::getContainer()->get(PropertyAccessor::class)->get($bag, $path))->toBe($expected);
    })->with([
        'a nested key' => ['data.writer.name', 'Ada'],
        'one segment' => ['data', ['writer' => ['name' => 'Ada'], 'editor' => null, 'title' => 'Dune']],
        'an absent key' => ['data.writer.handle', null],
        'past a null' => ['data.editor.name', null],
        'past a scalar' => ['data.title.length', null],
    ]);

    it('writes a bag by dotted path, building a missing branch and turning a null or a scalar slot into one', function (): void {
        $accessor = $this::getContainer()->get(PropertyAccessor::class);
        $bag = new ManuscriptStateBag();

        $accessor->set($bag, 'data', ['writer' => null, 'title' => 'Dune']);
        $accessor->set($bag, 'data.writer.name', 'Ada');
        $accessor->set($bag, 'data.title.main', 'Dune');
        $accessor->set($bag, 'data.meta.seo.title', 'Arrakis');

        expect($bag->data)->toBe([
            'writer' => ['name' => 'Ada'],
            'title' => ['main' => 'Dune'],
            'meta' => ['seo' => ['title' => 'Arrakis']],
        ]);
    });
});

describe('detached array', function (): void {
    it('reads a detached array by dotted path, and null past an absent key or a scalar', function (string $path, mixed $expected): void {
        $state = ['writer' => ['name' => 'Ada', 'handle' => null], 'title' => 'Dune'];

        expect($this::getContainer()->get(PropertyAccessor::class)->getFromArray($state, $path))->toBe($expected);
    })->with([
        'a present key' => ['writer.name', 'Ada'],
        'an absent key' => ['writer.bio', null],
        'past a scalar' => ['title.main', null],
        'a present null' => ['writer.handle', null],
    ]);

    it('writes a detached array by dotted path, building missing segments, turning a scalar into one and overwriting a leaf', function (): void {
        $state = ['writer' => ['name' => 'Ada'], 'title' => 'Dune'];
        $accessor = $this::getContainer()->get(PropertyAccessor::class);

        $accessor->setInArray($state, 'writer.name', 'Bo');
        $accessor->setInArray($state, 'title.main', 'Dune');
        $accessor->setInArray($state, 'meta.seo.title', 'Arrakis');

        expect($state)->toBe([
            'writer' => ['name' => 'Bo'],
            'title' => ['main' => 'Dune'],
            'meta' => ['seo' => ['title' => 'Arrakis']],
        ]);
    });

    it('forgets only the leaf of one path or of several, leaving an emptied parent in place', function (): void {
        $state = ['writer' => ['name' => 'Ada', 'handle' => 'ada'], 'meta' => ['seo' => ['title' => 'Arrakis']], 'title' => 'Dune'];
        $accessor = $this::getContainer()->get(PropertyAccessor::class);

        $accessor->forgetFromArray($state, 'meta.seo.title');
        $accessor->forgetFromArray($state, ['writer.name', 'writer.handle', 'title.main']);

        expect($state)->toBe(['writer' => [], 'meta' => ['seo' => []], 'title' => 'Dune']);
    });

    it('tells a present null from an absent key', function (): void {
        $state = ['writer' => ['handle' => null], 'title' => 'Dune'];
        $accessor = $this::getContainer()->get(PropertyAccessor::class);

        expect($accessor->hasInArray($state, 'writer.handle'))->toBeTrue()
            ->and($accessor->hasInArray($state, 'writer.name'))->toBeFalse()
            ->and($accessor->hasInArray($state, 'title.main'))->toBeFalse();
    });
});

describe('getFromRecord()', function (): void {
    it('reads a record graph by dotted path, hopping objects by property and arrays by key, and null past a null or a scalar', function (string $path, mixed $expected): void {
        $manuscript = new Manuscript();
        $manuscript->setTitle('Dune');
        $manuscript->setWriter(new ManuscriptWriter('Ada'));
        $manuscript->setMeta(['seo' => ['title' => 'Arrakis']]);

        expect($this::getContainer()->get(PropertyAccessor::class)->getFromRecord($manuscript, $path))->toBe($expected);
    })->with([
        'an object hop' => ['writer.name', 'Ada'],
        'an array column' => ['meta.seo.title', 'Arrakis'],
        'an absent key' => ['meta.seo.description', null],
        'past a null' => ['invoice.id', null],
        'past a scalar' => ['title.main', null],
    ]);

    it("lets an object hop it cannot read raise Symfony's refusal", function (): void {
        $accessor = $this::getContainer()->get(PropertyAccessor::class);

        expect(fn (): mixed => $accessor->getFromRecord(new Manuscript(), 'editor.name'))->toThrow(UninitializedPropertyException::class)
            ->and(fn (): mixed => $accessor->getFromRecord(new Manuscript(), 'publisher'))->toThrow(NoSuchPropertyException::class);
    });
});

describe('setOnRecord()', function (): void {
    it('writes a leaf through its setter, and into an existing nested object in place', function (): void {
        $manuscript = new Manuscript();
        $manuscript->setWriter($writer = new ManuscriptWriter('Ada'));

        $accessor = $this::getContainer()->get(PropertyAccessor::class);

        $accessor->setOnRecord($manuscript, 'title', 'Dune');
        $accessor->setOnRecord($manuscript, 'writer.handle', 'ada');

        expect($manuscript->getTitle())->toBe('Dune')
            ->and($manuscript->getWriter())->toBe($writer)
            ->and($writer->getHandle())->toBe('ada');
    });

    it('builds a missing nested object from its declared type, whether it holds null or was never assigned', function (string $property): void {
        $manuscript = new Manuscript();

        $this::getContainer()->get(PropertyAccessor::class)->setOnRecord($manuscript, "{$property}.name", 'Ada');

        expect($manuscript->{'get' . ucfirst($property)}())->toEqual(new ManuscriptWriter('Ada'));
    })->with([
        'null' => ['writer'],
        'never assigned' => ['editor'],
    ]);

    it('writes the rest of a path past a property no object type names as a nested array', function (): void {
        $manuscript = new Manuscript();
        $manuscript->setMeta(['seo' => 'flat', 'lang' => 'en']);

        $anthology = new Anthology();
        $accessor = $this::getContainer()->get(PropertyAccessor::class);

        $accessor->setOnRecord($manuscript, 'meta.seo.title', 'Arrakis');
        $accessor->setOnRecord($anthology, 'drafts.0.name', 'Ada');

        expect($manuscript->getMeta())->toBe(['seo' => ['title' => 'Arrakis'], 'lang' => 'en'])
            ->and($anthology->drafts)->toBe([0 => ['name' => 'Ada']]);
    });

    it('refuses an array a typed property rejects by naming the path, leaving the object it built on the way attached', function (): void {
        $manuscript = new Manuscript();

        expect(fn () => $this::getContainer()->get(PropertyAccessor::class)->setOnRecord($manuscript, 'writer.name.first', 'Ada'))
            ->toThrow(LogicException::class, sprintf('Cannot map state path "writer.name.first" onto %s: "name" declares no object the mapper can construct, and its property rejects the array written in its place. Assign the nested object yourself if the property can hold one, or map this path onto a property that accepts the branch.', ManuscriptWriter::class))
            ->and($manuscript->getWriter())->toBeInstanceOf(ManuscriptWriter::class)
            ->and($manuscript->getWriter()?->getName())->toBeNull();
    });

    it('refuses an object collection by its declared type and by the collection it meets, naming relationship()', function (string $path): void {
        $anthology = new Anthology();

        expect(fn () => $this::getContainer()->get(PropertyAccessor::class)->setOnRecord($anthology, $path, []))
            ->toThrow(LogicException::class, sprintf('Cannot map state path "%s" onto %s: "%s" is an object collection. Collection items need identity mapping (which state item is which entity), which only a Repeater declaring relationship() supplies — the Repeater maps plain-array items otherwise. Declare relationship() on the component bound to this property, or map it by hand after getData().', $path, Anthology::class, explode('.', $path)[0]));
    })->with([
        'declared' => ['contributors'],
        'met mid-path' => ['archive.0.name'],
    ]);

    it('refuses a nested class it cannot build without arguments, naming the class', function (): void {
        expect(fn () => $this::getContainer()->get(PropertyAccessor::class)->setOnRecord(new Manuscript(), 'invoice.id', 7))
            ->toThrow(LogicException::class, sprintf('Cannot map state path "invoice.id" onto %s: %s requires constructor arguments, so the mapper cannot build it. Give the constructor defaults, or initialize "invoice" before mapping.', Manuscript::class, Invoice::class));
    });
});

describe('leaf no setter can change', function (): void {
    it('leaves a property no setter can change alone when it already holds the value', function (mixed $held, mixed $written): void {
        $key = new ManuscriptKey($held);

        $this::getContainer()->get(PropertyAccessor::class)->setOnRecord($key, 'held', $written);

        expect($key->held)->toBe($held);
    })->with([
        'identical' => ['MS-7', 'MS-7'],
        'null over a held empty string' => ['', null],
        'an empty string over a held null' => [null, ''],
        'a written number that prints as the held string' => ['5', 5],
        'a numeric string over a held number' => [5, '05'],
        'arrays whose elements each hold the same' => [['5', 'a'], [5, 'a']],
        'an equal object that is no record' => [new DateTimeImmutable('2024-01-02 03:04:05'), new DateTimeImmutable('2024-01-02 03:04:05')],
        'the same record' => fn (): array => [$invoice = new Invoice(7), $invoice],
    ]);

    it('refuses another value by name, giving the two types and never the values', function (mixed $held, mixed $written, string $heldType, string $writtenType): void {
        expect(fn () => $this::getContainer()->get(PropertyAccessor::class)->setOnRecord(new ManuscriptKey($held), 'held', $written))
            ->toThrow(LogicException::class, sprintf("Cannot map state path \"held\" onto %s: no setter can change that property, and it already holds another value (the record holds [%s], the write [%s]). Hand the constructor the form's value as it is, make the field's state match what the constructor keeps with dehydrateStateUsing(), or leave the field out of the form.", ManuscriptKey::class, $heldType, $writtenType));
    })->with([
        'another string' => ['MS-7', 'MS-8', 'string', 'string'],
        'two numeric strings' => ['007', '7', 'string', 'string'],
        'a written number that does not print as the held string' => ['05', 5, 'string', 'int'],
        'arrays whose strings differ' => [['007'], ['7'], 'array', 'array'],
        'arrays of different lengths' => [['a'], ['a', 'b'], 'array', 'array'],
        'arrays whose string element does not print as the written number' => [['05'], [5], 'array', 'array'],
        'a bool over a held number' => [1, true, 'int', 'bool'],
        'null over a held zero' => [0, null, 'int', 'null'],
        'an equal record that is another instance' => fn (): array => [new Invoice(7), new Invoice(7), Invoice::class, Invoice::class],
        'an equal record of a subclass the mapping does not know' => fn (): array => [new UnmappedHostPathRedirect('example.com', '/'), new UnmappedHostPathRedirect('example.com', '/'), UnmappedHostPathRedirect::class, UnmappedHostPathRedirect::class],
    ]);

    it('reads a private property behind its getter the same way', function (): void {
        $key = new ManuscriptKey();
        $accessor = $this::getContainer()->get(PropertyAccessor::class);

        $accessor->setOnRecord($key, 'code', 'MS-7');

        expect($key->getCode())->toBe('MS-7')
            ->and(fn () => $accessor->setOnRecord($key, 'code', 'MS-8'))->toThrow(LogicException::class, 'Cannot map state path "code" onto ' . ManuscriptKey::class . ': no setter can change that property, and it already holds another value (the record holds [string], the write [string]).');
    });

    it('refuses a property no setter can change that holds no value yet, which only the constructor can give', function (): void {
        expect(fn () => $this::getContainer()->get(PropertyAccessor::class)->setOnRecord(new ManuscriptKey(), 'badge', 'gold'))
            ->toThrow(LogicException::class, sprintf("Cannot map state path \"badge\" onto %s: no setter can change that property, and it holds no value yet, so only the record's constructor can give it one. Build the record with that value before the form is mapped onto it, or leave the field out of the form.", ManuscriptKey::class));
    });

    it("surfaces Symfony's refusal for a property it can neither read nor write", function (string $property): void {
        expect(fn () => $this::getContainer()->get(PropertyAccessor::class)->setOnRecord(new ManuscriptKey(), $property, 'quill'))
            ->toThrow(NoSuchPropertyException::class);
    })->with([
        'private and readonly without a getter' => ['secret'],
        'misspelled' => ['sekret'],
    ]);

    it('writes a writable property through its setter even when it already holds the value', function (): void {
        $key = new ManuscriptKey();
        $key->setTitle('Dune');

        $this::getContainer()->get(PropertyAccessor::class)->setOnRecord($key, 'title', 'Dune');

        expect($key->getTitleWrites())->toBe(2);
    });
});

describe('setAssociationOnRecord()', function (): void {
    it('writes a list through the adder and the remover, keeping the collection and changing only the difference, where setOnRecord() refuses it', function (): void {
        $anthology = new Anthology();
        $anthology->addContributor($ada = new ManuscriptWriter('Ada'));
        $anthology->addContributor($bo = new ManuscriptWriter('Bo'));

        $cy = new ManuscriptWriter('Cy');
        $anthology->calls = [];
        $contributors = $anthology->getContributors();
        $accessor = $this::getContainer()->get(PropertyAccessor::class);

        expect(fn () => $accessor->setOnRecord($anthology, 'contributors', [$bo, $cy]))->toThrow(LogicException::class, 'is an object collection');

        $accessor->setAssociationOnRecord($anthology, 'contributors', [$bo, $cy]);

        expect($anthology->getContributors())->toBe($contributors)
            ->and(array_values($contributors->toArray()))->toBe([$bo, $cy])
            ->and($anthology->calls)->toBe(['remove Ada', 'add Cy'])
            ->and($contributors->contains($ada))->toBeFalse();
    });

    it('writes a single related record, or null, through its setter', function (): void {
        $anthology = new Anthology();
        $accessor = $this::getContainer()->get(PropertyAccessor::class);

        $accessor->setAssociationOnRecord($anthology, 'editor', $editor = new ManuscriptWriter('Ada'));

        $written = $anthology->getEditor();
        $accessor->setAssociationOnRecord($anthology, 'editor', null);

        expect($written)->toBe($editor)
            ->and($anthology->getEditor())->toBeNull();
    });

    it('compares a single related object on a property no setter can change by identity alone', function (): void {
        $anthology = new Anthology($founder = new ManuscriptWriter('Ada'));
        $accessor = $this::getContainer()->get(PropertyAccessor::class);

        $accessor->setAssociationOnRecord($anthology, 'founder', $founder);
        $accessor->setOnRecord($anthology, 'founder', new ManuscriptWriter('Ada'));

        expect($anthology->founder)->toBe($founder)
            ->and(fn () => $accessor->setAssociationOnRecord($anthology, 'founder', new ManuscriptWriter('Ada')))
            ->toThrow(LogicException::class, sprintf('Cannot write association "founder" onto %s: no setter can change that property, and it already holds another value (the record holds [%2$s], the write [%2$s]). Build the record around the record it is attached to, or give the property a setter.', Anthology::class, ManuscriptWriter::class));
    });

    it('refuses a related record on a property no setter can change that holds none yet, which only the constructor can give', function (): void {
        expect(fn () => $this::getContainer()->get(PropertyAccessor::class)->setAssociationOnRecord(new ManuscriptKey(), 'badge', new ManuscriptWriter('Ada')))
            ->toThrow(LogicException::class, sprintf("Cannot write association \"badge\" onto %s: no setter can change that property, and it holds no value yet, so only the record's constructor can give it one. Build the record with its related record before attaching it.", ManuscriptKey::class));
    });
});

describe('type probes', function (): void {
    it('answers whether the type a path declares on its write side takes null, and yes where it cannot read one', function (string $path, bool $expected): void {
        expect($this::getContainer()->get(PropertyAccessor::class)->acceptsNullOnRecord(ManuscriptSignatures::class, $path))->toBe($expected);
    })->with([
        'a non-nullable setter' => ['name', false],
        'a nullable setter behind a non-nullable getter' => ['writer', true],
        'a non-nullable setter behind a nullable getter' => ['reviewer', false],
        'a path through a nested object' => ['invoice.id', false],
        'an unreadable path' => ['publisher', true],
    ]);

    it('answers the native type a path declares, and none for an object, a collection, a union or an unreadable path', function (string $path, ?string $expected): void {
        expect($this::getContainer()->get(PropertyAccessor::class)->getBuiltinTypeOnRecord(ManuscriptSignatures::class, $path))->toBe($expected);
    })->with([
        'an int' => ['pages', 'int'],
        'a string' => ['name', 'string'],
        'a bool' => ['published', 'bool'],
        'a path through a nested object' => ['invoice.id', 'int'],
        'an object' => ['writer', null],
        'a collection' => ['notes', null],
        'a union' => ['code', null],
        'an unreadable path' => ['publisher', null],
    ]);
});

describe('constructRecord()', function (): void {
    it("builds a class that needs no constructor arguments, and refuses one that does in one sentence around the caller's subject and advice", function (): void {
        $accessor = $this::getContainer()->get(PropertyAccessor::class);

        expect($accessor->constructRecord(ManuscriptWriter::class, 'the writer', 'bind one.'))->toBeInstanceOf(ManuscriptWriter::class)
            ->and(fn (): object => $accessor->constructRecord(Invoice::class, 'the invoice of a draft', 'bind the record through record().'))
            ->toThrow(LogicException::class, sprintf('Cannot map the invoice of a draft: %s requires constructor arguments, so the mapper cannot build it. Give the constructor defaults, or bind the record through record().', Invoice::class));
    });
});
