<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Schemas\State\AssociationReader;
use Sylament\Schemas\State\AssociationTarget;
use Sylament\Tests\Fixtures\Entity\AisleSeat;
use Sylament\Tests\Fixtures\Entity\Atelier;
use Sylament\Tests\Fixtures\Entity\AtelierApprentice;
use Sylament\Tests\Fixtures\Entity\AtelierEasel;
use Sylament\Tests\Fixtures\Entity\AtelierLease;
use Sylament\Tests\Fixtures\Entity\AtelierPigment;
use Sylament\Tests\Fixtures\Entity\AtelierPlaque;
use Sylament\Tests\Fixtures\Entity\AtelierStock;
use Sylament\Tests\Fixtures\Entity\CaselessHandle;
use Sylament\Tests\Fixtures\Entity\Charter;
use Sylament\Tests\Fixtures\Entity\CharterSeal;
use Sylament\Tests\Fixtures\Entity\CipherStrongbox;
use Sylament\Tests\Fixtures\Entity\CipherVault;
use Sylament\Tests\Fixtures\Entity\CodeKeyedSlate;
use Sylament\Tests\Fixtures\Entity\Convoy;
use Sylament\Tests\Fixtures\Entity\ConvoyWagon;
use Sylament\Tests\Fixtures\Entity\ExhibitPlinth;
use Sylament\Tests\Fixtures\Entity\HostPathRedirect;
use Sylament\Tests\Fixtures\Entity\Invoice;
use Sylament\Tests\Fixtures\Entity\InvoiceLine;
use Sylament\Tests\Fixtures\Entity\ListenedLedgerEntry;
use Sylament\Tests\Fixtures\Entity\MarkedCodeLabel;
use Sylament\Tests\Fixtures\Entity\OrderLine;
use Sylament\Tests\Fixtures\Entity\PrefilledLedgerEntry;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Entity\StageResidency;
use Sylament\Tests\Fixtures\Entity\StageVenue;
use Sylament\Tests\Fixtures\Entity\StatuePlinth;
use Sylament\Tests\Fixtures\Entity\SubtypedVoucher;
use Sylament\Tests\Fixtures\Entity\UidReceipt;
use Sylament\Tests\Fixtures\Schemas\Doctrine\Cipher;
use Sylament\Tests\Fixtures\Schemas\Doctrine\WithdrawnNoteFilter;
use Sylament\Tests\Fixtures\Schemas\Record\UnmappedHostPathRedirect;
use Sylament\Tests\Infrastructure\Doctrine\TestPlatform;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;

describe('without an entity manager', function (): void {
    it("tells a reader built with new from the container's, which holds the manager", function (): void {
        expect(new AssociationReader()->hasEntityManager())->toBeFalse()
            ->and($this::getContainer()->get(AssociationReader::class)->hasEntityManager())->toBeTrue();
    });

    it('answers no association and refuses every operation with one message naming both causes and both ways out', function (): void {
        $reader = new AssociationReader();
        $product = new Product('Lamp');
        $message = 'The sylament AssociationReader has no Doctrine EntityManager, so this operation cannot run — it was built outside the container, or no EntityManagerInterface service exists. Register Doctrine (doctrine/doctrine-bundle) or bind the record by hand after getData().';

        expect($reader->describe(AtelierEasel::class, 'atelier'))->toBeNull()
            ->and($this::getContainer()->get(AssociationReader::class)->describe(AtelierEasel::class, 'atelier'))->toBeInstanceOf(AssociationTarget::class)
            ->and(fn (): object => $reader->getReference(Product::class, 1))->toThrow(LogicException::class, $message)
            ->and(fn (): string => $reader->getRecordKey($product))->toThrow(LogicException::class, $message)
            ->and(fn (): ?string => $reader->findRecordKey($product))->toThrow(LogicException::class, $message)
            ->and(fn (): bool => $reader->hasIncompleteAssignedIdentifier($product))->toThrow(LogicException::class, $message)
            ->and(fn (): bool => $reader->hasBlankAssignedIdentifier($product))->toThrow(LogicException::class, $message)
            ->and(fn (): object => $reader->createOptionQuery(Product::class))->toThrow(LogicException::class, $message)
            ->and(fn () => $reader->remove($product))->toThrow(LogicException::class, $message)
            ->and(fn () => $reader->persist($product))->toThrow(LogicException::class, $message)
            ->and(fn () => $reader->flush())->toThrow(LogicException::class, $message);
    });

    it("guards nothing in the record check, where the container's reader refuses", function (): void {
        $this->seed(new OrderLine(order: 7, line: 2));

        $reader = new AssociationReader();
        $bound = $this::getContainer()->get(AssociationReader::class);
        $seat = new AisleSeat('north', aisle: 3);
        $claim = new OrderLine(order: 7, line: 2);
        $stored = $this->entityManager()->find(OrderLine::class, ['order' => 7, 'line' => 2]);
        $stored->line = 3;

        expect($reader->findBlankAssignedIdentifiers($seat))->toBe([])
            ->and($reader->findTakenIdentifiers($claim))->toBe([])
            ->and($reader->revertIdentifierChanges($stored))->toBe([])
            ->and($stored->line)->toBe(3)
            ->and($bound->findBlankAssignedIdentifiers($seat))->toBe(['seat' => 'This value should not be blank.'])
            ->and($bound->findTakenIdentifiers($claim))->toBe(['order' => 'This value is already used.', 'line' => 'This value is already used.'])
            ->and($bound->revertIdentifierChanges($stored))->toBe(['line' => 'This value cannot be changed.']);
    });
});

