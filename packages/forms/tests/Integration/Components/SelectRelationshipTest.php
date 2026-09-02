<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\QueryBuilder;
use LogicException;
use ReflectionMethod;
use Sylament\Forms\Components\CheckboxList;
use Sylament\Forms\Components\Select;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Author;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Post;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\PostSection;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Tag;
use Sylament\Schemas\Exceptions\ValidationException;
use Sylament\Schemas\Schema;
use Symfony\Component\Validator\Constraints\Choice;
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

    // multiple() makes the select searchable, and a searchable select enumerates
    // no map without preload() — the guard the test wants lives on the map build.
    $select = Select::make('author')->relationship(titleAttribute: 'name')->multiple()->preload();
    [$schema] = wireRecordSelect($select, $post);

    expect(fn (): array => $select->getOptions())
        ->toThrow(LogicException::class, 'Drop multiple()')
        ->and(fn (): Schema => $schema->fill($post))
        ->toThrow(LogicException::class, 'holds a single');
});

it('fills stored tag ids and syncs the collection on getData', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, $a, $b, $c] = seedTaggedPost($entityManager);

    // multiple() makes the select searchable, so the map below needs preload().
    $select = Select::make('tags')->multiple()->preload()->relationship(titleAttribute: 'name');
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

    // multiple() makes the select searchable; preload() keeps it a full-map select,
    // so the case stays about the collection write rather than the label probe.
    $select = Select::make('tags')->relationship(titleAttribute: 'name')->multiple()->preload();
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

    // multiple() makes the select searchable; preload() keeps it a full-map select,
    // so the case stays about the collection write rather than the label probe.
    $select = Select::make('tags')->multiple()->preload()->relationship(titleAttribute: 'name');
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

/*
 * The searchable arm: a searchable relationship select enumerates nothing until
 * the user types (or preload() asks for the limited map), searches the title
 * attribute — or the declared columns — through a LIKE per column, labels a
 * selection by fetching its rows, and validates by probing those labels rather
 * than by a full-map Choice.
 */

/**
 * Three authors flushed in an order the title ordering has to undo: Bob, Ada,
 * then Cy.
 *
 * @return array{Post, Author, Author, Author}
 */
function seedUnorderedAuthors(EntityManagerInterface $entityManager): array
{
    $bob = new Author();
    $bob->setName('Bob');

    $ada = new Author();
    $ada->setName('Ada');

    $cy = new Author();
    $cy->setName('Cy');

    $post = new Post();
    $post->setTitle('t');

    $entityManager->persist($bob);
    $entityManager->persist($ada);
    $entityManager->persist($cy);
    $entityManager->persist($post);
    $entityManager->flush();

    return [$post, $bob, $ada, $cy];
}

it('searches the title attribute with a LIKE and orders the results by it', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, $bob, $ada, $cy] = seedUnorderedAuthors($entityManager);

    $captured = null;

    $select = Select::make('author')->searchable()->relationship(
        titleAttribute: 'name',
        modifyQueryUsing: function (QueryBuilder $query) use (&$captured): QueryBuilder {
            return $captured = $query;
        },
    );
    wireRecordSelect($select, $post);

    expect($select->getSearchResults('b'))->toBe([$bob->getId() => 'Bob'])
        ->and($captured?->getDQL())->toContain('record.name LIKE :sylament_search_0')
        ->toContain('ORDER BY record.name ASC')
        ->and($captured?->getParameter('sylament_search_0')?->getValue())->toBe('%b%')
        ->and($captured?->getMaxResults())->toBe(50)
        ->and($select->getSearchResults(''))->toBe([$ada->getId() => 'Ada', $bob->getId() => 'Bob', $cy->getId() => 'Cy'])
        ->and($select->getSearchResults('zzz'))->toBe([]);
});

