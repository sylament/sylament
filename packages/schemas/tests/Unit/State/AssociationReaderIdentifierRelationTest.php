<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\State;

use LogicException;
use Sylament\Schemas\Identity\RecordKey;
use Sylament\Schemas\State\AssociationReader;
use Sylament\Schemas\State\Relationship;
use Sylament\Schemas\Tests\Fixtures\Doctrine\CompositeTargetRig;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Comment;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Marker;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\PostTranslation;

it('refuses to offer candidates for a one-to-many whose mapped-by field is part of the target identifier, and offers them for an ordinary one', function (): void {
    $entityManager = CompositeTargetRig::create();
    $reader = new AssociationReader($entityManager);
    $post = CompositeTargetRig::post($entityManager, 1);

    try {
        $reader->createRelationshipCandidateQuery(new Relationship($post, 'translations'));
        $message = null;
    } catch (LogicException $logicException) {
        $message = $logicException->getMessage();
    }

    expect($message)->toContain(PostTranslation::class . '::$post] is part of the identifier of')
        ->toContain('pointing it at another owner would move its primary key')
        ->and($reader->createRelationshipCandidateQuery(new Relationship($post, 'comments'))->getDQL())
        ->toEndWith('WHERE record.post IS NULL OR record.post <> :sylament_relationship_owner');
});

it('still lists the records of a one-to-many it refuses to offer candidates for', function (): void {
    $entityManager = CompositeTargetRig::create();
    $reader = new AssociationReader($entityManager);
    $post = CompositeTargetRig::post($entityManager, 1);

    $related = $reader->createRelationshipQuery(new Relationship($post, 'translations'))->getQuery()->getResult();

    expect(array_map(static fn (object $record): string => RecordKey::of($entityManager, $record), $related))
        ->toBe([CompositeTargetRig::TRANSLATION_EN, CompositeTargetRig::TRANSLATION_DE]);
});

it('answers whether a relationship accepts an existing record at all', function (string $relationship, bool $accepted): void {
    $entityManager = CompositeTargetRig::create();
    $reader = new AssociationReader($entityManager);
    $post = CompositeTargetRig::post($entityManager, 1);

    $assert = fn (): null => $reader->assertRelationshipAcceptsAssociate(new Relationship($post, $relationship)) ?? null;

    $accepted
        ? expect($assert())->toBeNull()
        : expect($assert)->toThrow(LogicException::class, 'would move its primary key');
})->with([
    'the mapped-by field is half the target identifier' => ['translations', false],
    'an ordinary one-to-many' => ['comments', true],
    'a many-to-many onto the same target' => ['linkedTranslations', true],
]);

it("attaches a new child and this owner's own child, and refuses another owner's child without moving anything", function (): void {
    $entityManager = CompositeTargetRig::create();
    $reader = new AssociationReader($entityManager);
    $post = CompositeTargetRig::post($entityManager, 1);

    $fresh = new PostTranslation();
    $fresh->setLocale('cs');
    $fresh->setTitle('cs');

    $reader->attach(new Relationship($post, 'translations'), $fresh);
    $entityManager->persist($fresh);
    $entityManager->flush();

    $picked = new PostTranslation();
    $picked->setPost(CompositeTargetRig::post($entityManager, 2));
    $picked->setLocale('pl');
    $picked->setTitle('pl');

    $reader->attach(new Relationship($post, 'translations'), $picked);
    $entityManager->persist($picked);
    $entityManager->flush();

    $own = CompositeTargetRig::findTranslation($entityManager, 1, 'en');
    $reader->attach(new Relationship($post, 'translations'), $own);

    $foreign = CompositeTargetRig::findTranslation($entityManager, 2, 'fr');

    expect(RecordKey::of($entityManager, $fresh))->toBe('1~cs')
        ->and(RecordKey::of($entityManager, $picked))->toBe('1~pl')
        ->and(RecordKey::of($entityManager, $own))->toBe(CompositeTargetRig::TRANSLATION_EN)
        ->and(fn () => $reader->attach(new Relationship($post, 'translations'), $foreign))
        ->toThrow(LogicException::class, 'would move its primary key')
        ->and(RecordKey::of($entityManager, $foreign))->toBe(CompositeTargetRig::TRANSLATION_FR);

    $entityManager->flush();
    $entityManager->clear();

    expect($entityManager->find(PostTranslation::class, ['post' => 2, 'locale' => 'fr']))->toBeInstanceOf(PostTranslation::class)
        ->and($entityManager->find(PostTranslation::class, ['post' => 1, 'locale' => 'fr']))->toBeNull()
        ->and($entityManager->find(PostTranslation::class, ['post' => 1, 'locale' => 'pl']))->toBeInstanceOf(PostTranslation::class)
        ->and($entityManager->find(PostTranslation::class, ['post' => 2, 'locale' => 'pl']))->toBeNull();
});