describe('withEntityManager()', function (): void {
    it('answers a copy of its own class bound to the other manager, carrying what the subclass holds and leaving itself unbound', function (): void {
        $reader = new class ('north') extends AssociationReader {
            public function __construct(public readonly string $wing)
            {
                parent::__construct();
            }
        };

        $bound = $reader->withEntityManager($this->entityManager());

        expect($bound)->toBeInstanceOf($reader::class)
            ->not->toBe($reader)
            ->and($bound->wing)->toBe('north')
            ->and($bound->hasEntityManager())->toBeTrue()
            ->and($reader->hasEntityManager())->toBeFalse();
    });

    it('reads through the manager it is bound to, while the reader it came from keeps its own', function (): void {
        $this->seed(new Product('Lamp'), new Product('Desk'));

        $reader = $this::getContainer()->get(AssociationReader::class);
        $rebound = $reader->withEntityManager($this->platformEntityManager(TestPlatform::Sqlite));

        expect($rebound->createOptionQuery(Product::class)->getQuery()->getResult())->toBe([])
            ->and(array_map(static fn (Product $product): string => $product->name, $reader->createOptionQuery(Product::class)->getQuery()->getResult()))->toEqualCanonicalizing(['Lamp', 'Desk']);
    });
});

describe('describe()', function (): void {
    it('answers what the mapping declares at an association, on either side of each shape', function (string $class, string $property, AssociationTarget $target): void {
        expect($this::getContainer()->get(AssociationReader::class)->describe($class, $property))->toEqual($target);
    })->with([
        'a many-to-one whose join column refuses null' => [AtelierEasel::class, 'atelier', new AssociationTarget(targetClass: Atelier::class, isToMany: false, isOwningSide: true, isOrphanRemoval: false, isManyToMany: false, mappedBy: null, inversedBy: 'easels', isNullable: false, orderBy: [])],
        'its ordered one-to-many, which removes orphans' => [Atelier::class, 'easels', new AssociationTarget(targetClass: AtelierEasel::class, isToMany: true, isOwningSide: false, isOrphanRemoval: true, isManyToMany: false, mappedBy: 'atelier', inversedBy: null, isNullable: true, orderBy: ['position' => SortDirection::Ascending])],
        'a one-to-many that removes nothing' => [Atelier::class, 'apprentices', new AssociationTarget(targetClass: AtelierApprentice::class, isToMany: true, isOwningSide: false, isOrphanRemoval: false, isManyToMany: false, mappedBy: 'atelier', inversedBy: null, isNullable: true, orderBy: [])],
        'the owning side of a one-to-one' => [AtelierLease::class, 'atelier', new AssociationTarget(targetClass: Atelier::class, isToMany: false, isOwningSide: true, isOrphanRemoval: false, isManyToMany: false, mappedBy: null, inversedBy: 'lease', isNullable: true, orderBy: [])],
        'the inverse side of a one-to-one' => [Atelier::class, 'lease', new AssociationTarget(targetClass: AtelierLease::class, isToMany: false, isOwningSide: false, isOrphanRemoval: false, isManyToMany: false, mappedBy: 'atelier', inversedBy: null, isNullable: true, orderBy: [])],
        'the owning side of a many-to-many' => [Atelier::class, 'pigments', new AssociationTarget(targetClass: AtelierPigment::class, isToMany: true, isOwningSide: true, isOrphanRemoval: false, isManyToMany: true, mappedBy: null, inversedBy: 'ateliers', isNullable: true, orderBy: [])],
        'the inverse side of a many-to-many' => [AtelierPigment::class, 'ateliers', new AssociationTarget(targetClass: Atelier::class, isToMany: true, isOwningSide: false, isOrphanRemoval: false, isManyToMany: true, mappedBy: 'pigments', inversedBy: null, isNullable: true, orderBy: [])],
        'a unidirectional to-one onto a composite key whose second join column refuses null' => [AtelierApprentice::class, 'commission', new AssociationTarget(targetClass: OrderLine::class, isToMany: false, isOwningSide: true, isOrphanRemoval: false, isManyToMany: false, mappedBy: null, inversedBy: null, isNullable: false, orderBy: [])],
        'a unidirectional to-one onto a composite key whose join columns all accept null' => [AtelierApprentice::class, 'quote', new AssociationTarget(targetClass: OrderLine::class, isToMany: false, isOwningSide: true, isOrphanRemoval: false, isManyToMany: false, mappedBy: null, inversedBy: null, isNullable: true, orderBy: [])],
    ]);

    it('answers null where Doctrine has no association to tell of', function (string $class, string $property): void {
        expect($this::getContainer()->get(AssociationReader::class)->describe($class, $property))->toBeNull();
    })->with([
        'a plain column' => [Product::class, 'name'],
        'a property the mapping does not know' => [Product::class, 'colour'],
        'a class that is no entity' => [UnmappedHostPathRedirect::class, 'host'],
        'an anonymous class, whose metadata read would throw' => fn (): array => [new class {
            public ?Product $product = null;
        }::class, 'product'],
    ]);
});