it('searches every declared column, ORed', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, $bob, $ada] = seedUnorderedAuthors($entityManager);

    $captured = null;

    $select = Select::make('author')->searchable(['name', 'id'])->relationship(
        titleAttribute: 'name',
        modifyQueryUsing: function (QueryBuilder $query) use (&$captured): QueryBuilder {
            return $captured = $query;
        },
    );
    wireRecordSelect($select, $post);

    expect($select->getSearchResults((string) $bob->getId()))->toBe([$bob->getId() => 'Bob'])
        ->and($captured?->getDQL())->toContain('WHERE record.name LIKE :sylament_search_0 OR record.id LIKE :sylament_search_1')
        ->and($select->getSearchResults('Ada'))->toBe([$ada->getId() => 'Ada']);
});

it('answers no search results when the relationship names no column to search', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post] = seedUnorderedAuthors($entityManager);

    $select = Select::make('author')->searchable()->relationship();
    wireRecordSelect($select, $post);

    expect($select->getSearchColumns())->toBeNull()
        ->and($select->getSearchResults('a'))->toBe([])
        ->and($select->hasDynamicSearchResults())->toBeFalse();
});

it('refuses a search column that hops an association rather than failing inside Doctrine', function (): void {
    // Boot before Select::make(): a component built while no kernel is up gets the
    // container-less AssociationReader, and the guard below needs a real mapping.
    $this::getContainer();

    $select = Select::make('post')->relationship(titleAttribute: 'title')->searchable(['author.name']);
    wireRecordSelect($select, new PostSection());

    expect(fn (): array => $select->getSearchResults('a'))
        ->toThrow(LogicException::class, 'not a mapped field');
});

it('limits the search results to optionsLimit, unless the query scope set a limit of its own', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, $bob, $ada] = seedUnorderedAuthors($entityManager);

    $limited = Select::make('author')->searchable()->optionsLimit(2)->relationship(titleAttribute: 'name');
    wireRecordSelect($limited, $post);

    $scoped = Select::make('author')->searchable()->optionsLimit(2)->relationship(
        titleAttribute: 'name',
        modifyQueryUsing: fn (QueryBuilder $query): QueryBuilder => $query->setMaxResults(1),
    );
    wireRecordSelect($scoped, $post);

    expect($limited->getSearchResults(''))->toBe([$ada->getId() => 'Ada', $bob->getId() => 'Bob'])
        ->and($scoped->getSearchResults(''))->toBe([$ada->getId() => 'Ada'])
        ->and($scoped->getOptionsLimit())->toBe(1);
});

it('hands the search term to modifyQueryUsing, and null outside a search', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, , $ada] = seedUnorderedAuthors($entityManager);

    $terms = [];

    $select = Select::make('author')->searchable()->preload()->relationship(
        titleAttribute: 'name',
        modifyQueryUsing: function (QueryBuilder $query, ?string $search) use (&$terms): QueryBuilder {
            $terms[] = $search;

            return $query->andWhere("record.name = 'Ada'");
        },
    );
    wireRecordSelect($select, $post);

    expect($select->getSearchResults('A'))->toBe([$ada->getId() => 'Ada'])
        ->and($select->getOptions())->toBe([$ada->getId() => 'Ada'])
        ->and($terms)->toBe(['A', null]);
});

it('lowers both sides of the LIKE only when told to, since SQLite is not Postgres', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, , $ada] = seedUnorderedAuthors($entityManager);

    $shape = function (?bool $forced) use ($post): array {
        $captured = null;

        $select = Select::make('author')->searchable()->forceSearchCaseInsensitive($forced)->relationship(
            titleAttribute: 'name',
            modifyQueryUsing: function (QueryBuilder $query) use (&$captured): QueryBuilder {
                return $captured = $query;
            },
        );
        wireRecordSelect($select, $post);

        $results = $select->getSearchResults('ADA');

        return [$results, $captured?->getDQL(), $captured?->getParameter('sylament_search_0')?->getValue()];
    };

    [$results, $dql, $parameter] = $shape(true);

    expect($results)->toBe([$ada->getId() => 'Ada'])
        ->and($dql)->toContain('LOWER(record.name) LIKE :sylament_search_0')
        ->and($parameter)->toBe('%ada%');

    [, $dql, $parameter] = $shape(false);

    expect($dql)->toContain('record.name LIKE :sylament_search_0')
        ->not->toContain('LOWER(')
        ->and($parameter)->toBe('%ADA%');

    [, $dql] = $shape(null);

    expect($dql)->not->toContain('LOWER(');
});

