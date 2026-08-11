<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\QueryBuilder;
use LogicException;
use Sylament\Forms\Components\Select;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Author;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Post;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Tag;
use Sylament\Schemas\Exceptions\ValidationException;
use Sylament\Schemas\Schema;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/*
 * Select::relationship() against a real Doctrine entity manager: the option map
 * comes off the target table, fill() reads the association's key through the load
 * hook, and getData() writes the association back through the save hook. Nothing
 * here is mocked — the point of the epic is that the two hooks and the reader
 * compose into a round-trip the ordinary walks drive.
 */

/**
 * Bind a select to a record instance through a `data`-bag host, keyed and
 * validator-fed so `getData()` can run its validation pass.
 *
 * @return array{Schema, FieldRenderHost}
 */
function wireRecordSelect(Select $select, object $record): array
{
    $host = new FieldRenderHost();
    $host->setSchemaStateValidator(test()->getContainer()->get(ValidatorInterface::class));

    $schema = Schema::make($host)->statePath('data')->record($record)->components([$select]);

    $host->cacheSchema('schema', $schema);
    $schema->getComponents();

    return [$schema, $host];
}

/**
 * A flushed post authored by Ada, with Bob available as the other option. The
 * identity map is left intact, so every entity below is managed.
 *
 * @return array{Post, Author, Author}
 */
function seedAuthoredPost(EntityManagerInterface $entityManager): array
{
    $ada = new Author();
    $ada->setName('Ada');

    $bob = new Author();
    $bob->setName('Bob');

    $post = new Post();
    $post->setTitle('t');
    $post->setAuthor($ada);

    $entityManager->persist($ada);
    $entityManager->persist($bob);
    $entityManager->persist($post);
    $entityManager->flush();

    return [$post, $ada, $bob];
}

/**
 * A flushed post tagged `a` and `b`, with `c` available as the third option.
 *
 * @return array{Post, Tag, Tag, Tag}
 */
function seedTaggedPost(EntityManagerInterface $entityManager): array
{
    $a = new Tag();
    $a->setName('a');

    $b = new Tag();
    $b->setName('b');

    $c = new Tag();
    $c->setName('c');

    $post = new Post();
    $post->setTitle('t');
    $post->addTag($a);
    $post->addTag($b);

    $entityManager->persist($a);
    $entityManager->persist($b);
    $entityManager->persist($c);
    $entityManager->persist($post);
    $entityManager->flush();

    return [$post, $a, $b, $c];
}

it('fills the stored author id and offers the author table as options', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, $ada, $bob] = seedAuthoredPost($entityManager);

    $select = Select::make('author')->relationship(titleAttribute: 'name');
    [$schema, $host] = wireRecordSelect($select, $post);

    $schema->fill($post);

    expect($host->data['author'])->toBe((string) $ada->getId())
        ->and($select->getOptions())->toBe([
            $ada->getId() => 'Ada',
            $bob->getId() => 'Bob',
        ])
        ->and($select->getOptionLabel())->toBe('Ada');
});

it('maps a selected id onto the association through getData', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, , $bob] = seedAuthoredPost($entityManager);

    $select = Select::make('author')->relationship(titleAttribute: 'name');
    [$schema, $host] = wireRecordSelect($select, $post);

    $schema->fill($post);

    $host->data['author'] = (string) $bob->getId();

    expect($schema->getData())->toBe($post)
        ->and($post->getAuthor())->toBe($entityManager->find(Author::class, $bob->getId()));
});

it('nulls the association on an empty submission', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post] = seedAuthoredPost($entityManager);

    $select = Select::make('author')->relationship(titleAttribute: 'name');
    [$schema, $host] = wireRecordSelect($select, $post);

    $schema->fill($post);

    $host->data['author'] = '';

    expect($schema->getData())->toBe($post)
        ->and($post->getAuthor())->toBeNull();
});

it('validates the submitted id against the option map', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, $ada] = seedAuthoredPost($entityManager);

    $select = Select::make('author')->relationship(titleAttribute: 'name');
    [$schema, $host] = wireRecordSelect($select, $post);

    $schema->fill($post);

    $host->data['author'] = '999';

    expect(fn (): object => $schema->getData())->toThrow(ValidationException::class)
        ->and($host->getErrorsForSchemaStatePath('data.author'))->not->toBe([])
        ->and($post->getAuthor())->toBe($ada);
});

it('scopes options through modifyQueryUsing', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, $ada] = seedAuthoredPost($entityManager);

    $select = Select::make('author')->relationship(
        titleAttribute: 'name',
        modifyQueryUsing: fn (QueryBuilder $query): QueryBuilder => $query->where("record.name = 'Ada'"),
    );

    wireRecordSelect($select, $post);

    expect($select->getOptions())->toBe([$ada->getId() => 'Ada']);
});

it('memoizes the option map, and re-queries whenever a query scope is declared', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, $ada, $bob] = seedAuthoredPost($entityManager);

    $select = Select::make('author')->relationship(titleAttribute: 'name');
    wireRecordSelect($select, $post);

    $scoped = Select::make('author')->relationship(
        titleAttribute: 'name',
        modifyQueryUsing: static fn (QueryBuilder $query): QueryBuilder => $query,
    );
    wireRecordSelect($scoped, $post);

    $seeded = [$ada->getId() => 'Ada', $bob->getId() => 'Bob'];

    expect($select->getOptions())->toBe($seeded)
        ->and($scoped->getOptions())->toBe($seeded);

    $carol = new Author();
    $carol->setName('Carol');

    $entityManager->persist($carol);
    $entityManager->flush();

    expect($select->getOptions())->toBe($seeded)
        ->and($scoped->getOptions())->toBe($seeded + [$carol->getId() => 'Carol']);
});

