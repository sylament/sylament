<?php

declare(strict_types=1);

use Sylament\Schemas\Identity\IdentifierConstraint;
use Sylament\Schemas\Identity\RecordKey;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Cipher;
use Sylament\Schemas\Tests\Fixtures\Doctrine\CompositeOwnerRig;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Author;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Beacon;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Crate;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\CrateSeal;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Flagged;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Listing;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\ListingNote;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\ListingSeal;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Pallet;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Placement;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Post;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\PostSeal;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\PostSealNote;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\PostTranslation;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Redirect;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Shortlink;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Strongbox;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\StrongboxSeal;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Tariff;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Ticket;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Vault;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Zone;
use Sylament\Schemas\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Schemas\Tests\Fixtures\Doctrine\IdentityRig;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Sku;
use Symfony\Component\Uid\Uuid;

it('keys a record of every identifier flavour', function (): void {
    $entityManager = IdentityRig::create();

    $redirect = $entityManager->find(Redirect::class, ['host' => 'www.example.com', 'path' => '/blog/2026/bundles all the way down']);
    $translation = $entityManager->find(PostTranslation::class, ['post' => 1, 'locale' => 'en/GB']);
    $seal = $entityManager->find(PostSeal::class, 1);
    $placement = $entityManager->find(Placement::class, ['zone' => Zone::Footer, 'slot' => 2]);
    $post = $entityManager->find(Post::class, 1);

    assert($redirect instanceof Redirect && $translation instanceof PostTranslation && $seal instanceof PostSeal && $placement instanceof Placement && $post instanceof Post);

    expect(RecordKey::of($entityManager, $redirect))->toBe(IdentityRig::HOSTILE_KEY)
        ->and(RecordKey::of($entityManager, $translation))->toBe('1~en=2FGB')
        ->and(RecordKey::of($entityManager, $seal))->toBe('1')
        ->and(RecordKey::of($entityManager, $placement))->toBe('footer~2')
        ->and(RecordKey::of($entityManager, $post))->toBe('1');
});

/*
 * A guid column on a platform with no guid type of its own is a CHAR(36) holding
 * whatever the application put there, so a row whose id is not a UUID is a row the
 * key seam must carry: the mint, the decode and the predicate all have to answer
 * for it, or a legacy table's own rows resolve to nothing.
 */
it('resolves a guid identifier the platform stores as plain text', function (): void {
    $entityManager = IdentityRig::create();
    $beacon = $entityManager->find(Beacon::class, IdentityRig::BEACON_ID);

    assert($beacon instanceof Beacon);

    $key = RecordKey::of($entityManager, $beacon);
    $query = $entityManager->createQueryBuilder()->select('record')->from(Beacon::class, 'record');

    /** @var list<Beacon> $found */
    $found = $query
        ->where((string) IdentifierConstraint::equals($query, 'record', Beacon::class, $key, 'sylament_record_key'))
        ->getQuery()
        ->getResult();

    expect($key)->toBe(IdentityRig::BEACON_ID)
        ->and(RecordKey::toIdentifier($entityManager, Beacon::class, $key))->toBe(['id' => IdentityRig::BEACON_ID])
        ->and($found)->toHaveCount(1)
        ->and($found[0]->getLabel())->toBe('beaconed');
});

it('keys a record whose identifier is a strict value object, alone and behind an association', function (): void {
    $entityManager = IdentityRig::create();

    $listing = $entityManager->find(Listing::class, new Sku('AB-123'));
    $note = $entityManager->find(ListingNote::class, ['listing' => new Sku('AB-123'), 'code' => 'n1']);
    $ticket = $entityManager->find(Ticket::class, ['id' => IdentityRig::TICKET_ID, 'serial' => 7]);

    assert($listing instanceof Listing && $note instanceof ListingNote && $ticket instanceof Ticket);

    expect(RecordKey::of($entityManager, $listing))->toBe('AB-123')
        ->and(RecordKey::of($entityManager, $note))->toBe('AB-123~n1')
        ->and(RecordKey::of($entityManager, $ticket))->toBe(IdentityRig::TICKET_ID . '~7');
});