describe('getReference()', function (): void {
    it('answers the very instance the manager holds, without a statement', function (object $record, string $key): void {
        $this->seed($record);

        $loaded = $this->entityManager()->getRepository($record::class)->findAll();
        $statements = count($this->statementsRun());

        expect($this::getContainer()->get(AssociationReader::class)->getReference($record::class, $key))->toBe($loaded[0])
            ->and($this->statementsRun())->toHaveCount($statements);
    })->with([
        'two scalars' => fn (): array => [new OrderLine(order: 7, line: 2), '7~2'],
        'an association plus a scalar' => fn (): array => [new InvoiceLine(new Invoice(7), line: 2), '7~2'],
    ]);

    it('references a record a cold manager holds nothing of, loading it only where an identifier field is an association', function (array $seed, string $class, string $key, int $statements): void {
        $this->seed(...$seed);

        $reader = $this::getContainer()->get(AssociationReader::class);
        $reference = $reader->getReference($class, $key);

        expect($this->statementsRun())->toHaveCount($statements)
            ->and($reference)->toBeInstanceOf($class)
            ->and($reader->getRecordKey($reference))->toBe($key);
    })->with([
        'a generated identifier' => fn (): array => [[new Product('Lamp')], Product::class, '1', 0],
        'two scalars' => fn (): array => [[new OrderLine(order: 7, line: 2)], OrderLine::class, '7~2', 0],
        'a type that reads only the form its column stores' => fn (): array => [[new MarkedCodeLabel('north')], MarkedCodeLabel::class, 'north', 0],
        'an association plus a scalar' => fn (): array => [[new InvoiceLine(new Invoice(7), line: 2)], InvoiceLine::class, '7~2', 1],
        'an association alone' => fn (): array => [[$charter = new Charter(1), new CharterSeal($charter)], CharterSeal::class, '1', 1],
        'two associations' => fn (): array => [[$atelier = new Atelier(), $pigment = new AtelierPigment(), new AtelierStock($atelier, $pigment)], AtelierStock::class, '1~1', 1],
        'the root of a hierarchy, which Doctrine loads itself' => fn (): array => [[new StatuePlinth('hall', bay: 3)], ExhibitPlinth::class, 'hall~3', 1],
    ]);

    it('refuses a key no record of the class mints, without a statement', function (string $class, int|string $key): void {
        expect(fn (): object => $this::getContainer()->get(AssociationReader::class)->getReference($class, $key))
            ->toThrow(LogicException::class, sprintf('No [%s] carries the identifier [%s], so it cannot be referenced.', $class, $key))
            ->and($this->statementsRun())->toBe([]);
    })->with([
        'letters after the digits of an integer' => [Product::class, '5abc'],
        'two components for one field' => [Product::class, '1~2'],
        'an integer for two fields' => [OrderLine::class, 7],
        'a key of a type that refuses its printed form on read and on write' => [CipherStrongbox::class, 'vault'],
    ]);

    it('refuses a well-formed key naming no row where the reference loads, after one statement', function (array $seed, string $class, string $key): void {
        $this->seed(...$seed);

        expect(fn (): object => $this::getContainer()->get(AssociationReader::class)->getReference($class, $key))
            ->toThrow(LogicException::class, sprintf('No [%s] carries the identifier [%s], so it cannot be referenced.', $class, $key))
            ->and($this->statementsRun())->toHaveCount(1);
    })->with([
        'an association plus a scalar' => fn (): array => [[new InvoiceLine(new Invoice(7), line: 2)], InvoiceLine::class, '7~3'],
        'the root of a hierarchy' => fn (): array => [[new StatuePlinth('hall', bay: 3)], ExhibitPlinth::class, 'hall~4'],
    ]);
});