it('enumerates nothing when searchable, and the limited ordered map when preloaded', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, $bob, $ada] = seedUnorderedAuthors($entityManager);

    $searchable = Select::make('author')->searchable()->relationship(titleAttribute: 'name');
    wireRecordSelect($searchable, $post);

    $preloaded = Select::make('author')->searchable()->preload()->optionsLimit(2)->relationship(titleAttribute: 'name');
    wireRecordSelect($preloaded, $post);

    $plain = Select::make('author')->optionsLimit(2)->relationship(titleAttribute: 'name');
    wireRecordSelect($plain, $post);

    expect($searchable->getOptions())->toBe([])
        ->and($preloaded->getOptions())->toBe([$ada->getId() => 'Ada', $bob->getId() => 'Bob'])
        ->and($plain->getOptions())->toHaveCount(3)
        ->and(array_keys($plain->getOptions()))->toBe([$ada->getId(), $bob->getId(), array_key_last($plain->getOptions())]);
});

it('labels rows by the record-label callback in database order, or by the title attribute in title order', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, $bob, $ada, $cy] = seedUnorderedAuthors($entityManager);

    $select = Select::make('author')
        ->searchable()
        ->preload()
        ->relationship(titleAttribute: 'name')
        ->getOptionLabelFromRecordUsing(fn (Author $record): string => mb_strtoupper($record->getName()));
    wireRecordSelect($select, $post);

    expect($select->getOptions())->toBe([$bob->getId() => 'BOB', $ada->getId() => 'ADA', $cy->getId() => 'CY'])
        ->and($select->getSearchResults('b'))->toBe([$bob->getId() => 'BOB']);
});

it('resolves the selected label through the record, by title attribute or by callback', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, , $ada] = seedUnorderedAuthors($entityManager);

    $select = Select::make('author')->searchable()->relationship(titleAttribute: 'name');
    wireRecordSelect($select, $post);
    $select->state((string) $ada->getId());

    $callback = Select::make('author')
        ->searchable()
        ->relationship(titleAttribute: 'name')
        ->getOptionLabelFromRecordUsing(fn (Author $record): string => mb_strtoupper($record->getName()));
    wireRecordSelect($callback, $post);
    $callback->state((string) $ada->getId());

    $missing = Select::make('author')->searchable()->relationship(titleAttribute: 'name');
    wireRecordSelect($missing, $post);
    $missing->state('999');

    expect($select->getOptions())->toBe([])
        ->and($select->getOptionLabel())->toBe('Ada')
        ->and($callback->getOptionLabel())->toBe('ADA')
        ->and($missing->getOptionLabel(withDefault: false))->toBeNull()
        ->and($missing->getOptionLabel())->toBe('999');
});

it('resolves the selected labels of a multiple selection through the rows, ignoring an id outside the scope', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, $a, $b, $c] = seedTaggedPost($entityManager);

    $select = Select::make('tags')->multiple()->relationship(
        titleAttribute: 'name',
        modifyQueryUsing: fn (QueryBuilder $query): QueryBuilder => $query->andWhere("record.name <> 'c'"),
    );
    wireRecordSelect($select, $post);
    $select->state([(string) $b->getId(), (string) $c->getId(), (string) $a->getId(), '999']);

    $callback = Select::make('tags')->multiple()->relationship(titleAttribute: 'name')
        ->getOptionLabelFromRecordUsing(fn (Tag $record): string => mb_strtoupper($record->getName()));
    wireRecordSelect($callback, $post);
    $callback->state([(string) $a->getId()]);

    // The rows the query admits are the labels; a value it does not admit is
    // absent rather than defaulted, as Filament's closure arm leaves it.
    expect($select->getOptionLabels(withDefaults: false))->toBe([$a->getId() => 'a', $b->getId() => 'b'])
        ->and($select->getOptionLabels())->toBe([$a->getId() => 'a', $b->getId() => 'b'])
        ->and($callback->getOptionLabels())->toBe([$a->getId() => 'A']);
});