it('keys a chained derived identity while its target is managed, and after a cold read', function (): void {
    $entityManager = IdentityRig::create();

    $seal = $entityManager->find(PostSeal::class, 1);
    $managed = $entityManager->find(PostSealNote::class, ['seal' => 1, 'code' => 'n1']);

    assert($seal instanceof PostSeal && $managed instanceof PostSealNote);

    $unflushed = new PostSealNote();
    $unflushed->setSeal($seal);
    $unflushed->setCode('n2');

    expect(RecordKey::of($entityManager, $managed))->toBe('1~n1')
        ->and(RecordKey::find($entityManager, $unflushed))->toBe('1~n2');

    $entityManager->clear();

    /** @var list<PostSealNote> $cold */
    $cold = $entityManager->createQueryBuilder()->select('note')->from(PostSealNote::class, 'note')->getQuery()->getResult();

    expect(array_map(static fn (PostSealNote $note): string => RecordKey::of($entityManager, $note), $cold))->toBe(['1~n1']);
});

it('leaves a single string identifier verbatim', function (): void {
    $shortlink = new Shortlink();
    $shortlink->setCode('sy/0.1~x');

    expect(RecordKey::of(EntityManagerFactory::create(), $shortlink))->toBe('sy/0.1~x');
});

it('keys a subclass the mapping does not know by the class the caller declares', function (): void {
    $redirect = new class extends Redirect {};
    $redirect->setHost('example.com');
    $redirect->setPath('/about');

    $entityManager = EntityManagerFactory::create();

    expect(RecordKey::of($entityManager, $redirect, Redirect::class))->toBe('example=2Ecom~=2Fabout')
        ->and(RecordKey::find($entityManager, $redirect, Redirect::class))->toBe('example=2Ecom~=2Fabout');
});

it('keys an unflushed record whose assigned identifier is already complete', function (): void {
    $entityManager = IdentityRig::create();
    $translation = new PostTranslation();
    $translation->setPost($entityManager->getReference(Post::class, 1));
    $translation->setLocale('sk');

    expect(RecordKey::find($entityManager, $translation))->toBe('1~sk');
});

it('finds no key while a component is missing or still blank', function (): void {
    $entityManager = IdentityRig::create();

    $unattributed = new PostTranslation();
    $unattributed->setLocale('sk');

    $unsavedPost = new PostTranslation();
    $unsavedPost->setPost(new Post());
    $unsavedPost->setLocale('sk');

    $blank = new Redirect();
    $blank->setHost('example.com');

    expect(RecordKey::find($entityManager, $unattributed))->toBeNull()
        ->and(RecordKey::find($entityManager, $unsavedPost))->toBeNull()
        ->and(RecordKey::find($entityManager, $blank))->toBeNull()
        ->and(RecordKey::find($entityManager, new Author()))->toBeNull()
        ->and(RecordKey::find($entityManager, new PostSeal()))->toBeNull();
});

it('refuses to key a record whose identifier is missing', function (): void {
    expect(fn (): string => RecordKey::of(EntityManagerFactory::create(), new PostTranslation()))
        ->toThrow(LogicException::class, 'has none set');
});

it('refuses to key a record whose identifier is blank', function (): void {
    $redirect = new Redirect();
    $redirect->setHost('example.com');

    expect(fn (): string => RecordKey::of(EntityManagerFactory::create(), $redirect))
        ->toThrow(LogicException::class, 'is not a printable value');
});

