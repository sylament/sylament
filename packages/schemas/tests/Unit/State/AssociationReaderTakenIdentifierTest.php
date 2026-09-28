<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\State;

use Doctrine\ORM\EntityManager;
use Sylament\Schemas\State\AssociationReader;
use Sylament\Schemas\Tests\Fixtures\Concerns\RecordFixture;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Cipher;
use Sylament\Schemas\Tests\Fixtures\Doctrine\CompositeOwnerRig;
use Sylament\Schemas\Tests\Fixtures\Doctrine\CompositeTargetRig;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Binder;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Carton;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Chain;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\ChilledCarton;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Crate;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Divider;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Enrolment;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Headband;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Listing;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Pennant;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Placard;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Plinth;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\PlinthCap;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Post;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\PostSeal;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\PostTranslation;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Redirect;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Ribbon;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Sheet;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Shortlink;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Sigil;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\SigilChild;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\SigilRack;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Slate;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Spine;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Ticket;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Vault;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Zone;
use Sylament\Schemas\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Schemas\Tests\Fixtures\Doctrine\HiddenRedirectFilter;
use Sylament\Schemas\Tests\Fixtures\Doctrine\IdentifierGuardRig;
use Sylament\Schemas\Tests\Fixtures\Doctrine\RecordingMiddleware;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Sku;
use Symfony\Component\Uid\Uuid;

/*
 * The taken-key pass: every new record the record's loaded cascade-persist
 * associations reach — what a flush would insert — whose assigned key a stored
 * or managed record already holds, or a new record met earlier in the walk
 * claims. One primary-key SELECT per new record, none when the holder is
 * managed, none for a generated id or a key still missing a component.
 */

/**
 * A new record of the named shape on the key the rig stores, or on one it does
 * not.
 */
function takenIdentifierRecord(EntityManager $entityManager, string $shape, bool $free = false): object
{
    switch ($shape) {
        case 'single':
            $record = new Shortlink();
            $record->setCode($free ? 'free' : IdentifierGuardRig::SHORTLINK);

            return $record;
        case 'pair':
            $record = new Redirect();
            $record->setHost('example.com');
            $record->setPath($free ? '/free' : '/a');

            return $record;
        case 'derived':
            $record = new PostTranslation();
            $record->setPost(CompositeTargetRig::post($entityManager, 1));
            $record->setLocale($free ? 'it' : 'en');

            return $record;
        case 'single derived':
            $record = new PostSeal();
            $record->setPost(CompositeTargetRig::post($entityManager, $free ? 3 : 1));

            return $record;
        case 'chained':
            $record = new Chain();
            $record->setSeal(CompositeTargetRig::findSeal($entityManager, 1));
            $record->setLink($free ? 50 : 1);

            return $record;
        case 'binary uuid':
            $record = new Crate();
            $record->setId(Uuid::fromString($free ? 'a1b2c3d4-0000-4000-8000-0000000000ff' : CompositeOwnerRig::CRATE_MINE));

            return $record;
        case 'cipher':
            $record = new Vault();
            $record->setCipher(new Cipher('zeta'));
            $record->setZone($free ? 'east' : 'north');

            return $record;
        case 'sku':
            $record = new Listing();
            $record->setSku(new Sku($free ? 'EF-789' : 'ab-123'));

            return $record;
        case 'enum':
            $record = new Placard();
            $record->setZone(Zone::Header);

            return $record;
        default:
            $record = new Ticket();
            $record->setId(IdentifierGuardRig::TICKET_ID);
            $record->setSerial($free ? 8 : '7');

            return $record;
    }
}

/**
 * The stored record of the named shape, read into the identity map.
 */
