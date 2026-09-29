<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\State;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\PersistentCollection;
use LogicException;
use Sylament\Schemas\Identity\RecordKey;
use Sylament\Schemas\State\AssociationReader;
use Sylament\Schemas\Tests\Fixtures\Concerns\RecordFixture;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Cipher;
use Sylament\Schemas\Tests\Fixtures\Doctrine\CompositeOwnerRig;
use Sylament\Schemas\Tests\Fixtures\Doctrine\CompositeTargetRig;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Chain;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Crate;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Listing;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Placard;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Post;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\PostSeal;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\PostTranslation;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Redirect;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Ribbon;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Sheet;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Shortlink;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Ticket;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Vault;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Zone;
use Sylament\Schemas\Tests\Fixtures\Doctrine\IdentifierGuardRig;
use Sylament\Schemas\Tests\Fixtures\Doctrine\IdentityRig;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Sku;
use Sylament\Support\Tests\Doctrine\RecordingMiddleware;
use Sylament\Support\Tests\Doctrine\TestPlatform;
use Symfony\Component\Uid\Uuid;

/*
 * The changed-key pass: every managed record the record's loaded associations
 * reach has each identifier field compared with the value the unit of work keyed
 * it by, and a changed one is answered at its path and put back on the spot —
 * running no statement and loading nothing.
 */

/**
 * A stored record of the named shape, freshly read.
 */
function changedIdentifierRecord(EntityManager $entityManager, string $shape): object
{
    return match ($shape) {
        'single' => IdentifierGuardRig::shortlink($entityManager),
        'pair' => CompositeOwnerRig::findRedirect($entityManager, 'example.com', '/a'),
        'derived', 'derived association' => CompositeOwnerRig::findTranslation($entityManager, 1, 'en'),
        'single derived' => CompositeTargetRig::findSeal($entityManager, 1),
        'chained' => CompositeOwnerRig::findChain($entityManager, 1, 1),
        'binary uuid' => CompositeOwnerRig::findCrate($entityManager, CompositeOwnerRig::CRATE_MINE),
        'cipher' => CompositeOwnerRig::findVault($entityManager, 'zeta', 'north'),
        'sku' => CompositeOwnerRig::findListing($entityManager, 'AB-123'),
        'enum' => CompositeOwnerRig::findPlacard($entityManager, Zone::Header),
        'guid', 'bigint' => IdentifierGuardRig::ticket($entityManager),
        'identity' => CompositeTargetRig::post($entityManager, 1),
        'member' => IdentifierGuardRig::binder($entityManager, 1)->getSheets()->first() ?: throw new LogicException('The rig seeds one sheet.'),
        default => IdentifierGuardRig::binder($entityManager, 1)->getRibbons()->first() ?: throw new LogicException('The rig seeds one ribbon.'),
    };
}

/**
 * Move the named shape's identifier field to a value no row holds.
 */
function changeIdentifierOf(EntityManager $entityManager, string $shape, object $record): void
{
    match (true) {
        $record instanceof Shortlink => $record->setCode('gone'),
        $record instanceof Redirect => $record->setHost('elsewhere.com'),
        $record instanceof PostTranslation && $shape === 'derived' => $record->setLocale('fr'),
        $record instanceof PostTranslation => $record->setPost($entityManager->getReference(Post::class, 3)),
        $record instanceof PostSeal => $record->setPost(CompositeTargetRig::post($entityManager, 3)),
        $record instanceof Chain => $record->setLink(99),
        $record instanceof Crate => $record->setId(Uuid::fromString('a1b2c3d4-0000-4000-8000-0000000000ff')),
        $record instanceof Vault => $record->setCipher(new Cipher('omega')),
        $record instanceof Listing => $record->setSku(new Sku('ZZ-999')),
        $record instanceof Placard => $record->setZone(Zone::Footer),
        $record instanceof Ticket && $shape === 'guid' => $record->setId(mb_strtoupper(IdentifierGuardRig::TICKET_ID)),
        $record instanceof Ticket => $record->setSerial(8),
        $record instanceof Post => $entityManager->getClassMetadata(Post::class)->setFieldValue($record, 'id', 99),
        $record instanceof Sheet => $record->setCode('outro'),
        $record instanceof Ribbon => $record->setTag(CompositeTargetRig::tag($entityManager, 2)),
        default => throw new LogicException(sprintf('No change is written for [%s].', $record::class)),
    };
}