describe('getRecordKey() and findRecordKey()', function (): void {
    it("speaks a record by the key its mapping's identifier prints, through both, at any width", function (array $seed, object $record, string $key): void {
        $this->seed(...$seed);

        $reader = $this::getContainer()->get(AssociationReader::class);

        expect($reader->getRecordKey($record))->toBe($key)
            ->and($reader->findRecordKey($record))->toBe($key);
    })->with([
        'a generated identifier' => fn (): array => [[$product = new Product('Lamp')], $product, '1'],
        'an assigned string' => fn (): array => [[$slate = new CodeKeyedSlate('slate-1')], $slate, 'slate-1'],
        'two scalars' => fn (): array => [[$line = new OrderLine(order: 7, line: 2)], $line, '7~2'],
        'an association plus a scalar' => fn (): array => [[$line = new InvoiceLine(new Invoice(7), line: 2)], $line, '7~2'],
        'an association alone' => fn (): array => [[$charter = new Charter(1), $seal = new CharterSeal($charter)], $seal, '1'],
        'two associations' => fn (): array => [[$atelier = new Atelier(), $pigment = new AtelierPigment(), $stock = new AtelierStock($atelier, $pigment)], $stock, '1~1'],
    ]);

    it('finds no key for a record whose identifier is not assigned yet, where getRecordKey() refuses it', function (object $record, string $message): void {
        $reader = $this::getContainer()->get(AssociationReader::class);

        expect($reader->findRecordKey($record))->toBeNull()
            ->and(fn (): string => $reader->getRecordKey($record))->toThrow(LogicException::class, $message);
    })->with([
        'a generated identifier before the flush' => fn (): array => [new Product('Lamp'), sprintf('[%s] has none set.', Product::class)],
        'an assigned string still blank' => fn (): array => [new CodeKeyedSlate(' '), sprintf('The identifier of [%s] is not a printable value.', CodeKeyedSlate::class)],
    ]);

    it('refuses from both an identifier that is assigned and cannot be printed', function (): void {
        $reader = $this::getContainer()->get(AssociationReader::class);
        $slate = new CodeKeyedSlate("slate\0one");
        $message = sprintf('The identifier of [%s] is not a printable value.', CodeKeyedSlate::class);

        expect(fn (): string => $reader->getRecordKey($slate))->toThrow(LogicException::class, $message)
            ->and(fn (): ?string => $reader->findRecordKey($slate))->toThrow(LogicException::class, $message);
    });
});

describe('hasIncompleteAssignedIdentifier()', function (): void {
    it('answers that a component the application assigns is missing', function (object $record): void {
        expect($this::getContainer()->get(AssociationReader::class)->hasIncompleteAssignedIdentifier($record))->toBeTrue();
    })->with([
        'one of three scalars left null' => fn (): AisleSeat => new AisleSeat('north', aisle: 3),
        'a half whose value prints blank' => fn (): CipherVault => new CipherVault(new Cipher(''), 'north'),
        'an association onto a record whose own key is blank' => fn (): StageResidency => new StageResidency(new StageVenue(''), 'autumn'),
        'a blank half beside an owner whose generated identifier is pending' => fn (): AtelierPlaque => new AtelierPlaque(new Atelier(), ''),
    ]);

    it('answers that nothing the application assigns is missing', function (object $record): void {
        expect($this::getContainer()->get(AssociationReader::class)->hasIncompleteAssignedIdentifier($record))->toBeFalse();
    })->with([
        'two scalars' => fn (): OrderLine => new OrderLine(order: 7, line: 2),
        'an association plus a scalar' => fn (): InvoiceLine => new InvoiceLine(new Invoice(7), line: 2),
        'a single scalar, even blank' => fn (): CodeKeyedSlate => new CodeKeyedSlate(''),
        'a generated identifier' => fn (): Product => new Product('Lamp'),
        'an owner whose generated identifier is pending, beside an assigned half' => fn (): AtelierPlaque => new AtelierPlaque(new Atelier(), 'en'),
        'a class declaring a prePersist callback, whatever it fills' => fn (): PrefilledLedgerEntry => new PrefilledLedgerEntry('', ''),
        'a class mapping an entity listener' => fn (): ListenedLedgerEntry => new ListenedLedgerEntry('', ''),
    ]);
});