function takenIdentifierHolder(EntityManager $entityManager, string $shape): object
{
    return match ($shape) {
        'single' => IdentifierGuardRig::shortlink($entityManager),
        'pair' => CompositeOwnerRig::findRedirect($entityManager, 'example.com', '/a'),
        'derived' => CompositeOwnerRig::findTranslation($entityManager, 1, 'en'),
        'single derived' => CompositeTargetRig::findSeal($entityManager, 1),
        'chained' => CompositeOwnerRig::findChain($entityManager, 1, 1),
        'binary uuid' => CompositeOwnerRig::findCrate($entityManager, CompositeOwnerRig::CRATE_MINE),
        'cipher' => CompositeOwnerRig::findVault($entityManager, 'zeta', 'north'),
        'sku' => CompositeOwnerRig::findListing($entityManager, 'AB-123'),
        'enum' => CompositeOwnerRig::findPlacard($entityManager, Zone::Header),
        default => IdentifierGuardRig::ticket($entityManager),
    };
}

/**
 * The binder the rig stores with no child, its children read — so a new child
 * is the only one it holds.
 */
function takenIdentifierEmptyBinder(EntityManager $entityManager): Binder
{
    $binder = IdentifierGuardRig::binder($entityManager, 2);

    $binder->getSheets()->toArray();
    $binder->getSlates()->toArray();
    $binder->getDividers()->toArray();

    return $binder;
}

function takenIdentifierSheet(string $code): Sheet
{
    $sheet = new Sheet();
    $sheet->setCode($code);
    $sheet->setTitle($code);

    return $sheet;
}

dataset('taken identifier shapes', [
    'a single assigned string' => ['single', 'code', 'SELECT t0.code AS code_1, t0.target AS target_2 FROM Shortlink t0 WHERE t0.code = ?'],
    'a scalar pair' => ['pair', 'host', 'SELECT t0.host AS host_1, t0.path AS path_2, t0.label AS label_3, t0.parent_host AS parent_host_4, t0.parent_path AS parent_path_5 FROM Redirect t0 WHERE t0.host = ? AND t0.path = ?'],
    'an association plus a scalar' => ['derived', 'locale', 'SELECT t0.locale AS locale_1, t0.title AS title_2, t0.post_id AS post_id_3 FROM PostTranslation t0 WHERE t0.post_id = ? AND t0.locale = ?'],
    'a single derived identity' => ['single derived', 'post', 'SELECT t0.note AS note_1, t0.post_id AS post_id_2 FROM PostSeal t0 WHERE t0.post_id = ?'],
    'a chained derived identity' => ['chained', 'link', 'SELECT t0.link AS link_1, t0.label AS label_2, t0.seal_post_id AS seal_post_id_3 FROM Chain t0 WHERE t0.seal_post_id = ? AND t0.link = ?'],
    'a binary uuid' => ['binary uuid', 'id', 'SELECT t0.id AS id_1, t0.label AS label_2 FROM Crate t0 WHERE t0.id = ?'],
    'a doubly-strict cipher' => ['cipher', 'cipher', 'SELECT t0.cipher AS cipher_1, t0.zone AS zone_2, t0.label AS label_3 FROM Vault t0 WHERE t0.cipher = ? AND t0.zone = ?'],
    'a converting sku, typed in another spelling' => ['sku', 'sku', 'SELECT t0.sku AS sku_1, t0.title AS title_2 FROM Listing t0 WHERE t0.sku = ?'],
    'a backed enum' => ['enum', 'zone', 'SELECT t0.zone AS zone_1, t0.label AS label_2 FROM Placard t0 WHERE t0.zone = ?'],
    'a guid beside a bigint, the bigint typed as a string' => ['guid', 'id', 'SELECT t0.id AS id_1, t0.serial AS serial_2, t0.subject AS subject_3 FROM Ticket t0 WHERE t0.id = ? AND t0.serial = ?'],
]);

it('refuses a new record on a stored key with one primary-key SELECT', function (string $shape, string $field, string $select): void {
    $recorder = new RecordingMiddleware();
    $entityManager = IdentifierGuardRig::create($recorder);
    $record = takenIdentifierRecord($entityManager, $shape);

    $recorder->reset();

    expect(new AssociationReader($entityManager)->findTakenIdentifiers($record))->toBe([$field => 'This value is already used.'])
        ->and($recorder->statements())->toBe([$select]);
})->with('taken identifier shapes');