it('refuses a changed identifier field at its path, puts the stored value back and leaves the flush nothing to write', function (string $shape, string $field, string $key): void {
    $recorder = new RecordingMiddleware();
    $entityManager = IdentifierGuardRig::create($recorder);
    $record = changedIdentifierRecord($entityManager, $shape);

    changeIdentifierOf($entityManager, $shape, $record);
    $recorder->reset();

    $refusals = new AssociationReader($entityManager)->revertIdentifierChanges($record);

    $unitOfWork = $entityManager->getUnitOfWork();
    $unitOfWork->computeChangeSets();

    expect($refusals)->toBe([$field => 'This value cannot be changed.'])
        ->and($recorder->statements())->toBe([])
        ->and(RecordKey::of($entityManager, $record))->toBe($key)
        ->and($unitOfWork->getEntityChangeSet($record))->toBe([]);

    $entityManager->flush();

    expect($recorder->statements())->toBe([]);
})->with([
    'a single assigned string' => ['single', 'code', 'go'],
    'a scalar pair' => ['pair', 'host', 'example=2Ecom~=2Fa'],
    'the scalar half of an association plus a scalar' => ['derived', 'locale', '1~en'],
    'the association half, moved onto a reference' => ['derived association', 'post', '1~en'],
    'a single derived identity' => ['single derived', 'post', '1'],
    'a chained derived identity' => ['chained', 'link', '1~1'],
    'a binary uuid' => ['binary uuid', 'id', 'a1b2c3d4-0000-4000-8000-000000000001'],
    'a doubly-strict cipher' => ['cipher', 'cipher', 'zeta~north'],
    'a converting sku' => ['sku', 'sku', 'AB-123'],
    'a backed enum' => ['enum', 'zone', 'header'],
    'the guid half, in another spelling of the same uuid' => ['guid', 'id', 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11~7'],
    'the bigint half' => ['bigint', 'serial', 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11~7'],
    'a generated IDENTITY id' => ['identity', 'id', '1'],
    'the code half of a collection member keyed by its owner' => ['member', 'code', '1~intro'],
    'one association of a two-association key' => ['ribbon', 'tag', '1~1'],
]);

it('refuses every moved field of one key at its own path and puts each of them back', function (): void {
    $recorder = new RecordingMiddleware();
    $entityManager = IdentifierGuardRig::create($recorder);
    $redirect = CompositeOwnerRig::findRedirect($entityManager, 'example.com', '/a');

    $redirect->setHost('elsewhere.com');
    $redirect->setPath('/moved');

    $recorder->reset();

    expect(new AssociationReader($entityManager)->revertIdentifierChanges($redirect))->toBe(['host' => 'This value cannot be changed.', 'path' => 'This value cannot be changed.'])
        ->and($recorder->statements())->toBe([])
        ->and(RecordKey::of($entityManager, $redirect))->toBe('example=2Ecom~=2Fa');
});

it('answers nothing for the same key held in another PHP value, and puts nothing back', function (string $change, string $key): void {
    $recorder = new RecordingMiddleware();
    $entityManager = IdentifierGuardRig::create($recorder);

    $record = match ($change) {
        'bigint' => IdentifierGuardRig::ticket($entityManager),
        'uuid' => CompositeOwnerRig::findCrate($entityManager, CompositeOwnerRig::CRATE_MINE),
        'enum' => CompositeOwnerRig::findPlacard($entityManager, Zone::Header),
        'sku' => CompositeOwnerRig::findListing($entityManager, 'AB-123'),
        'cipher' => CompositeOwnerRig::findVault($entityManager, 'zeta', 'north'),
        'same' => CompositeOwnerRig::findRedirect($entityManager, 'example.com', '/a'),
        'reference' => CompositeTargetRig::findSeal($entityManager, 1),
        default => CompositeOwnerRig::findRedirect($entityManager, 'example.com', '/b'),
    };

    match (true) {
        $record instanceof Ticket => $record->setSerial('7'),
        $record instanceof Crate => $record->setId(Uuid::fromString(CompositeOwnerRig::CRATE_MINE)),
        $record instanceof Placard => $record->setZone(Zone::Header),
        $record instanceof Listing => $record->setSku(new Sku('ab-123')),
        $record instanceof Vault => $record->setCipher(new Cipher('zeta')),
        $record instanceof PostSeal => $record->setPost($entityManager->getReference(Post::class, 1)),
        $record instanceof Redirect && $change === 'same' => $record->setHost('example.com'),
        default => null,
    };

    $recorder->reset();

    expect(new AssociationReader($entityManager)->revertIdentifierChanges($record))->toBe([])
        ->and($recorder->statements())->toBe([])
        ->and(RecordKey::of($entityManager, $record))->toBe($key);

    if ($record instanceof Ticket) {
        expect($record->getSerial())->toBe('7');
    }
})->with([
    "'7' over the bigint 7 an int|string property holds" => ['bigint', 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11~7'],
    'an equal uuid in a new instance' => ['uuid', 'a1b2c3d4-0000-4000-8000-000000000001'],
    'the same enum case set again' => ['enum', 'header'],
    'a sku the type normalises to the stored spelling' => ['sku', 'AB-123'],
    'an equal doubly-strict cipher' => ['cipher', 'zeta~north'],
    'the stored value written back' => ['same', 'example=2Ecom~=2Fa'],
    'the identifier target set again as a reference to itself' => ['reference', '1'],
    'an untouched record' => ['untouched', 'example=2Ecom~=2Fb'],
]);

/**
 * A stored shortlink whose code holds a NUL byte, so its key has no printable
 * form, freshly read.
 *
 * @return array{EntityManager, RecordingMiddleware, Shortlink}
 */
function changedIdentifierUnprintableShortlink(): array
{
    $recorder = new RecordingMiddleware();
    $entityManager = IdentifierGuardRig::create($recorder);
    $entityManager->getConnection()->executeStatement('INSERT INTO Shortlink (code, target) VALUES (?, ?)', ["a\0b", '/nul']);
    $entityManager->clear();

    $record = $entityManager->find(Shortlink::class, "a\0b") ?? throw new LogicException('The shortlink was inserted a line above.');

    return [$entityManager, $recorder, $record];
}

it('answers nothing for an untouched stored key that has no printable form', function (): void {
    [$entityManager, $recorder, $record] = changedIdentifierUnprintableShortlink();
    $recorder->reset();

    expect(new AssociationReader($entityManager)->revertIdentifierChanges($record))->toBe([])
        ->and($recorder->statements())->toBe([])
        ->and($record->getCode())->toBe("a\0b");
})->skip(TestPlatform::current() === TestPlatform::PostgreSql, 'pdo_pgsql truncates a NUL byte in a bound string, so PostgreSQL cannot store the identifier this test is about.');

it('refuses a change between two spellings that both have no printable form, and puts the stored one back', function (): void {
    [$entityManager, $recorder, $record] = changedIdentifierUnprintableShortlink();
    $record->setCode("a\0c");
    $recorder->reset();

    expect(new AssociationReader($entityManager)->revertIdentifierChanges($record))->toBe(['code' => 'This value cannot be changed.'])
        ->and($recorder->statements())->toBe([])
        ->and($record->getCode())->toBe("a\0b");
})->skip(TestPlatform::current() === TestPlatform::PostgreSql, 'pdo_pgsql truncates a NUL byte in a bound string, so PostgreSQL cannot store the identifier this test is about.');

it('answers nothing for any untouched stored record of any shape the rigs seed', function (): void {
    $census = [];

    foreach (['IdentityRig' => IdentityRig::create(), 'IdentifierGuardRig' => IdentifierGuardRig::create()] as $rig => $entityManager) {
        $reader = new AssociationReader($entityManager);
        $census[$rig] = ['checked' => 0, 'refused' => []];

        foreach ($entityManager->getMetadataFactory()->getAllMetadata() as $metadata) {
            if ($metadata->isMappedSuperclass) {
                continue;
            }

            $entityManager->clear();

            foreach ($entityManager->getRepository($metadata->getName())->findAll() as $record) {
                $census[$rig]['checked']++;

                if ($reader->revertIdentifierChanges($record) !== []) {
                    $census[$rig]['refused'][] = $record::class;
                }
            }
        }
    }

    expect($census)->toBe([
        'IdentityRig' => ['checked' => match (TestPlatform::current()) {
            TestPlatform::PostgreSql => 15,
            TestPlatform::Sqlite, TestPlatform::MySql => 16,
        }, 'refused' => []],
        'IdentifierGuardRig' => ['checked' => 103, 'refused' => []],
    ]);
});

it('answers nothing for a record read again after the manager was cleared', function (): void {
    $entityManager = IdentifierGuardRig::create();
    $reader = new AssociationReader($entityManager);

    CompositeOwnerRig::findRedirect($entityManager, 'example.com', '/a')->setHost('elsewhere.com');
    $entityManager->clear();

    expect($reader->revertIdentifierChanges(CompositeOwnerRig::findRedirect($entityManager, 'example.com', '/a')))->toBe([]);
});

it('skips a record the unit of work does not key, or is about to insert or delete, and leaves its change alone', function (string $state): void {
    $entityManager = IdentifierGuardRig::create();

    if (in_array($state, ['new', 'scheduled for insert'], true)) {
        $record = new Redirect();
        $record->setHost('new.com');
        $record->setPath('/new');
    } else {
        $record = CompositeOwnerRig::findRedirect($entityManager, 'example.com', '/a');
    }

    match ($state) {
        'detached' => $entityManager->detach($record),
        'scheduled for insert' => $entityManager->persist($record),
        'scheduled for delete' => $entityManager->remove($record),
        default => null,
    };

    $record->setHost('moved.com');

    expect(new AssociationReader($entityManager)->revertIdentifierChanges($record))->toBe([])
        ->and($record->getHost())->toBe('moved.com');
})->with([
    'detached' => ['detached'],
    'new, never persisted' => ['new'],
    'scheduled for insert' => ['scheduled for insert'],
    'scheduled for delete' => ['scheduled for delete'],
]);

it('guards nothing without an entity manager', function (): void {
    $entityManager = IdentifierGuardRig::create();
    $record = CompositeOwnerRig::findRedirect($entityManager, 'example.com', '/a');

    $record->setHost('moved.com');

    expect(new AssociationReader()->revertIdentifierChanges($record))->toBe([])
        ->and($record->getHost())->toBe('moved.com');
});

it('skips a record whose class the mapping does not know, an anonymous one included', function (object $record): void {
    expect(new AssociationReader(IdentifierGuardRig::create())->revertIdentifierChanges($record))->toBe([]);
})->with([
    'an anonymous class' => [new class {
        public string $code = 'anonymous';
    }],
    'a transient class' => [new RecordFixture()],
]);

it('reaches a changed member of a loaded collection by its index', function (): void {
    $recorder = new RecordingMiddleware();
    $entityManager = IdentifierGuardRig::create($recorder);
    $post = CompositeTargetRig::post($entityManager, 1);
    $translations = $post->getTranslations();

    $locales = array_map(static fn (PostTranslation $translation): string => $translation->getLocale(), $translations->toArray());
    $index = array_search('de', $locales, true);

    assert(is_int($index));

    expect($locales)->toEqualCanonicalizing(['en', 'de']);

    $translations[$index]?->setLocale('xx');
    $recorder->reset();

    expect(new AssociationReader($entityManager)->revertIdentifierChanges($post))->toBe([sprintf('translations[%d].locale', $index) => 'This value cannot be changed.'])
        ->and($recorder->statements())->toBe([])
        ->and($translations[$index]?->getLocale())->toBe('de');
});

it('reaches a changed record behind a loaded to-one by its property path', function (): void {
    $entityManager = IdentifierGuardRig::create();
    $binder = IdentifierGuardRig::binder($entityManager, 1);
    $spine = $binder->getSpine() ?? throw new LogicException('The rig seeds a spine on the first binder.');

    expect($spine->getLabel())->toBe('stored spine');

    $spine->setCode('SP-9');

    expect(new AssociationReader($entityManager)->revertIdentifierChanges($binder))->toBe(['spine.code' => 'This value cannot be changed.'])
        ->and($spine->getCode())->toBe(IdentifierGuardRig::SPINE);
});

it('visits each record once through a cycle, so a change is answered at the first path reaching it', function (): void {
    $entityManager = IdentifierGuardRig::create();
    $translation = CompositeOwnerRig::findTranslation($entityManager, 1, 'en');
    $post = $translation->getPost() ?? throw new LogicException('The rig seeds the translation onto a post.');

    expect($post->getTranslations()->contains($translation))->toBeTrue();

    $translation->setLocale('xx');

    expect(new AssociationReader($entityManager)->revertIdentifierChanges($translation))->toBe(['locale' => 'This value cannot be changed.']);
});

it('leaves an uninitialized reference unloaded', function (): void {
    $recorder = new RecordingMiddleware();
    $entityManager = IdentifierGuardRig::create($recorder);
    $translation = CompositeOwnerRig::findTranslation($entityManager, 1, 'en');
    $post = $translation->getPost() ?? throw new LogicException('The rig seeds the translation onto a post.');

    expect($entityManager->isUninitializedObject($post))->toBeTrue();

    $recorder->reset();

    expect(new AssociationReader($entityManager)->revertIdentifierChanges($translation))->toBe([])
        ->and($recorder->statements())->toBe([])
        ->and($entityManager->isUninitializedObject($post))->toBeTrue();
});

it('leaves an uninitialized collection unloaded', function (): void {
    $recorder = new RecordingMiddleware();
    $entityManager = IdentifierGuardRig::create($recorder);
    $post = CompositeTargetRig::post($entityManager, 1);
    $translations = $post->getTranslations();

    expect($translations)->toBeInstanceOf(PersistentCollection::class)
        ->and($translations instanceof PersistentCollection && $translations->isInitialized())->toBeFalse();

    $recorder->reset();

    expect(new AssociationReader($entityManager)->revertIdentifierChanges($post))->toBe([])
        ->and($recorder->statements())->toBe([])
        ->and($translations instanceof PersistentCollection && $translations->isInitialized())->toBeFalse();
});

it('refuses by name to put back an identifier association the unit of work holds no original value for', function (): void {
    $entityManager = IdentifierGuardRig::create();
    $translation = CompositeOwnerRig::findTranslation($entityManager, 1, 'en');

    $entityManager->getUnitOfWork()->setOriginalEntityProperty(spl_object_id($translation), 'post', null);
    $translation->setPost(CompositeTargetRig::post($entityManager, 3));

    expect(fn (): array => new AssociationReader($entityManager)->revertIdentifierChanges($translation))
        ->toThrow(LogicException::class, '[' . PostTranslation::class . '::$post] is an identifier association the unit of work holds no original value for, so its change cannot be put back.');
});