describe('hasBlankAssignedIdentifier()', function (): void {
    it('reads whether the key persist() left still holds a blank string', function (object $record, bool $blank): void {
        $reader = $this::getContainer()->get(AssociationReader::class);
        $reader->persist($record);

        expect($reader->hasBlankAssignedIdentifier($record))->toBe($blank);
    })->with([
        'a blank half' => fn (): array => [new HostPathRedirect('', '/home'), true],
        'a blank half of the key an association points at' => fn (): array => [new StageResidency(new StageVenue(''), 'autumn'), true],
        'a blank half beside an owner whose generated identifier is pending' => fn (): array => [new AtelierPlaque(new Atelier(), ''), true],
        'a half the prePersist callback left blank' => fn (): array => [new PrefilledLedgerEntry('', ''), true],
        'a key the prePersist callback completed' => fn (): array => [new PrefilledLedgerEntry('ledger', ''), false],
        'an owner whose generated identifier is pending, beside an assigned half' => fn (): array => [new AtelierPlaque(new Atelier(), 'en'), false],
        'a single scalar, even blank' => fn (): array => [new CodeKeyedSlate(''), false],
        'a generated identifier' => fn (): array => [new Product('Lamp'), false],
    ]);
});

describe('findBlankAssignedIdentifiers()', function (): void {
    it("lists every blank key field of a new record, its scalars first and an association's target before the association", function (object $record, array $paths): void {
        expect($this::getContainer()->get(AssociationReader::class)->findBlankAssignedIdentifiers($record))
            ->toBe(array_fill_keys($paths, 'This value should not be blank.'));
    })->with([
        'one of three scalars left null' => fn (): array => [new AisleSeat('north', aisle: 3), ['seat']],
        'a blank half' => fn (): array => [new HostPathRedirect('', '/home'), ['host']],
        'a blank half beside an association onto a blank key' => fn (): array => [new StageResidency(new StageVenue(''), ''), ['season', 'venue.handle', 'venue']],
        'a class whose prePersist callback would fill one half' => fn (): array => [new PrefilledLedgerEntry('', ''), ['book', 'folio']],
    ]);

    it('lists nothing for a generated identifier, a single scalar or an association onto a generated one', function (object $record): void {
        expect($this::getContainer()->get(AssociationReader::class)->findBlankAssignedIdentifiers($record))->toBe([]);
    })->with([
        'a generated identifier' => fn (): Product => new Product('Lamp'),
        'a single scalar, even blank' => fn (): CodeKeyedSlate => new CodeKeyedSlate(''),
        'an owner whose generated identifier is pending, beside an assigned half' => fn (): AtelierPlaque => new AtelierPlaque(new Atelier(), 'en'),
    ]);

    it('lists nothing for a record the manager holds, blank key or not', function (): void {
        $this->seed(new HostPathRedirect('', '/home'));

        $held = $this->entityManager()->find(HostPathRedirect::class, ['host' => '', 'path' => '/home']);

        expect($this::getContainer()->get(AssociationReader::class)->findBlankAssignedIdentifiers($held))->toBe([])
            ->and($held->host)->toBe('');
    });
});