it('refuses a new record on the key of a managed record with no statement', function (string $shape, string $field): void {
    $recorder = new RecordingMiddleware();
    $entityManager = IdentifierGuardRig::create($recorder);

    takenIdentifierHolder($entityManager, $shape);
    $record = takenIdentifierRecord($entityManager, $shape);
    $recorder->reset();

    expect(new AssociationReader($entityManager)->findTakenIdentifiers($record))->toBe([$field => 'This value is already used.'])
        ->and($recorder->statements())->toBe([]);
})->with('taken identifier shapes');

it('refuses a new record on the key of one persisted earlier and not yet flushed, with no statement', function (string $shape, string $field): void {
    $recorder = new RecordingMiddleware();
    $entityManager = IdentifierGuardRig::create($recorder);

    $entityManager->persist(takenIdentifierRecord($entityManager, $shape));

    $record = takenIdentifierRecord($entityManager, $shape);
    $recorder->reset();

    expect(new AssociationReader($entityManager)->findTakenIdentifiers($record))->toBe([$field => 'This value is already used.'])
        ->and($recorder->statements())->toBe([]);
})->with('taken identifier shapes');

it('answers nothing for a new record on a free key, after one primary-key SELECT', function (string $shape, string $field, string $select): void {
    $recorder = new RecordingMiddleware();
    $entityManager = IdentifierGuardRig::create($recorder);
    $record = takenIdentifierRecord($entityManager, $shape, free: true);

    $recorder->reset();

    expect(new AssociationReader($entityManager)->findTakenIdentifiers($record))->toBe([])
        ->and($recorder->statements())->toBe([$select]);
})->with([
    'a single assigned string' => ['single', 'code', 'SELECT t0.code AS code_1, t0.target AS target_2 FROM Shortlink t0 WHERE t0.code = ?'],
    'a scalar pair' => ['pair', 'host', 'SELECT t0.host AS host_1, t0.path AS path_2, t0.label AS label_3, t0.parent_host AS parent_host_4, t0.parent_path AS parent_path_5 FROM Redirect t0 WHERE t0.host = ? AND t0.path = ?'],
    'an association plus a scalar' => ['derived', 'locale', 'SELECT t0.locale AS locale_1, t0.title AS title_2, t0.post_id AS post_id_3 FROM PostTranslation t0 WHERE t0.post_id = ? AND t0.locale = ?'],
    'a single derived identity' => ['single derived', 'post', 'SELECT t0.note AS note_1, t0.post_id AS post_id_2 FROM PostSeal t0 WHERE t0.post_id = ?'],
    'a chained derived identity' => ['chained', 'link', 'SELECT t0.link AS link_1, t0.label AS label_2, t0.seal_post_id AS seal_post_id_3 FROM Chain t0 WHERE t0.seal_post_id = ? AND t0.link = ?'],
    'a binary uuid' => ['binary uuid', 'id', 'SELECT t0.id AS id_1, t0.label AS label_2 FROM Crate t0 WHERE t0.id = ?'],
    'a doubly-strict cipher' => ['cipher', 'cipher', 'SELECT t0.cipher AS cipher_1, t0.zone AS zone_2, t0.label AS label_3 FROM Vault t0 WHERE t0.cipher = ? AND t0.zone = ?'],
    'a converting sku' => ['sku', 'sku', 'SELECT t0.sku AS sku_1, t0.title AS title_2 FROM Listing t0 WHERE t0.sku = ?'],
    'a guid beside a bigint' => ['guid', 'id', 'SELECT t0.id AS id_1, t0.serial AS serial_2, t0.subject AS subject_3 FROM Ticket t0 WHERE t0.id = ? AND t0.serial = ?'],
]);