it("evaluates a cloned select's relationship closures against the clone", function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, $ada, $bob] = seedAuthoredPost($entityManager);

    $select = Select::make('author')->relationship(titleAttribute: 'name');
    wireRecordSelect($select, $post);

    $clone = $select->getClone();

    $select->relationship('tags', 'name');

    expect($clone->getOptions())->toBe([$ada->getId() => 'Ada', $bob->getId() => 'Bob'])
        ->and($clone->loadStateFromRelationships($post))->toBe((string) $ada->getId());
});

it('throws loudly when the relationship declares no title attribute', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post] = seedAuthoredPost($entityManager);

    $select = Select::make('author')->relationship();
    wireRecordSelect($select, $post);

    expect(fn (): array => $select->getOptions())
        ->toThrow(LogicException::class, 'titleAttribute');
});

it('maps a relationship named apart from the field onto that association', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, $ada, $bob] = seedAuthoredPost($entityManager);

    $select = Select::make('authorId')->relationship('author', 'name');
    [$schema, $host] = wireRecordSelect($select, $post);

    $schema->fill($post);

    expect($select->getRelationshipName())->toBe('author')
        ->and($host->data['authorId'])->toBe((string) $ada->getId());

    $host->data['authorId'] = (string) $bob->getId();

    expect($schema->getData())->toBe($post)
        ->and($post->getAuthor())->toBe($bob);
});

it('refuses a to-many association the field never declared multiple() for', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post] = seedTaggedPost($entityManager);

    $select = Select::make('tags')->relationship(titleAttribute: 'name');
    [$schema] = wireRecordSelect($select, $post);

    expect(fn (): array => $select->getOptions())
        ->toThrow(LogicException::class, 'Call multiple() on this field')
        ->and(fn (): Schema => $schema->fill($post))
        ->toThrow(LogicException::class, 'holds a collection of');
});

it('refuses a to-one association the field declared multiple() for', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post] = seedAuthoredPost($entityManager);

    $select = Select::make('author')->relationship(titleAttribute: 'name')->multiple();
    [$schema] = wireRecordSelect($select, $post);

    expect(fn (): array => $select->getOptions())
        ->toThrow(LogicException::class, 'Drop multiple()')
        ->and(fn (): Schema => $schema->fill($post))
        ->toThrow(LogicException::class, 'holds a single');
});

it('fills stored tag ids and syncs the collection on getData', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, $a, $b, $c] = seedTaggedPost($entityManager);

    $select = Select::make('tags')->multiple()->relationship(titleAttribute: 'name');
    [$schema, $host] = wireRecordSelect($select, $post);

    $schema->fill($post);

    expect($host->data['tags'])->toBe([(string) $a->getId(), (string) $b->getId()])
        ->and($select->getOptions())->toBe([
            $a->getId() => 'a',
            $b->getId() => 'b',
            $c->getId() => 'c',
        ]);

    $collection = $post->getTags();

    $host->data['tags'] = [(string) $a->getId(), (string) $c->getId()];

    expect($schema->getData())->toBe($post);

    $entityManager->flush();

    expect($post->getTags())->toBe($collection)
        ->toBeInstanceOf(PersistentCollection::class)
        ->and($post->getTags()->contains($a))->toBeTrue()
        ->and($post->getTags()->contains($c))->toBeTrue()
        ->and($post->getTags()->contains($b))->toBeFalse()
        ->and($entityManager->getRepository(Tag::class)->count([]))->toBe(3);
});

it('empties the collection on an empty multi submission', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post] = seedTaggedPost($entityManager);

    $select = Select::make('tags')->relationship(titleAttribute: 'name')->multiple();
    [$schema, $host] = wireRecordSelect($select, $post);

    $schema->fill($post);

    $host->data['tags'] = [];

    expect($schema->getData())->toBe($post);

    $entityManager->flush();

    expect($post->getTags()->count())->toBe(0)
        ->and($entityManager->getRepository(Tag::class)->count([]))->toBe(3);
});

it('round-trips a reloaded post whose collection is still lazy', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$seeded, $a, $b, $c] = seedTaggedPost($entityManager);

    $postId = $seeded->getId();
    $aId = $a->getId();
    $bId = $b->getId();
    $cId = $c->getId();

    $entityManager->clear();

    $post = $entityManager->find(Post::class, $postId);
    assert($post instanceof Post);

    $select = Select::make('tags')->multiple()->relationship(titleAttribute: 'name');
    [$schema, $host] = wireRecordSelect($select, $post);

    $schema->fill($post);

    expect($host->data['tags'])->toBe([(string) $aId, (string) $bId]);

    $host->data['tags'] = [(string) $aId, (string) $cId];

    $schema->getData();

    $entityManager->flush();
    $entityManager->clear();

    $reloaded = $entityManager->find(Post::class, $postId);
    assert($reloaded instanceof Post);

    $names = array_map(static fn (Tag $tag): string => $tag->getName(), $reloaded->getTags()->toArray());
    sort($names);

    expect($names)->toBe(['a', 'c']);
});