it('mints from raw values the key the record mints', function (): void {
    $entityManager = EntityManagerFactory::create();

    expect(RecordKey::fromValues($entityManager, PostTranslation::class, [1, 'en/GB']))->toBe('1~en=2FGB')
        ->and(RecordKey::fromValues($entityManager, PostTranslation::class, ['1', 'en/GB']))->toBe('1~en=2FGB')
        ->and(RecordKey::fromValues($entityManager, Placement::class, [Zone::Footer, 2]))->toBe('footer~2')
        ->and(RecordKey::fromValues($entityManager, Post::class, [7]))->toBe('7');
});

it('refuses raw values that do not match the identifier', function (): void {
    $entityManager = EntityManagerFactory::create();

    expect(fn (): string => RecordKey::fromValues($entityManager, PostTranslation::class, [1]))
        ->toThrow(LogicException::class, 'takes 2 identifier values, 1 given')
        ->and(fn (): string => RecordKey::fromValues($entityManager, PostTranslation::class, [1, ['en']]))
        ->toThrow(LogicException::class, 'is not a printable value');
});

it('decodes a key into the identifier map find() takes', function (): void {
    $entityManager = IdentityRig::create();

    $identifier = RecordKey::toIdentifier($entityManager, PostTranslation::class, '1~en=2FGB');

    expect($identifier)->toBe(['post' => 1, 'locale' => 'en/GB'])
        ->and($entityManager->find(PostTranslation::class, $identifier ?? [])?->getTitle())->toBe('en/GB')
        ->and(RecordKey::toIdentifier($entityManager, Redirect::class, IdentityRig::HOSTILE_KEY))
        ->toBe(['host' => 'www.example.com', 'path' => '/blog/2026/bundles all the way down'])
        ->and(RecordKey::toIdentifier($entityManager, Post::class, '1'))->toBe(['id' => 1]);
});

it('hands find() a strict value object rather than the string a strict type would refuse', function (): void {
    $entityManager = IdentityRig::create();

    $single = RecordKey::toIdentifier($entityManager, Listing::class, 'AB-123');
    $behindAnAssociation = RecordKey::toIdentifier($entityManager, ListingNote::class, 'AB-123~n1');

    expect($single)->toEqual(['sku' => new Sku('AB-123')])
        ->and($entityManager->find(Listing::class, $single ?? [])?->getTitle())->toBe('Listed')
        ->and($entityManager->find(ListingNote::class, $behindAnAssociation ?? [])?->getBody())->toBe('noted');
});

it('answers a map exactly when the same key narrows a query', function (string $class, string $key): void {
    $entityManager = IdentityRig::create();
    $query = $entityManager->createQueryBuilder()->select('record')->from($class, 'record');

    expect(RecordKey::toIdentifier($entityManager, $class, $key) === null)
        ->toBe(IdentifierConstraint::equals($query, 'record', $class, $key, 'key') === null);
})->with([
    [Post::class, '1'],
    [Post::class, "1\n"],
    [Post::class, '01'],
    [Post::class, '99999999999999999999'],
    [PostTranslation::class, '1~en=2FGB'],
    [PostTranslation::class, '1~en=2fGB'],
    [Listing::class, 'AB-123'],
    [Listing::class, 'ab-123'],
    [Listing::class, 'not-a-sku'],
    [ListingNote::class, 'AB-123~n1'],
    [ListingNote::class, 'zz~n1'],
    [Ticket::class, IdentityRig::TICKET_ID . '~7'],
    [Ticket::class, 'not-a-guid~7'],
    [Ticket::class, IdentityRig::TICKET_ID . '~abc'],
]);

it('answers null rather than throwing for a key the class never mints', function (string $class, string $key): void {
    expect(RecordKey::toIdentifier(EntityManagerFactory::create(), $class, $key))->toBeNull();
})->with([
    'the wrong arity' => [PostTranslation::class, '1'],
    'a non-integer association component' => [PostTranslation::class, 'one~en'],
    'a non-canonical escape' => [PostTranslation::class, '1~en=2fGB'],
    'a non-canonical single integer' => [Post::class, '05'],
    'a single integer with a trailing newline' => [Post::class, "1\n"],
    'a value a strict type refuses' => [Listing::class, 'not-a-sku'],
    'an injection attempt' => [Post::class, '1 OR 1=1'],
    'blank' => [Post::class, ''],
]);