it('refuses the second of two new siblings on one key in memory, after probing the first', function (): void {
    $recorder = new RecordingMiddleware();
    $entityManager = IdentifierGuardRig::create($recorder);
    $binder = takenIdentifierEmptyBinder($entityManager);

    $binder->addSheet(takenIdentifierSheet('twin'));
    $binder->addSheet(takenIdentifierSheet('twin'));

    $recorder->reset();

    expect(new AssociationReader($entityManager)->findTakenIdentifiers($binder))->toBe(['sheets[1].code' => 'This value is already used.'])
        ->and($recorder->statements())->toBe(['SELECT t0.code AS code_1, t0.title AS title_2, t0.binder_id AS binder_id_3 FROM Sheet t0 WHERE t0.binder_id = ? AND t0.code = ?']);
});

it('refuses the second of two new siblings under a new owner, whose key nothing can look up yet', function (): void {
    $recorder = new RecordingMiddleware();
    $entityManager = IdentifierGuardRig::create($recorder);
    $binder = new Binder();

    $binder->addSheet(takenIdentifierSheet('twin'));
    $binder->addSheet(takenIdentifierSheet('twin'));

    $recorder->reset();

    expect(new AssociationReader($entityManager)->findTakenIdentifiers($binder))->toBe(['sheets[1].code' => 'This value is already used.'])
        ->and($recorder->statements())->toBe([]);
});

it('answers nothing for two new siblings on different keys under a new owner', function (): void {
    $recorder = new RecordingMiddleware();
    $entityManager = IdentifierGuardRig::create($recorder);
    $binder = new Binder();

    $binder->addSheet(takenIdentifierSheet('first'));
    $binder->addSheet(takenIdentifierSheet('second'));

    $recorder->reset();

    expect(new AssociationReader($entityManager)->findTakenIdentifiers($binder))->toBe([])
        ->and($recorder->statements())->toBe([]);
});

it('refuses the second of two new siblings whose keys differ only in a spelling their column stores alike', function (bool $storedOwner): void {
    $recorder = new RecordingMiddleware();
    $entityManager = IdentifierGuardRig::create($recorder);
    $binder = $storedOwner ? takenIdentifierEmptyBinder($entityManager) : new Binder();

    foreach (['AB', 'ab'] as $code) {
        $divider = new Divider();
        $divider->setCode($code);
        $binder->addDivider($divider);
    }

    $recorder->reset();

    expect(new AssociationReader($entityManager)->findTakenIdentifiers($binder))->toBe(['dividers[1].code' => 'This value is already used.'])
        ->and($recorder->statements())->toBe(['SELECT t0.code AS code_1, t0.binder_id AS binder_id_2 FROM Divider t0 WHERE t0.code = ?']);
})->with([
    'under a stored owner' => [true],
    'under a new owner' => [false],
]);

it('answers nothing for two new records of different classes whose key values are equal under one owner', function (): void {
    $recorder = new RecordingMiddleware();
    $entityManager = IdentifierGuardRig::create($recorder);
    $binder = new Binder();

    $slate = new Slate();
    $slate->setCode('same');
    $slate->setLabel('slate');

    $binder->addSlate($slate);

    $divider = new Divider();
    $divider->setCode('same');

    $binder->addDivider($divider);

    $recorder->reset();

    expect(new AssociationReader($entityManager)->findTakenIdentifiers($binder))->toBe([])
        ->and($recorder->statements())->toHaveCount(2);

    $entityManager->persist($binder);
    $entityManager->flush();

    expect($entityManager->getConnection()->fetchOne('SELECT COUNT(*) FROM Slate WHERE code = ?', ['same']))->toBe(1)
        ->and($entityManager->getConnection()->fetchOne('SELECT COUNT(*) FROM Divider WHERE code = ?', ['same']))->toBe(1);
});

it('refuses a new child on the key of a stored sibling its loaded collection holds, with no statement', function (): void {
    $recorder = new RecordingMiddleware();
    $entityManager = IdentifierGuardRig::create($recorder);
    $binder = IdentifierGuardRig::binder($entityManager, 1);

    $binder->addSheet(takenIdentifierSheet(IdentifierGuardRig::SHEET));

    $recorder->reset();

    expect(new AssociationReader($entityManager)->findTakenIdentifiers($binder))->toBe(['sheets[1].code' => 'This value is already used.'])
        ->and($recorder->statements())->toBe([]);
});

