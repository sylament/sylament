<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\State;

use LogicException;
use Sylament\Schemas\State\AssociationReader;
use Sylament\Schemas\State\Relationship;
use Sylament\Schemas\Tests\Fixtures\Doctrine\CompositeTargetRig;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Enrolment;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Flagged;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Listened;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Listing;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\ListingNote;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Post;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\PostSeal;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\PostTranslation;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Redirect;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Shortlink;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Stamped;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Tag;
use Sylament\Schemas\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Schemas\Tests\Fixtures\Doctrine\RecordingMiddleware;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Sku;

it('mints the same key for a record of every identifier width, and finds it again', function (string $flavour, string $key): void {
    $entityManager = CompositeTargetRig::create();
    $reader = new AssociationReader($entityManager);

    $record = match ($flavour) {
        'scalar pair' => CompositeTargetRig::findRedirect($entityManager, 'example.com', '/a'),
        'derived identity' => CompositeTargetRig::findTranslation($entityManager, 1, 'en'),
        'single derived identity' => CompositeTargetRig::findSeal($entityManager, 1),
        default => CompositeTargetRig::findEnrolment($entityManager, 1, 1),
    };

    expect($reader->getRecordKey($record))->toBe($key)
        ->and($reader->findRecordKey($record))->toBe($key);
})->with([
    'scalar pair' => ['scalar pair', CompositeTargetRig::REDIRECT_ONE],
    'derived identity' => ['derived identity', CompositeTargetRig::TRANSLATION_EN],
    'single derived identity' => ['single derived identity', CompositeTargetRig::SEAL_ONE],
    'join entity' => ['join entity', CompositeTargetRig::ENROLMENT_ONE],
]);

it('escapes a key component that carries the separator', function (): void {
    $entityManager = EntityManagerFactory::create();
    $reader = new AssociationReader($entityManager);

    $redirect = new Redirect();
    $redirect->setHost('a~b');
    $redirect->setPath('c');
    $redirect->setLabel('tilde');

    expect($reader->getRecordKey($redirect))->toBe('a=7Eb~c');
});

it('finds no key while an identifier half is unset', function (): void {
    $entityManager = EntityManagerFactory::create();
    $reader = new AssociationReader($entityManager);

    $translation = new PostTranslation();
    $translation->setLocale('en');

    expect($reader->findRecordKey($translation))->toBeNull();
});

it('keys a false boolean identifier as a printed zero', function (): void {
    $entityManager = EntityManagerFactory::create();
    $reader = new AssociationReader($entityManager);

    $flagged = new Flagged();
    $flagged->setTitle('off');

    EntityManagerFactory::seed($entityManager, $flagged);

    expect($reader->getRecordKey($flagged))->toBe('0')
        ->and($reader->getReference(Flagged::class, '0'))->toBeInstanceOf(Flagged::class);
});

it('finds no key for a blank assigned identifier while still refusing to mint one', function (): void {
    $entityManager = EntityManagerFactory::create();
    $reader = new AssociationReader($entityManager);

    $shortlink = new Shortlink();
    $shortlink->setTarget('https://example.com');

    expect($reader->findRecordKey($shortlink))->toBeNull()
        ->and(fn (): string => $reader->getRecordKey($shortlink))
        ->toThrow(LogicException::class, 'is not a printable value');
});

it('resolves a record of every identifier flavour by key against a cold identity map', function (string $flavour, string $class, string $key, int $statements): void {
    $recorder = new RecordingMiddleware();
    $entityManager = CompositeTargetRig::create($recorder);
    $reader = new AssociationReader($entityManager);

    $entityManager->clear();
    $recorder->reset();

    $record = $reader->getReference($class, $key);

    expect($record)->toBeInstanceOf($class)
        ->and($reader->getRecordKey($record))->toBe($key)
        ->and($recorder->statements())->toHaveCount($statements);
})->with([
    'single identifier' => ['single identifier', Tag::class, '1', 0],
    'scalar pair' => ['scalar pair', Redirect::class, CompositeTargetRig::REDIRECT_ONE, 0],
    'derived identity' => ['derived identity', PostTranslation::class, CompositeTargetRig::TRANSLATION_EN, 1],
    'single derived identity' => ['single derived identity', PostSeal::class, CompositeTargetRig::SEAL_ONE, 1],
    'join entity' => ['join entity', Enrolment::class, CompositeTargetRig::ENROLMENT_ONE, 1],
]);