it('still re-points an ordinary child at another owner', function (): void {
    $entityManager = CompositeTargetRig::create();
    $reader = new AssociationReader($entityManager);

    $comment = new Comment();
    $comment->setBody('moved');
    $comment->setPost(CompositeTargetRig::post($entityManager, 2));

    $entityManager->persist($comment);
    $entityManager->flush();

    $reader->attach(new Relationship(CompositeTargetRig::post($entityManager, 1), 'comments'), $comment);
    $entityManager->flush();

    expect($comment->getPost()?->getId())->toBe(1);
});

it('refuses to dissociate a record whose mapped-by field is part of its identifier, before it reads the join column', function (): void {
    $entityManager = CompositeTargetRig::create();
    $reader = new AssociationReader($entityManager);
    $post = CompositeTargetRig::post($entityManager, 1);

    try {
        $reader->assertRelationshipAcceptsDetach(new Relationship($post, 'translations'));
        $message = null;
    } catch (LogicException $logicException) {
        $message = $logicException->getMessage();
    }

    expect($message)->toContain("nulling it would erase half the record's identity; use DeleteAction.")
        ->not->toContain('make the column nullable')
        ->and(fn () => $reader->detach(new Relationship($post, 'translations'), CompositeTargetRig::findTranslation($entityManager, 1, 'en')))
        ->toThrow(LogicException::class, "erase half the record's identity");
});

it('keeps dissociating a one-to-many whose mapped-by field is nothing of the target identity', function (): void {
    $entityManager = CompositeTargetRig::create();
    $reader = new AssociationReader($entityManager);
    $seal = CompositeTargetRig::findSeal($entityManager, 1);

    expect($reader->assertRelationshipAcceptsDetach(new Relationship($seal, 'markers')) ?? null)->toBeNull();
});

it("writes a composite owner's own collections, which only its query builders refuse", function (): void {
    $entityManager = CompositeTargetRig::create();
    $reader = new AssociationReader($entityManager);
    $redirect = CompositeTargetRig::findRedirect($entityManager, 'other.com', '/c');

    $reader->attach(new Relationship($redirect, 'posts'), CompositeTargetRig::post($entityManager, 2));
    $entityManager->flush();
    $entityManager->clear();

    $redirect = CompositeTargetRig::findRedirect($entityManager, 'other.com', '/c');

    expect(array_map(static fn (object $post): ?int => $post->getId(), $redirect->getPosts()->toArray()))->toBe([2]);

    $reader->detach(new Relationship($redirect, 'posts'), CompositeTargetRig::post($entityManager, 2));
    $entityManager->flush();
    $entityManager->clear();

    expect(CompositeTargetRig::findRedirect($entityManager, 'other.com', '/c')->getPosts()->toArray())->toBe([]);
});

it('points a one-to-many child at a composite owner and nulls it again', function (): void {
    $entityManager = CompositeTargetRig::create();
    $reader = new AssociationReader($entityManager);

    $marker = new Marker();
    $marker->setLabel('translated');

    $entityManager->persist($marker);
    $reader->attach(new Relationship(CompositeTargetRig::findTranslation($entityManager, 1, 'en'), 'markers'), $marker);
    $entityManager->flush();
    $entityManager->clear();

    $reloaded = $entityManager->find(Marker::class, $marker->getId());
    assert($reloaded instanceof Marker);
    $translation = $reloaded->getTranslation();
    assert($translation instanceof PostTranslation);

    expect(RecordKey::of($entityManager, $translation))->toBe(CompositeTargetRig::TRANSLATION_EN);

    $reader->detach(new Relationship($translation, 'markers'), $reloaded);
    $entityManager->flush();
    $entityManager->clear();

    $reloaded = $entityManager->find(Marker::class, $marker->getId());
    assert($reloaded instanceof Marker);

    expect($reloaded->getTranslation())->toBeNull();
});