it('refuses a new record behind a cascading to-one by its property path', function (): void {
    $recorder = new RecordingMiddleware();
    $entityManager = IdentifierGuardRig::create($recorder);
    $binder = new Binder();
    $spine = new Spine();

    $spine->setCode(IdentifierGuardRig::SPINE);

    $binder->setSpine($spine);
    $recorder->reset();

    expect(new AssociationReader($entityManager)->findTakenIdentifiers($binder))->toBe(['spine.code' => 'This value is already used.'])
        ->and($recorder->statements())->toBe(['SELECT t0.code AS code_1, t0.label AS label_2 FROM Spine t0 WHERE t0.code = ?']);
});

it('walks only the associations a flush would cascade a new record through', function (): void {
    $recorder = new RecordingMiddleware();
    $entityManager = IdentifierGuardRig::create($recorder);
    $post = CompositeTargetRig::post($entityManager, 1);
    $translations = $post->getTranslations();
    $translation = new PostTranslation();

    expect(array_map(static fn (PostTranslation $member): string => $member->getLocale(), $translations->toArray()))->toBe(['en', 'de']);

    $translation->setPost($post);
    $translation->setLocale('en');

    $translations->add($translation);
    $recorder->reset();

    expect(new AssociationReader($entityManager)->findTakenIdentifiers($post))->toBe([])
        ->and($recorder->statements())->toBe([]);
});

it('looks up nothing for a generated id', function (): void {
    $recorder = new RecordingMiddleware();
    $entityManager = IdentifierGuardRig::create($recorder);
    $post = new Post();

    $post->setTitle('generated');

    $recorder->reset();

    expect(new AssociationReader($entityManager)->findTakenIdentifiers($post))->toBe([])
        ->and($recorder->statements())->toBe([]);
});

it('looks up nothing for a generated id, even one set by hand to a stored value the flush discards', function (): void {
    $recorder = new RecordingMiddleware();
    $entityManager = IdentifierGuardRig::create($recorder);
    $post = new Post();

    $post->setTitle('generated');
    $entityManager->getClassMetadata(Post::class)->setFieldValue($post, 'id', 1);
    $recorder->reset();

    expect(new AssociationReader($entityManager)->findTakenIdentifiers($post))->toBe([])
        ->and($recorder->statements())->toBe([]);
});

it('reads a detached record, or one another manager keeps, as a new one — so its own unchanged key answers taken', function (bool $detached): void {
    $recorder = new RecordingMiddleware();
    $entityManager = IdentifierGuardRig::create($recorder);

    if ($detached) {
        $record = IdentifierGuardRig::shortlink($entityManager);
        $entityManager->detach($record);
    } else {
        $record = IdentifierGuardRig::shortlink(IdentifierGuardRig::create());
    }

    $recorder->reset();

    expect(new AssociationReader($entityManager)->findTakenIdentifiers($record))->toBe(['code' => 'This value is already used.'])
        ->and($recorder->statements())->toBe(['SELECT t0.code AS code_1, t0.target AS target_2 FROM Shortlink t0 WHERE t0.code = ?']);
})->with([
    'detached' => [true],
    'kept by another manager over the same mapping' => [false],
]);