it('refuses a key no record of the class could mint, without executing a statement', function (string $class, string $key): void {
    $recorder = new RecordingMiddleware();
    $entityManager = CompositeTargetRig::create($recorder);
    $reader = new AssociationReader($entityManager);

    $entityManager->clear();
    $recorder->reset();

    expect(fn (): object => $reader->getReference($class, $key))
        ->toThrow(LogicException::class, 'so it cannot be referenced')
        ->and($recorder->statements())->toBe([]);
})->with([
    'a leading zero' => [Tag::class, '05'],
    'a leading space' => [Tag::class, ' 5'],
    'a numeric prefix' => [Tag::class, '5abc'],
    'a separator on a single identifier' => [Tag::class, '1~2'],
    'too few components' => [Redirect::class, 'example=2Ecom'],
    'an unescaped component' => [Redirect::class, 'example.com~/a'],
    'a blank component' => [PostTranslation::class, '1~'],
    'a lowercase escape carrying a hex letter' => [PostTranslation::class, '1~zh=2dhans'],
    'a scalar key for a join entity' => [Enrolment::class, '1'],
]);

it('hands back the very instance a relationship query loaded, whichever read came first', function (): void {
    $entityManager = CompositeTargetRig::create();
    $reader = new AssociationReader($entityManager);

    $entityManager->clear();
    $loadedFirst = $reader->createRelationshipQuery(new Relationship(CompositeTargetRig::post($entityManager, 1), 'linkedTranslations'))->getQuery()->getResult();
    $referencedAfter = $reader->getReference(PostTranslation::class, CompositeTargetRig::TRANSLATION_EN);

    $entityManager->clear();
    $referencedFirst = $reader->getReference(PostTranslation::class, CompositeTargetRig::TRANSLATION_EN);
    $loadedAfter = $reader->createRelationshipQuery(new Relationship(CompositeTargetRig::post($entityManager, 1), 'linkedTranslations'))->getQuery()->getResult();

    expect($referencedAfter)->toBe($loadedFirst[0])
        ->and($loadedAfter[0])->toBe($referencedFirst);
});

it('reports whether a record is missing an identifier component the application has to assign', function (string $state, bool $incomplete): void {
    $entityManager = EntityManagerFactory::create();
    $reader = new AssociationReader($entityManager);

    $pendingOwner = new Post();
    $pendingOwner->setTitle('Alpha');

    $record = match ($state) {
        'a generated identifier of its own' => $pendingOwner,
        'a derived identity under an owner whose generated id is pending' => (static function () use ($pendingOwner): PostTranslation {
            $translation = new PostTranslation();
            $translation->setPost($pendingOwner);
            $translation->setLocale('de');

            return $translation;
        })(),
        'a blank assigned component' => (static function () use ($pendingOwner): PostTranslation {
            $translation = new PostTranslation();
            $translation->setPost($pendingOwner);
            $translation->setLocale('');

            return $translation;
        })(),
        'no identifier association at all' => (static function (): PostTranslation {
            $translation = new PostTranslation();
            $translation->setLocale('de');

            return $translation;
        })(),
        'a composite half a prePersist callback of the class assigns' => (static function (): Stamped {
            $stamped = new Stamped();
            $stamped->setScope('releases');

            return $stamped;
        })(),
        'a composite half a mapped entity listener assigns' => (static function (): Listened {
            $listened = new Listened();
            $listened->setScope('releases');

            return $listened;
        })(),
        'an identifier association onto an incomplete assigned record' => (static function (): ListingNote {
            $note = new ListingNote();
            $note->setListing(new Listing());
            $note->setCode('n1');

            return $note;
        })(),
        default => (static function (): ListingNote {
            $listing = new Listing();
            $listing->setSku(new Sku('AB-123'));

            $note = new ListingNote();
            $note->setListing($listing);
            $note->setCode('n1');

            return $note;
        })(),
    };

    expect($reader->hasIncompleteAssignedIdentifier($record))->toBe($incomplete);
})->with([
    'a generated identifier of its own' => ['a generated identifier of its own', false],
    'a derived identity under an owner whose generated id is pending' => ['a derived identity under an owner whose generated id is pending', false],
    'a blank assigned component' => ['a blank assigned component', true],
    'no identifier association at all' => ['no identifier association at all', true],
    'an identifier association onto an incomplete assigned record' => ['an identifier association onto an incomplete assigned record', true],
    'an identifier association onto a complete assigned record' => ['an identifier association onto a complete assigned record', false],
    'a composite half a prePersist callback of the class assigns' => ['a composite half a prePersist callback of the class assigns', false],
    'a composite half a mapped entity listener assigns' => ['a composite half a mapped entity listener assigns', false],
]);

it('leaves a plain single scalar identifier to the application, blank or not', function (): void {
    $entityManager = EntityManagerFactory::create();
    $reader = new AssociationReader($entityManager);

    $shortlink = new Shortlink();
    $shortlink->setTarget('https://example.com');

    expect($reader->hasIncompleteAssignedIdentifier($shortlink))->toBeFalse()
        ->and($reader->findRecordKey($shortlink))->toBeNull();
});