it("reads the values a key prints, before they are printed, an identifier association reduced to its target's", function (string $flavour, array $values, string $key): void {
    $entityManager = CompositeOwnerRig::create();

    $record = match ($flavour) {
        'raw-bytes uuid' => CompositeOwnerRig::findCrate($entityManager, 'a1b2c3d4-0000-4000-8000-000000000001'),
        'raw-bytes half and string half' => CompositeOwnerRig::findPallet($entityManager, 'b1b2c3d4-0000-4000-8000-000000000001', 'north'),
        'derived identity' => CompositeOwnerRig::findTranslation($entityManager, 1, 'en'),
        'single derived identity onto a converting identifier' => CompositeOwnerRig::findListingSeal($entityManager, 'AB-123'),
        'single derived identity onto a raw-bytes uuid' => CompositeOwnerRig::findCrateSeal($entityManager, 'a1b2c3d4-0000-4000-8000-000000000001'),
        default => CompositeOwnerRig::findStrongboxSeal($entityManager, 'omega'),
    };

    expect(RecordKey::values($entityManager, $record))->toEqual($values)
        ->and(RecordKey::of($entityManager, $record))->toBe($key);
})->with([
    'a raw-bytes uuid' => ['raw-bytes uuid', [Uuid::fromString('a1b2c3d4-0000-4000-8000-000000000001')], 'a1b2c3d4-0000-4000-8000-000000000001'],
    'a raw-bytes half and a string half' => ['raw-bytes half and string half', [Uuid::fromString('b1b2c3d4-0000-4000-8000-000000000001'), 'north'], 'b1b2c3d4-0000-4000-8000-000000000001~north'],
    'a derived identity' => ['derived identity', [1, 'en'], '1~en'],
    'a single derived identity onto a converting identifier' => ['single derived identity onto a converting identifier', [new Sku('AB-123')], 'AB-123'],
    'a single derived identity onto a raw-bytes uuid' => ['single derived identity onto a raw-bytes uuid', [Uuid::fromString('a1b2c3d4-0000-4000-8000-000000000001')], 'a1b2c3d4-0000-4000-8000-000000000001'],
    'a single derived identity onto a doubly-strict identifier' => ['single derived identity onto a doubly-strict identifier', [new Cipher('omega')], 'omega'],
]);

it('reads no values while a component is missing at any level', function (): void {
    $entityManager = EntityManagerFactory::create();

    $unattributed = new PostTranslation();
    $unattributed->setLocale('sk');

    $unsealed = new ListingSeal();

    $unkeyedTarget = new ListingSeal();
    $unkeyedTarget->setListing(new Listing());

    expect(RecordKey::values($entityManager, $unattributed))->toBeNull()
        ->and(RecordKey::values($entityManager, $unsealed))->toBeNull()
        ->and(RecordKey::values($entityManager, $unkeyedTarget))->toBeNull()
        ->and(RecordKey::values($entityManager, new Author()))->toBeNull();
});

it('reads the values of a subclass the mapping does not know by the class the caller declares', function (): void {
    $redirect = new class extends Redirect {};
    $redirect->setHost('example.com');
    $redirect->setPath('/about');

    expect(RecordKey::values(EntityManagerFactory::create(), $redirect, Redirect::class))->toBe(['example.com', '/about']);
});

/*
 * A key is minted from the value the row would read back: each component goes
 * through its field's DBAL type to the database form and back before it is
 * printed. A loaded record already holds what its row read back, so none of
 * these may move.
 */
