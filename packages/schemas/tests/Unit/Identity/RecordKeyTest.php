<?php

declare(strict_types=1);

use Sylament\Schemas\Identity\IdentifierConstraint;
use Sylament\Schemas\Identity\RecordKey;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Author;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Beacon;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Listing;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\ListingNote;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Placement;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Post;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\PostSeal;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\PostSealNote;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\PostTranslation;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Redirect;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Shortlink;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Ticket;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Zone;
use Sylament\Schemas\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Schemas\Tests\Fixtures\Doctrine\IdentityRig;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Sku;

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

/*
 * Doctrine's own IdentifierFlattener reads a managed target's identifier by join-column
 * name and answers a blank for it, so a chained identity keyed through it breaks exactly
 * when the target is managed — which a row read from the database always is.
 */
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
