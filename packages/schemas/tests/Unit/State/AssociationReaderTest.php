<?php

declare(strict_types=1);

use Sylament\Schemas\State\AssociationReader;
use Sylament\Schemas\State\AssociationTarget;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Author;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\CompositePair;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Post;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\PostSection;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Profile;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Tag;
use Sylament\Schemas\Tests\Fixtures\Doctrine\EntityManagerFactory;

it('describes a to-one association', function (): void {
    $reader = new AssociationReader(EntityManagerFactory::create());

    $target = $reader->describe(Post::class, 'author');

    expect($target)->toBeInstanceOf(AssociationTarget::class)
        ->and($target?->targetClass)->toBe(Author::class)
        ->and($target?->isToMany)->toBeFalse()
        ->and($target?->isOwningSide)->toBeTrue()
        ->and($target?->isOrphanRemoval)->toBeFalse();
});

it('describes a to-many association with orphan removal', function (): void {
    $reader = new AssociationReader(EntityManagerFactory::create());

    $target = $reader->describe(Post::class, 'sections');

    expect($target?->targetClass)->toBe(PostSection::class)
        ->and($target?->isToMany)->toBeTrue()
        ->and($target?->isOwningSide)->toBeFalse()
        ->and($target?->isOrphanRemoval)->toBeTrue();
});

it('describes the many-to-many owning side', function (): void {
    $reader = new AssociationReader(EntityManagerFactory::create());

    $target = $reader->describe(Post::class, 'tags');

    expect($target?->targetClass)->toBe(Tag::class)
        ->and($target?->isToMany)->toBeTrue()
        ->and($target?->isOwningSide)->toBeTrue()
        ->and($target?->isOrphanRemoval)->toBeFalse();
});

it('reports the inverse side of a one-to-one', function (): void {
    $reader = new AssociationReader(EntityManagerFactory::create());

    $target = $reader->describe(Post::class, 'profile');

    expect($target?->targetClass)->toBe(Profile::class)
        ->and($target?->isToMany)->toBeFalse()
        ->and($target?->isOwningSide)->toBeFalse()
        ->and($target?->isOrphanRemoval)->toBeTrue();
});

it('answers null for a scalar property, a non-entity class and an anonymous class', function (): void {
    $reader = new AssociationReader(EntityManagerFactory::create());

    $anonymous = new class {
        public ?Author $author = null;
    };

    expect($reader->describe(Post::class, 'title'))->toBeNull()
        ->and($reader->describe(stdClass::class, 'anything'))->toBeNull()
        ->and($reader->describe($anonymous::class, 'author'))->toBeNull();
});

it('answers null without an entity manager and throws on the operations that need one', function (): void {
    $reader = new AssociationReader();

    expect($reader->hasEntityManager())->toBeFalse()
        ->and($reader->describe(Post::class, 'author'))->toBeNull()
        ->and(fn (): object => $reader->getReference(Author::class, 1))
        ->toThrow(LogicException::class, 'no Doctrine EntityManager')
        ->and(fn (): mixed => $reader->createOptionQuery(Author::class))
        ->toThrow(LogicException::class, 'no Doctrine EntityManager')
        ->and(fn () => $reader->remove(new Author()))
        ->toThrow(LogicException::class, 'no Doctrine EntityManager')
        ->and(fn (): string => $reader->getRecordKey(new Author()))
        ->toThrow(LogicException::class, 'no Doctrine EntityManager');
});

it('reports whether it has an entity manager', function (): void {
    expect(new AssociationReader(EntityManagerFactory::create())->hasEntityManager())->toBeTrue();
});

it('resolves a record key', function (): void {
    $entityManager = EntityManagerFactory::create();
    $author = new Author();
    $author->setName('Ada');
    EntityManagerFactory::seed($entityManager, $author);

    $reader = new AssociationReader($entityManager);
    $reloaded = $entityManager->find(Author::class, $author->getId());
    assert($reloaded instanceof Author);

    expect($reader->getRecordKey($reloaded))->toBe((string) $author->getId());
});

it('refuses a record with no identifier set', function (): void {
    $reader = new AssociationReader(EntityManagerFactory::create());

    expect(fn (): string => $reader->getRecordKey(new Author()))
        ->toThrow(LogicException::class, 'has none set');
});

it('refuses a composite identifier', function (): void {
    $reader = new AssociationReader(EntityManagerFactory::create());

    expect(fn (): string => $reader->getRecordKey(new CompositePair(1, 2, 'pair')))
        ->toThrow(LogicException::class, 'composite identifier');
});

it('references an entity by identifier', function (): void {
    $entityManager = EntityManagerFactory::create();
    $author = new Author();
    $author->setName('Ada');
    EntityManagerFactory::seed($entityManager, $author);

    $reader = new AssociationReader($entityManager);
    $id = $author->getId();
    assert($id !== null);

    expect($reader->getReference(Author::class, $id))->toBeInstanceOf(Author::class);
});

it('builds an option query selecting the target entity', function (): void {
    $entityManager = EntityManagerFactory::create();
    $author = new Author();
    $author->setName('Ada');
    EntityManagerFactory::seed($entityManager, $author);

    $reader = new AssociationReader($entityManager);
    $query = $reader->createOptionQuery(Author::class);

    expect($query->getDQL())->toBe('SELECT record FROM ' . Author::class . ' record')
        ->and($query->getQuery()->getResult())->toHaveCount(1);
});

it('removes an entity through the entity manager', function (): void {
    $entityManager = EntityManagerFactory::create();
    $author = new Author();
    $author->setName('Ada');
    EntityManagerFactory::seed($entityManager, $author);

    $reader = new AssociationReader($entityManager);
    $reloaded = $entityManager->find(Author::class, $author->getId());
    assert($reloaded instanceof Author);

    $reader->remove($reloaded);
    $entityManager->flush();
    $entityManager->clear();

    expect($entityManager->find(Author::class, $author->getId()))->toBeNull();
});