it('mints a loaded record the key it already held, whatever its identifier type converts', function (string $flavour, string $key): void {
    $entityManager = IdentityRig::create();

    $record = match ($flavour) {
        'sku' => $entityManager->find(Listing::class, new Sku('AB-123')),
        'sku behind an association' => $entityManager->find(ListingNote::class, ['listing' => new Sku('AB-123'), 'code' => 'n1']),
        'guid beside a bigint' => $entityManager->find(Ticket::class, ['id' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11', 'serial' => 7]),
        default => $entityManager->find(Beacon::class, 'legacy-42'),
    };

    assert($record !== null);

    expect(RecordKey::of($entityManager, $record))->toBe($key)
        ->and(RecordKey::find($entityManager, $record))->toBe($key);
})->with([
    'a sku' => ['sku', 'AB-123'],
    'a sku behind an association' => ['sku behind an association', 'AB-123~n1'],
    'a guid beside a bigint' => ['guid beside a bigint', 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11~7'],
    'a guid the platform stores as plain text' => ['guid stored as plain text', 'legacy-42'],
]);

it('mints a loaded record the key it already held behind a raw-bytes uuid or a cipher', function (string $flavour, string $key): void {
    $entityManager = CompositeOwnerRig::create();

    $record = match ($flavour) {
        'raw-bytes uuid' => $entityManager->find(Crate::class, Uuid::fromString('a1b2c3d4-0000-4000-8000-000000000001')),
        'raw-bytes uuid beside a string' => $entityManager->find(Pallet::class, ['batch' => Uuid::fromString('b1b2c3d4-0000-4000-8000-000000000001'), 'lane' => 'north']),
        'single derived identity onto a raw-bytes uuid' => $entityManager->find(CrateSeal::class, ['crate' => Uuid::fromString('a1b2c3d4-0000-4000-8000-000000000001')]),
        'cipher' => $entityManager->find(Strongbox::class, new Cipher('omega')),
        'cipher beside a string' => $entityManager->find(Vault::class, ['cipher' => new Cipher('zeta'), 'zone' => 'north']),
        'single derived identity onto a cipher' => $entityManager->find(StrongboxSeal::class, ['strongbox' => new Cipher('omega')]),
        'single derived identity onto a sku' => $entityManager->find(ListingSeal::class, ['listing' => new Sku('AB-123')]),
        default => CompositeOwnerRig::findChain($entityManager, 1, 1),
    };

    assert($record !== null);

    expect(RecordKey::of($entityManager, $record))->toBe($key)
        ->and(RecordKey::find($entityManager, $record))->toBe($key);
})->with([
    'a raw-bytes uuid' => ['raw-bytes uuid', 'a1b2c3d4-0000-4000-8000-000000000001'],
    'a raw-bytes uuid beside a string' => ['raw-bytes uuid beside a string', 'b1b2c3d4-0000-4000-8000-000000000001~north'],
    'a single derived identity onto a raw-bytes uuid' => ['single derived identity onto a raw-bytes uuid', 'a1b2c3d4-0000-4000-8000-000000000001'],
    'a cipher' => ['cipher', 'omega'],
    'a cipher beside a string' => ['cipher beside a string', 'zeta~north'],
    'a single derived identity onto a cipher' => ['single derived identity onto a cipher', 'omega'],
    'a single derived identity onto a sku' => ['single derived identity onto a sku', 'AB-123'],
    'a chained derived identity' => ['chained derived identity', '1~1'],
]);

it('mints a loaded record the key it already held under a decimal or a boolean identifier', function (): void {
    $entityManager = EntityManagerFactory::create();

    $tariff = new Tariff();
    $tariff->setRate('12.25');
    $tariff->setLabel('quarter');

    $off = new Flagged();
    $off->setFlag(false);
    $off->setTitle('off');

    $on = new Flagged();
    $on->setFlag(true);
    $on->setTitle('on');

    EntityManagerFactory::seed($entityManager, $tariff, $off, $on);

    $tariff = $entityManager->find(Tariff::class, '12.25');
    $off = $entityManager->find(Flagged::class, false);
    $on = $entityManager->find(Flagged::class, true);

    assert($tariff instanceof Tariff && $off instanceof Flagged && $on instanceof Flagged);

    expect(RecordKey::of($entityManager, $tariff))->toBe('12.25')
        ->and(RecordKey::find($entityManager, $tariff))->toBe('12.25')
        ->and(RecordKey::of($entityManager, $off))->toBe('0')
        ->and(RecordKey::find($entityManager, $off))->toBe('0')
        ->and(RecordKey::of($entityManager, $on))->toBe('1')
        ->and(RecordKey::find($entityManager, $on))->toBe('1');
});

it('mints a new record the key its row will read back, through of() and find() alike', function (int|string $serial): void {
    $ticket = new Ticket();
    $ticket->setId('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11');
    $ticket->setSerial($serial);

    $entityManager = EntityManagerFactory::create();

    expect(RecordKey::of($entityManager, $ticket))->toBe('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11~7')
        ->and(RecordKey::find($entityManager, $ticket))->toBe('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11~7');
})->with([
    'an integer' => [7],
    'a numeric string' => ['7'],
    'leading zeros' => ['0007'],
    'a plus sign' => ['+7'],
]);

it('mints a record it has just flushed the key its row mints once loaded', function (): void {
    $entityManager = EntityManagerFactory::create();

    $ticket = new Ticket();
    $ticket->setId('b0eebc99-9c0b-4ef8-bb6d-6bb9bd380a12');
    $ticket->setSerial('0012');
    $ticket->setSubject('flushed');

    $entityManager->persist($ticket);
    $entityManager->flush();

    $minted = RecordKey::of($entityManager, $ticket);

    expect($minted)->toBe('b0eebc99-9c0b-4ef8-bb6d-6bb9bd380a12~12')
        ->and(RecordKey::find($entityManager, $ticket))->toBe('b0eebc99-9c0b-4ef8-bb6d-6bb9bd380a12~12')
        ->and(RecordKey::toIdentifier($entityManager, Ticket::class, $minted))->toBe(['id' => 'b0eebc99-9c0b-4ef8-bb6d-6bb9bd380a12', 'serial' => 12]);

    $entityManager->clear();

    $loaded = $entityManager->find(Ticket::class, ['id' => 'b0eebc99-9c0b-4ef8-bb6d-6bb9bd380a12', 'serial' => 12]);

    assert($loaded instanceof Ticket);

    expect($loaded->getSubject())->toBe('flushed')
        ->and(RecordKey::of($entityManager, $loaded))->toBe('b0eebc99-9c0b-4ef8-bb6d-6bb9bd380a12~12');
});

it('mints nothing through the type from raw values, and decodes no spelling the type would rewrite', function (): void {
    $entityManager = EntityManagerFactory::create();

    expect(RecordKey::fromValues($entityManager, Ticket::class, ['a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11', '0007']))
        ->toBe('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11~0007')
        ->and(RecordKey::toIdentifier($entityManager, Ticket::class, 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11~0007'))->toBeNull()
        ->and(RecordKey::toIdentifier($entityManager, Ticket::class, 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11~7'))
        ->toBe(['id' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11', 'serial' => 7]);
});

it('finds no key for a blank component before its type converts it', function (): void {
    $ticket = new Ticket();
    $ticket->setId('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11');
    $ticket->setSerial('');

    expect(RecordKey::find(EntityManagerFactory::create(), $ticket))->toBeNull();
});

it('refuses a blank component through of() exactly as before the conversion', function (string $serial): void {
    $ticket = new Ticket();
    $ticket->setId('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11');
    $ticket->setSerial($serial);

    expect(fn (): string => RecordKey::of(EntityManagerFactory::create(), $ticket))
        ->toThrow(LogicException::class, 'The identifier of [Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Ticket] is not a printable value.');
})->with([
    'an empty string' => [''],
    'a space' => [' '],
]);