it('neither claims nor looks up a key holding a blank or a null component', function (string $shape): void {
    $recorder = new RecordingMiddleware();
    $entityManager = IdentifierGuardRig::create($recorder);

    if ($shape === 'blank siblings') {
        $record = takenIdentifierEmptyBinder($entityManager);
        $record->addSheet(takenIdentifierSheet(''));
        $record->addSheet(takenIdentifierSheet(''));
    } elseif ($shape === 'null half') {
        $record = new PostTranslation();
        $record->setLocale('en');
    } elseif ($shape === 'blank target') {
        $record = new Headband();
        $record->setSpine(new Spine());
    } else {
        $record = new Shortlink();
        $record->setCode($shape === 'whitespace' ? '   ' : '');
    }

    $recorder->reset();

    expect(new AssociationReader($entityManager)->findTakenIdentifiers($record))->toBe([])
        ->and($recorder->statements())->toBe([]);
})->with([
    'two new siblings on a blank code' => ['blank siblings'],
    'a blank single string' => ['blank'],
    'a single string of whitespace' => ['whitespace'],
    'an association half still null' => ['null half'],
    'an association onto a record whose own key is still blank' => ['blank target'],
]);

it('neither claims nor looks up two new siblings on a code of whitespace alone', function (): void {
    $recorder = new RecordingMiddleware();
    $entityManager = IdentifierGuardRig::create($recorder);
    $binder = takenIdentifierEmptyBinder($entityManager);

    $binder->addSheet(takenIdentifierSheet('   '));
    $binder->addSheet(takenIdentifierSheet('   '));

    $recorder->reset();

    expect(new AssociationReader($entityManager)->findTakenIdentifiers($binder))->toBe([])
        ->and($recorder->statements())->toBe([]);
});

it('saves two new siblings whose single string key their own prePersist fills from blank', function (bool $storedOwner): void {
    $entityManager = IdentifierGuardRig::create();
    $binder = $storedOwner ? takenIdentifierEmptyBinder($entityManager) : new Binder();

    foreach (['first', 'second'] as $label) {
        $slate = new Slate();
        $slate->setLabel($label);
        $binder->addSlate($slate);
    }

    expect(new AssociationReader($entityManager)->findTakenIdentifiers($binder))->toBe([]);

    $entityManager->persist($binder);
    $entityManager->flush();

    expect($entityManager->getConnection()->fetchFirstColumn("SELECT label FROM Slate WHERE label IN ('first', 'second') ORDER BY label"))->toBe(['first', 'second']);
})->with([
    'under a stored owner' => [true],
    'under a new owner' => [false],
]);

it('never binds an association half that is a new record with no identifier yet', function (bool $persisted): void {
    $recorder = new RecordingMiddleware();
    $entityManager = IdentifierGuardRig::create($recorder);
    $post = new Post();
    $translation = new PostTranslation();

    if ($persisted) {
        $entityManager->persist($post);
    }

    $translation->setPost($post);
    $translation->setLocale('en');

    $recorder->reset();

    expect(new AssociationReader($entityManager)->findTakenIdentifiers($translation))->toBe([])
        ->and($recorder->statements())->toBe([]);
})->with([
    'never persisted' => [false],
    'persisted, not flushed' => [true],
]);

it('answers nothing for the record itself, whether managed or persisted as new', function (bool $managed): void {
    $recorder = new RecordingMiddleware();
    $entityManager = IdentifierGuardRig::create($recorder);

    if ($managed) {
        $record = IdentifierGuardRig::shortlink($entityManager);
    } else {
        $record = new Shortlink();
        $record->setCode('persisted');
        $entityManager->persist($record);
    }

    $recorder->reset();

    expect(new AssociationReader($entityManager)->findTakenIdentifiers($record))->toBe([])
        ->and($recorder->statements())->toBe([]);
})->with([
    'managed' => [true],
    'persisted as new' => [false],
]);