it('fetches the selected record through the scoped option query, answering null on a miss or outside the scope', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, $bob, $ada] = seedUnorderedAuthors($entityManager);

    $select = Select::make('author')->relationship(titleAttribute: 'name');
    wireRecordSelect($select, $post);
    $select->state((string) $ada->getId());

    $missing = Select::make('author')->relationship(titleAttribute: 'name');
    wireRecordSelect($missing, $post);
    $missing->state('999');

    $scoped = Select::make('author')->relationship(
        titleAttribute: 'name',
        modifyQueryUsing: fn (QueryBuilder $query): QueryBuilder => $query->andWhere("record.name = 'Ada'"),
    );
    wireRecordSelect($scoped, $post);
    $scoped->state((string) $bob->getId());

    expect($select->getSelectedRecord())->toBe($ada)
        ->and($missing->getSelectedRecord())->toBeNull()
        ->and($scoped->getSelectedRecord())->toBeNull();
});

it('validates a searchable relationship select by probing the label, so a missing or out-of-scope id is rejected', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, $ada, $bob] = seedAuthoredPost($entityManager);

    $select = Select::make('author')->searchable()->relationship(
        titleAttribute: 'name',
        modifyQueryUsing: fn (QueryBuilder $query): QueryBuilder => $query->andWhere("record.name <> 'Bob'"),
    );
    [$schema, $host] = wireRecordSelect($select, $post);

    $schema->fill($post);

    expect($select->getOptions())->toBe([])
        ->and($schema->getData())->toBe($post)
        ->and($post->getAuthor())->toBe($ada);

    foreach (['999', (string) $bob->getId()] as $rejected) {
        $host->data['author'] = $rejected;
        $host->clearSchemaValidation();

        expect(fn (): object => $schema->getData())->toThrow(ValidationException::class)
            ->and($host->getErrorsForSchemaStatePath('data.author'))->not->toBe([])
            ->and($post->getAuthor())->toBe($ada);
    }
});

it('keeps the full-map Choice on a relationship select that is not searchable', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, $ada, $bob] = seedAuthoredPost($entityManager);

    $select = Select::make('author')->relationship(titleAttribute: 'name');
    [$schema] = wireRecordSelect($select, $post);

    $schema->fill($post);

    $choice = array_values(array_filter($select->getValidationConstraints(), fn (object $c): bool => $c instanceof Choice));

    expect($choice)->toHaveCount(1)
        ->and($choice[0]->choices)->toBe([(string) $ada->getId(), (string) $bob->getId()]);
});

/**
 * A flushed tag tree: `child` parented to `root`, with `other` as the third row.
 * Every tag is managed, so `getRecord()` answers a real identifier.
 *
 * @return array{Tag, Tag, Tag}
 */
function seedParentedTags(EntityManagerInterface $entityManager): array
{
    $root = new Tag();
    $root->setName('root');

    $other = new Tag();
    $other->setName('other');

    $child = new Tag();
    $child->setName('child');
    $child->setParent($root);

    $entityManager->persist($root);
    $entityManager->persist($other);
    $entityManager->persist($child);
    $entityManager->flush();

    return [$root, $other, $child];
}

it('omits the bound record from the options under ignoreRecord', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$root, $other, $child] = seedParentedTags($entityManager);

    $select = Select::make('parent')->relationship(titleAttribute: 'name', ignoreRecord: true);
    wireRecordSelect($select, $root);

    expect($select->getOptions())->toBe([
        $child->getId() => 'child',
        $other->getId() => 'other',
    ])
        ->and($root->getId())->not->toBeNull();
});