describe('revertIdentifierChanges()', function (): void {
    it('puts a changed key back and refuses it, running no statement', function (object $record, Closure $change, string $field): void {
        $this->seed($record);

        $reader = $this::getContainer()->get(AssociationReader::class);
        $loaded = $this->entityManager()->getRepository($record::class)->findAll()[0];
        $statements = count($this->statementsRun());
        $change($loaded);

        expect($reader->revertIdentifierChanges($loaded))->toBe([$field => 'This value cannot be changed.'])
            ->and($reader->getRecordKey($loaded))->toBe('7~2')
            ->and($this->statementsRun())->toHaveCount($statements);
    })->with([
        'two scalars' => fn (): array => [new OrderLine(order: 7, line: 2), static fn (OrderLine $line): int => $line->line = 3, 'line'],
        'an association plus a scalar' => fn (): array => [new InvoiceLine(new Invoice(7), line: 2), static fn (InvoiceLine $line): Invoice => $line->invoice = new Invoice(9), 'invoice'],
    ]);

    it('refuses a key changed on a loaded collection member, by its path from the record', function (): void {
        $atelier = new Atelier('north');
        $atelier->plaques->add(new AtelierPlaque($atelier, 'en'));
        $atelier->plaques->add(new AtelierPlaque($atelier, 'fr'));
        $this->seed($atelier);

        $loaded = $this->entityManager()->find(Atelier::class, $atelier->id);
        $plaque = $loaded->plaques[1];
        $locale = $plaque->locale;
        $plaque->locale = 'de';

        expect($this::getContainer()->get(AssociationReader::class)->revertIdentifierChanges($loaded))->toBe(['plaques[1].locale' => 'This value cannot be changed.'])
            ->and($plaque->locale)->toBe($locale);
    });

    it('refuses a key changed on the target of a loaded to-one, by its path from the record', function (): void {
        $commission = new OrderLine(order: 7, line: 2);
        $this->seed($commission, new AtelierApprentice(commission: $commission));

        $apprentice = $this->entityManager()->getRepository(AtelierApprentice::class)->findAll()[0];
        $apprentice->commission->note = 'loaded';
        $apprentice->commission->line = 3;

        expect($this::getContainer()->get(AssociationReader::class)->revertIdentifierChanges($apprentice))->toBe(['commission.line' => 'This value cannot be changed.'])
            ->and($apprentice->commission->line)->toBe(2);
    });

    it('refuses another spelling of a key, even one its column stores alike', function (): void {
        $this->seed(new CaselessHandle('north'));

        $handle = $this->entityManager()->find(CaselessHandle::class, 'north');
        $handle->handle = 'North';

        expect($this::getContainer()->get(AssociationReader::class)->revertIdentifierChanges($handle))->toBe(['handle' => 'This value cannot be changed.'])
            ->and($handle->handle)->toBe('north');
    });

    it('takes the same key in another PHP type for no change, and a different one for a change', function (object $record, Closure $same, Closure $different, string $field): void {
        $this->seed($record);

        $reader = $this::getContainer()->get(AssociationReader::class);
        $loaded = $this->entityManager()->getRepository($record::class)->findAll()[0];
        $same($loaded);
        $unchanged = $reader->revertIdentifierChanges($loaded);
        $different($loaded);

        expect($unchanged)->toBe([])
            ->and($reader->revertIdentifierChanges($loaded))->toBe([$field => 'This value cannot be changed.']);
    })->with([
        'a numeric string over an integer' => fn (): array => [
            new SubtypedVoucher('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11', 7, '12.25', false),
            static fn (SubtypedVoucher $voucher): string => $voucher->serial = '7',
            static fn (SubtypedVoucher $voucher): string => $voucher->serial = '8',
            'serial',
        ],
        'an equal uuid' => fn (): array => [
            new UidReceipt(Uuid::fromString('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11'), new Ulid()),
            static fn (UidReceipt $receipt): Uuid => $receipt->till = Uuid::fromString('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11'),
            static fn (UidReceipt $receipt): Uuid => $receipt->till = Uuid::fromString('b0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11'),
            'till',
        ],
    ]);

    it('loads nothing the record has not loaded, running no statement', function (): void {
        $north = new Convoy('north');
        $south = new Convoy('south');
        $this->seed($north, $south, new ConvoyWagon($north, 'coal'));

        $em = $this->entityManager();
        $wagon = $em->getRepository(ConvoyWagon::class)->findAll()[0];
        $loaded = $em->find(Convoy::class, $south->id);
        $statements = count($this->statementsRun());
        $reader = $this::getContainer()->get(AssociationReader::class);

        expect($reader->revertIdentifierChanges($wagon))->toBe([])
            ->and($reader->revertIdentifierChanges($loaded))->toBe([])
            ->and($em->isUninitializedObject($wagon->convoy))->toBeTrue()
            ->and($loaded->wagons->isInitialized())->toBeFalse()
            ->and($this->statementsRun())->toHaveCount($statements);
    });

    it('skips a record with no stored key to move: a new one, one scheduled for removal, one of an anonymous class', function (): void {
        $this->seed(new OrderLine(order: 7, line: 2), new OrderLine(order: 7, line: 4));

        $em = $this->entityManager();
        $reader = $this::getContainer()->get(AssociationReader::class);
        $removed = $em->find(OrderLine::class, ['order' => 7, 'line' => 2]);
        $kept = $em->find(OrderLine::class, ['order' => 7, 'line' => 4]);
        $em->remove($removed);
        $removed->line = 3;
        $kept->line = 5;
        $new = new OrderLine(order: 8, line: 1);
        $em->persist($new);
        $new->line = 2;

        expect($reader->revertIdentifierChanges($removed))->toBe([])
            ->and($reader->revertIdentifierChanges($new))->toBe([])
            ->and($reader->revertIdentifierChanges(new class {
                public int $id = 1;
            }))->toBe([])
            ->and($reader->revertIdentifierChanges($kept))->toBe(['line' => 'This value cannot be changed.']);
    });
});