it('finds the holder of an inheritance hierarchy through its root, whichever subclass holds the key', function (string $hierarchy, bool $managed, string $field, array $statements): void {
    $recorder = new RecordingMiddleware();
    $entityManager = IdentifierGuardRig::create($recorder);

    if ($hierarchy === 'single table') {
        if ($managed) {
            $entityManager->find(Sigil::class, ['realm' => IdentifierGuardRig::SIGIL_REALM, 'mark' => IdentifierGuardRig::SIGIL_MARK]);
        }

        $record = new SigilChild();
        $record->setRealm(IdentifierGuardRig::SIGIL_REALM);
        $record->setMark(IdentifierGuardRig::SIGIL_MARK);
    } else {
        if ($managed) {
            $entityManager->find(Carton::class, IdentifierGuardRig::CARTON);
        }

        $record = new ChilledCarton();
        $record->setCode(IdentifierGuardRig::CARTON);
    }

    $recorder->reset();

    expect(new AssociationReader($entityManager)->findTakenIdentifiers($record))->toBe([$field => 'This value is already used.'])
        ->and($recorder->statements())->toBe($statements);
})->with([
    'single table, the root stored' => ['single table', false, 'realm', ["SELECT t0.realm AS realm_1, t0.mark AS mark_2, t0.label AS label_3, t0.kind FROM Sigil t0 WHERE t0.realm = ? AND t0.mark = ? AND t0.kind IN ('sigil', 'child')"]],
    'single table, the root managed' => ['single table', true, 'realm', []],
    'class table, the root stored' => ['class table', false, 'code', ['SELECT t0.code AS code_2, t0.label AS label_3, t0.kind, t1.celsius AS celsius_4 FROM Carton t0 LEFT JOIN ChilledCarton t1 ON t0.code = t1.code WHERE t0.code = ?']],
    'class table, the root managed' => ['class table', true, 'code', []],
]);

it('refuses the second of two new records of different classes in one hierarchy on one key', function (): void {
    $recorder = new RecordingMiddleware();
    $entityManager = IdentifierGuardRig::create($recorder);
    $rack = new SigilRack();

    $root = new Sigil();
    $root->setRealm('north');
    $root->setMark('twin');
    $root->setLabel('root');

    $rack->addSigil($root);

    $child = new SigilChild();
    $child->setRealm('north');
    $child->setMark('twin');
    $child->setLabel('child');

    $rack->addSigil($child);

    $recorder->reset();

    expect(new AssociationReader($entityManager)->findTakenIdentifiers($rack))->toBe(['sigils[1].realm' => 'This value is already used.']);
});

it('names the field the form lets the user type, never the one pointing back at the record the walk came from', function (string $shape, string $path): void {
    $entityManager = IdentifierGuardRig::create();
    $tag = CompositeTargetRig::tag($entityManager, 1);

    switch ($shape) {
        case 'ribbon member':
            $record = IdentifierGuardRig::binder($entityManager, 1);
            $ribbon = new Ribbon();
            $ribbon->setTag($tag);
            $record->addRibbon($ribbon);

            break;
        case 'pennant member':
            $record = IdentifierGuardRig::binder($entityManager, 1);
            $pennant = new Pennant();
            $pennant->setTag($tag);
            $record->addPennant($pennant);

            break;
        case 'sheet member':
            $record = IdentifierGuardRig::binder($entityManager, 1);
            $record->addSheet(takenIdentifierSheet(IdentifierGuardRig::SHEET));

            break;
        case 'ribbon':
            $record = new Ribbon();
            $record->setBinder(IdentifierGuardRig::binder($entityManager, 1));
            $record->setTag($tag);

            break;
        case 'pennant':
            $record = new Pennant();
            $record->setTag($tag);
            $record->setBinder(IdentifierGuardRig::binder($entityManager, 1));

            break;
        case 'enrolment':
            $record = new Enrolment();
            $record->setPost(CompositeTargetRig::post($entityManager, 1));
            $record->setTag($tag);

            break;
        default:
            $record = new Sheet();
            $record->setBinder(IdentifierGuardRig::binder($entityManager, 1));
            $record->setCode(IdentifierGuardRig::SHEET);
    }

    expect(new AssociationReader($entityManager)->findTakenIdentifiers($record))->toBe([$path => 'This value is already used.']);
})->with([
    'a collection member keyed by its owner and a code: the code' => ['sheet member', 'sheets[1].code'],
    'the same record as the root: the code, not the first field' => ['sheet', 'code'],
    'a collection member keyed by its owner, then a tag: the tag' => ['ribbon member', 'ribbons[1].tag'],
    'a collection member keyed by a tag, then its owner: the tag' => ['pennant member', 'pennants[1].tag'],
    'two associations and no owner to skip, owner-first: the first field' => ['ribbon', 'binder'],
    'two associations and no owner to skip, tag-first: the first field' => ['pennant', 'tag'],
    'a join entity as the root: the first field' => ['enrolment', 'post'],
]);