it('omits the bound record from the search results under ignoreRecord', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$root, $other] = seedParentedTags($entityManager);

    $select = Select::make('parent')->searchable(['name'])->relationship(titleAttribute: 'name', ignoreRecord: true);
    wireRecordSelect($select, $root);

    // 'root' matches the term too, and is the row the flag takes out.
    expect($select->getSearchResults('o'))->toBe([$other->getId() => 'other']);
});

it('still resolves the label of an excluded record it already holds', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$root] = seedParentedTags($entityManager);

    // A stored self-reference is legacy data the label arms must keep speaking, or an
    // untouched submission fails Choice against an option map that no longer holds it.
    $select = Select::make('parent')->relationship(titleAttribute: 'name', ignoreRecord: true);
    wireRecordSelect($select, $root);
    $select->state((string) $root->getId());

    expect($select->getSelectedRecord())->toBe($root)
        ->and($select->getOptionLabel())->toBe('root')
        ->and($select->getOptionLabelsFromRelationship([(string) $root->getId()]))->toBe([$root->getId() => 'root']);
});

it('excludes nothing for an unflushed record', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$root, $other, $child] = seedParentedTags($entityManager);

    $select = Select::make('parent')->relationship(titleAttribute: 'name', ignoreRecord: true);
    wireRecordSelect($select, new Tag());

    expect(array_values($select->getOptions()))->toBe(['child', 'other', 'root'])
        ->and([$root, $other, $child])->toHaveCount(3);
});

it('excludes nothing when the schema names a record class rather than an instance', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    seedParentedTags($entityManager);

    // The create-page shape: `getRecord()` answers null for a class-string binding,
    // and there is no row to take out. An array record lands on the same branch.
    $host = new FieldRenderHost();
    $select = Select::make('parent')->relationship(titleAttribute: 'name', ignoreRecord: true);

    Schema::make($host)->statePath('data')->record(Tag::class)->components([$select])->getComponents();

    expect(array_values($select->getOptions()))->toBe(['child', 'other', 'root']);
});

it('refuses a bound record of another class than the association target', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post] = seedAuthoredPost($entityManager);

    // `author` is not self-referential: the record is a Post while the options are
    // Authors, so the Post's identifier would silently exclude an unrelated Author.
    $select = Select::make('author')->relationship(titleAttribute: 'name', ignoreRecord: true);
    wireRecordSelect($select, $post);

    expect(fn (): array => $select->getOptions())
        ->toThrow(LogicException::class, 'self-referential');
});

it('never memoizes the option map under ignoreRecord, so a clone answers for its own record', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$root, $other] = seedParentedTags($entityManager);

    $select = Select::make('parent')->relationship(titleAttribute: 'name', ignoreRecord: true);
    wireRecordSelect($select, $root);

    expect(array_values($select->getOptions()))->toBe(['child', 'other']);

    $clone = $select->getClone();
    $clone->record($other);

    expect(array_values($clone->getOptions()))->toBe(['child', 'root']);
});

it('binds no exclusion parameter by default', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$root] = seedParentedTags($entityManager);

    $select = Select::make('parent')->relationship(titleAttribute: 'name');
    wireRecordSelect($select, $root);

    expect(array_values($select->getOptions()))->toBe(['child', 'other', 'root']);
});

it('leaves the shared relationship() signature at three parameters', function (): void {
    // `ignoreRecord:` is a Select surface upstream; CheckboxList inherits the trait's
    // setter verbatim, so widening the trait would move it away from Filament's.
    expect(new ReflectionMethod(CheckboxList::class, 'relationship')->getNumberOfParameters())->toBe(3)
        ->and(new ReflectionMethod(Select::class, 'relationship')->getNumberOfParameters())->toBe(4);
});