describe('findTakenIdentifiers()', function (): void {
    it('refuses a new record on a key a stored record holds, under every field the user types, after one lookup', function (array $seed, Closure $claim, array $fields): void {
        $this->seed(...$seed);

        $record = $claim($this->entityManager());
        $statements = count($this->statementsRun());

        expect($this::getContainer()->get(AssociationReader::class)->findTakenIdentifiers($record))->toBe(array_fill_keys($fields, 'This value is already used.'))
            ->and($this->statementsRun())->toHaveCount($statements + 1);
    })->with([
        'two scalars' => fn (): array => [[new OrderLine(order: 7, line: 2)], static fn (): OrderLine => new OrderLine(order: 7, line: 2), ['order', 'line']],
        'an association plus a scalar' => fn (): array => [[new InvoiceLine(new Invoice(7), line: 2)], static fn (EntityManagerInterface $em): InvoiceLine => new InvoiceLine($em->find(Invoice::class, 7), line: 2), ['line']],
        'the key of a sibling class in a hierarchy, through its root' => fn (): array => [[new StatuePlinth('hall', bay: 3)], static fn (): ExhibitPlinth => new ExhibitPlinth('hall', bay: 3), ['site', 'bay']],
    ]);

    it('refuses a key the manager holds already without a statement, stored or only persisted', function (Closure $hold): void {
        $em = $this->entityManager();
        $hold($em);
        $statements = count($this->statementsRun());

        expect($this::getContainer()->get(AssociationReader::class)->findTakenIdentifiers(new OrderLine(order: 7, line: 2)))
            ->toBe(['order' => 'This value is already used.', 'line' => 'This value is already used.'])
            ->and($this->statementsRun())->toHaveCount($statements);
    })->with([
        'a loaded record' => [static function (EntityManagerInterface $em): void {
            $em->persist(new OrderLine(order: 7, line: 2));
            $em->flush();
            $em->clear();
            $em->find(OrderLine::class, ['order' => 7, 'line' => 2]);
        }],
        'a record persisted and not flushed' => [static fn (EntityManagerInterface $em) => $em->persist(new OrderLine(order: 7, line: 2))],
    ]);

    it('answers a free key free after one lookup, and looks nothing up for a generated identifier or an incomplete key', function (object $record, int $statements): void {
        expect($this::getContainer()->get(AssociationReader::class)->findTakenIdentifiers($record))->toBe([])
            ->and($this->statementsRun())->toHaveCount($statements);
    })->with([
        'a free key' => fn (): array => [new OrderLine(order: 7, line: 2), 1],
        'a generated identifier' => fn (): array => [new Product('Lamp'), 0],
        'a component left null' => fn (): array => [new AisleSeat('north', aisle: 3), 0],
        'a blank string half' => fn (): array => [new HostPathRedirect('', '/home'), 0],
        'a half whose value prints blank' => fn (): array => [new CipherVault(new Cipher(''), 'north'), 0],
    ]);

    it('refuses the second of two new records claiming one key in the same save', function (bool $stored, int $statements): void {
        $atelier = new Atelier('north');

        if ($stored) {
            $this->seed($atelier);
            $atelier = $this->entityManager()->find(Atelier::class, $atelier->id);
            $atelier->plaques->toArray();
        }

        $atelier->plaques->add(new AtelierPlaque($atelier, 'en'));
        $atelier->plaques->add(new AtelierPlaque($atelier, 'en'));

        $before = count($this->statementsRun());

        expect($this::getContainer()->get(AssociationReader::class)->findTakenIdentifiers($atelier))->toBe(['plaques[1].locale' => 'This value is already used.'])
            ->and($this->statementsRun())->toHaveCount($before + $statements);
    })->with([
        'under a new owner, without a statement' => [false, 0],
        'under a stored owner, after one lookup for the first' => [true, 1],
    ]);

    it('refuses the second of two new records whose keys differ only in a spelling their column stores alike', function (): void {
        expect($this::getContainer()->get(AssociationReader::class)->findTakenIdentifiers(new CaselessHandle('North', alias: new CaselessHandle('north'))))
            ->toBe(['alias.handle' => 'This value is already used.'])
            ->and($this->statementsRun())->toHaveCount(1);
    });

    it('refuses a new record on a stored key whose association half targets a record that prints blank', function (): void {
        $atelier = new Atelier();
        $atelier->plaques->add(new AtelierPlaque($atelier, 'en'));
        $this->seed($atelier);

        $loaded = $this->entityManager()->find(Atelier::class, $atelier->id);

        expect((string) $loaded)->toBe('')
            ->and($this::getContainer()->get(AssociationReader::class)->findTakenIdentifiers(new AtelierPlaque($loaded, 'en')))->toBe(['locale' => 'This value is already used.']);
    });

    it('places the refusal of a key with no scalar field on the first field that does not point back at the record the walk came from', function (): void {
        $atelier = new Atelier();
        $pigment = new AtelierPigment('ochre');
        $atelier->stocks->add(new AtelierStock($atelier, $pigment));
        $this->seed($atelier, $pigment);

        $em = $this->entityManager();
        $loaded = $em->find(Atelier::class, $atelier->id);
        $ochre = $em->find(AtelierPigment::class, $pigment->id);
        $reader = $this::getContainer()->get(AssociationReader::class);
        $alone = $reader->findTakenIdentifiers(new AtelierStock($loaded, $ochre));
        $loaded->stocks->toArray();
        $loaded->stocks->add(new AtelierStock($loaded, $ochre));

        expect($alone)->toBe(['atelier' => 'This value is already used.'])
            ->and($reader->findTakenIdentifiers($loaded))->toBe(['stocks[1].pigment' => 'This value is already used.']);
    });

    it('finds a holder a SQL filter hides, and leaves the filter on', function (object $holder, Closure $claim, array $fields): void {
        $this->seed($holder);

        $em = $this->entityManager();
        $em->getFilters()->enable(WithdrawnNoteFilter::NAME);
        $hidden = $em->getRepository($holder::class)->findAll();
        $record = $claim($em);

        expect($hidden)->toBe([])
            ->and($this::getContainer()->get(AssociationReader::class)->findTakenIdentifiers($record))->toBe(array_fill_keys($fields, 'This value is already used.'))
            ->and($em->getFilters()->isEnabled(WithdrawnNoteFilter::NAME))->toBeTrue();
    })->with([
        'a single identifier' => fn (): array => [new CodeKeyedSlate('slate-1', note: 'withdrawn'), static fn (): CodeKeyedSlate => new CodeKeyedSlate('slate-1'), ['code']],
        'two scalars' => fn (): array => [new OrderLine(order: 7, line: 2, note: 'withdrawn'), static fn (): OrderLine => new OrderLine(order: 7, line: 2), ['order', 'line']],
        'an association plus a scalar' => fn (): array => [new InvoiceLine(new Invoice(7), line: 2, note: 'withdrawn'), static fn (EntityManagerInterface $em): InvoiceLine => new InvoiceLine($em->find(Invoice::class, 7), line: 2), ['line']],
    ]);

    it('answers a free key free in one lookup while a SQL filter is on', function (): void {
        $this->entityManager()->getFilters()->enable(WithdrawnNoteFilter::NAME);

        expect($this::getContainer()->get(AssociationReader::class)->findTakenIdentifiers(new OrderLine(order: 7, line: 2)))->toBe([])
            ->and($this->statementsRun())->toHaveCount(1);
    });

    it('reads a detached record as a new one, so its own unchanged key answers taken', function (): void {
        $this->seed(new OrderLine(order: 7, line: 2));

        $em = $this->entityManager();
        $line = $em->find(OrderLine::class, ['order' => 7, 'line' => 2]);
        $em->detach($line);

        expect($this::getContainer()->get(AssociationReader::class)->findTakenIdentifiers($line))
            ->toBe(['order' => 'This value is already used.', 'line' => 'This value is already used.']);
    });
});