it('names the association pointing back at the record the walk came from when that one association is the whole key', function (): void {
    $entityManager = IdentifierGuardRig::create();
    $stored = new Plinth();
    $storedCap = new PlinthCap();

    $stored->setCode('P-1');
    $storedCap->setPlinth($stored);
    $storedCap->setLabel('stored cap');

    $stored->setCap($storedCap);

    EntityManagerFactory::seed($entityManager, $stored);

    $plinth = $entityManager->find(Plinth::class, 'P-1');
    assert($plinth instanceof Plinth);

    $cap = new PlinthCap();
    $cap->setPlinth($plinth);
    $cap->setLabel('new cap');

    $plinth->setCap($cap);

    expect(new AssociationReader($entityManager)->findTakenIdentifiers($plinth))->toBe(['cap.plinth' => 'This value is already used.']);
});

it('skips a record whose class the mapping does not know, an anonymous one included', function (object $record): void {
    $recorder = new RecordingMiddleware();
    $entityManager = IdentifierGuardRig::create($recorder);

    $recorder->reset();

    expect(new AssociationReader($entityManager)->findTakenIdentifiers($record))->toBe([])
        ->and($recorder->statements())->toBe([]);
})->with([
    'an anonymous class' => [new class {
        public string $code = 'go';
    }],
    'a transient class' => [new RecordFixture()],
]);

it('guards nothing without an entity manager', function (): void {
    $record = new Shortlink();
    $record->setCode(IdentifierGuardRig::SHORTLINK);

    expect(new AssociationReader()->findTakenIdentifiers($record))->toBe([]);
});

function hiddenRedirectManager(RecordingMiddleware $recorder): EntityManager
{
    $entityManager = EntityManagerFactory::create($recorder);
    $hidden = new Redirect();
    $hidden->setHost('example.com');
    $hidden->setPath('/gone');
    $hidden->setLabel('hidden');
    EntityManagerFactory::seed($entityManager, $hidden);

    $entityManager->getConfiguration()->addFilter('hidden_redirects', HiddenRedirectFilter::class);
    $entityManager->getFilters()->enable('hidden_redirects');

    return $entityManager;
}

function newRedirect(string $host, string $path): Redirect
{
    $redirect = new Redirect();
    $redirect->setHost($host);
    $redirect->setPath($path);
    $redirect->setLabel('again');

    return $redirect;
}

it('finds a taken key a SQL filter hides, restoring the filter after the one lookup', function (): void {
    $recorder = new RecordingMiddleware();
    $entityManager = hiddenRedirectManager($recorder);
    $recorder->reset();

    expect(new AssociationReader($entityManager)->findTakenIdentifiers(newRedirect('example.com', '/gone')))->toBe(['host' => 'This value is already used.'])
        ->and($recorder->statements())->toHaveCount(1)
        ->and($recorder->statements()[0])->not->toContain("label <> 'hidden'")
        ->and($entityManager->getFilters()->isEnabled('hidden_redirects'))->toBeTrue()
        ->and($entityManager->getRepository(Redirect::class)->findBy(['host' => 'example.com']))->toBe([]);
});

it('answers a free key under an enabled filter as free, in one lookup', function (): void {
    $recorder = new RecordingMiddleware();
    $entityManager = hiddenRedirectManager($recorder);
    $recorder->reset();

    expect(new AssociationReader($entityManager)->findTakenIdentifiers(newRedirect('example.com', '/free')))->toBe([])
        ->and($recorder->statements())->toHaveCount(1)
        ->and($entityManager->getFilters()->isEnabled('hidden_redirects'))->toBeTrue();
});