describe('createOptionQuery()', function (): void {
    it('selects every record of the target under the alias it is given, in no order and with no limit', function (): void {
        $this->seed(new Product('Lamp'), new Product('Desk'));

        $reader = $this::getContainer()->get(AssociationReader::class);
        $query = $reader->createOptionQuery(Product::class, alias: 'product');

        expect($query->getRootAliases())->toBe(['product'])
            ->and($query->getDQLPart('orderBy'))->toBe([])
            ->and($query->getMaxResults())->toBeNull()
            ->and(array_map(static fn (Product $product): string => $product->name, $query->getQuery()->getResult()))->toEqualCanonicalizing(['Lamp', 'Desk'])
            ->and($reader->createOptionQuery(Product::class)->getRootAliases())->toBe(['record']);
    });
});

describe('remove(), persist() and flush()', function (): void {
    it('writes a created record only at flush(), after which it has a key', function (): void {
        $reader = $this::getContainer()->get(AssociationReader::class);
        $product = new Product('Lamp');
        $reader->persist($product);
        $persisted = $this->statementsRun();
        $reader->flush();

        expect($persisted)->toBe([])
            ->and($this->statementsRun())->toContain('"COMMIT"')
            ->and($reader->getRecordKey($product))->toBe('1');
    });

    it('deletes a record at the next flush()', function (): void {
        $this->seed(new Product('Lamp'));

        $em = $this->entityManager();
        $reader = $this::getContainer()->get(AssociationReader::class);
        $reader->remove($em->find(Product::class, 1));

        $removed = count($this->statementsRun());
        $reader->flush();
        $em->clear();

        expect($removed)->toBe(1)
            ->and($em->find(Product::class, 1))->toBeNull();
    });
});
