<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components;

use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Sylament\Forms\Components\CheckboxList;
use Sylament\Forms\Components\Select;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\CompositePair;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Leaflet;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Post;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Sticker;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Tag;
use Sylament\Forms\Tests\Fixtures\Doctrine\RecordingMiddleware;
use Sylament\Schemas\Schema;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/*
 * A to-many relation-backed field bound to the INVERSE side of a many-to-many.
 * Doctrine persists only the owning side, so the save has to reach the target's
 * own collection for every member it adds or drops. The fixtures' inverse adders
 * deliberately heal nothing, which is what makes a save that wrote only the
 * record's own collection visible here: it reports success and writes no join
 * row. Every save is asserted twice — the join rows read back through the
 * connection, and the statements it cost.
 */

/**
 * Bind a field to a record through a `data`-bag host, validator-fed so
 * `getData()` runs its validation pass.
 *
 * @return array{Schema, FieldRenderHost}
 */
function inverseSideWire(object $field, object $record): array
{
    $host = new FieldRenderHost();
    $host->setSchemaStateValidator(test()->getContainer()->get(ValidatorInterface::class));

    $schema = Schema::make($host)->statePath('data')->record($record)->components([$field]);

    $host->cacheSchema('schema', $schema);
    $schema->getComponents();

    return [$schema, $host];
}

/**
 * Posts `one`, `two` and `three` (ids 1–3) and tags `a` and `b` (ids 1–2), joined
 * through the owning side: `a` on posts 1 and 2, `b` on post 3. The manager is
 * cleared and tag `a` answered freshly loaded, so its inverse collection is read
 * from the join table rather than from memory.
 */
function inverseSideSeedTags(EntityManagerInterface $entityManager): Tag
{
    $posts = inverseSideSeedPosts($entityManager);

    $a = new Tag();
    $a->setName('a');

    $b = new Tag();
    $b->setName('b');

    $entityManager->persist($a);
    $entityManager->persist($b);

    $posts[0]->addTag($a);
    $posts[1]->addTag($a);
    $posts[2]->addTag($b);

    $entityManager->flush();
    $entityManager->clear();

    $tag = $entityManager->find(Tag::class, 1);
    assert($tag instanceof Tag);

    return $tag;
}

/**
 * The same shape over a composite-keyed record: pairs `1~2` on posts 1 and 2
 * and `3~4` on post 3, with `1~2` answered freshly loaded.
 */
function inverseSideSeedPairs(EntityManagerInterface $entityManager): CompositePair
{
    $posts = inverseSideSeedPosts($entityManager);

    $pair = new CompositePair(1, 2, 'one-two');
    $other = new CompositePair(3, 4, 'three-four');

    $entityManager->persist($pair);
    $entityManager->persist($other);

    $posts[0]->addPair($pair);
    $posts[1]->addPair($pair);
    $posts[2]->addPair($other);

    $entityManager->flush();
    $entityManager->clear();

    $record = $entityManager->find(CompositePair::class, ['a' => 1, 'b' => 2]);
    assert($record instanceof CompositePair);

    return $record;
}

/**
 * @return list<Post>
 */
function inverseSideSeedPosts(EntityManagerInterface $entityManager): array
{
    $posts = [];

    foreach (['one', 'two', 'three'] as $title) {
        $post = new Post();
        $post->setTitle($title);

        $entityManager->persist($post);

        $posts[] = $post;
    }

    return $posts;
}

/**
 * Leaflets `one`, `two` and `three` (ids 1–3) and stickers `a` and `b` (ids
 * 1–2), joined through the owning side — the one collection in this fixture
 * tree that declares orphanRemoval: `a` on leaflets 1 and 2, `b` on leaflet 3.
 * Sticker `a` is answered freshly loaded.
 */
function inverseSideSeedStickers(EntityManagerInterface $entityManager): Sticker
{
    $leaflets = [];

    foreach (['one', 'two', 'three'] as $title) {
        $leaflet = new Leaflet();
        $leaflet->setTitle($title);

        $entityManager->persist($leaflet);

        $leaflets[] = $leaflet;
    }

    $a = new Sticker();
    $a->setLabel('a');

    $b = new Sticker();
    $b->setLabel('b');

    $entityManager->persist($a);
    $entityManager->persist($b);

    $leaflets[0]->addSticker($a);
    $leaflets[1]->addSticker($a);
    $leaflets[2]->addSticker($b);

    $entityManager->flush();
    $entityManager->clear();

    $sticker = $entityManager->find(Sticker::class, 1);
    assert($sticker instanceof Sticker);

    return $sticker;
}

/**
 * @return list<array<string, mixed>>
 */
function inverseSideTagRows(EntityManagerInterface $entityManager): array
{
    return $entityManager->getConnection()->fetchAllAssociative(
        'SELECT post_id, tag_id FROM post_tag ORDER BY tag_id, post_id',
    );
}

/**
 * @return list<array<string, mixed>>
 */
function inverseSidePairRows(EntityManagerInterface $entityManager): array
{
    return $entityManager->getConnection()->fetchAllAssociative(
        'SELECT post_id, composite_pair_a, composite_pair_b FROM post_composite_pair ORDER BY composite_pair_a, post_id',
    );
}

/**
 * @return list<array<string, mixed>>
 */
function inverseSideLeafletRows(EntityManagerInterface $entityManager): array
{
    return $entityManager->getConnection()->fetchAllAssociative(
        'SELECT leaflet_id, sticker_id FROM leaflet_sticker ORDER BY sticker_id, leaflet_id',
    );
}

/**
 * The surviving records of the collection the refusal protects, read through
 * the connection: a refused save leaves every one of them.
 *
 * @return list<int>
 */
function inverseSideStickerIds(EntityManagerInterface $entityManager): array
{
    return array_map(
        static fn (mixed $id): int => (int) $id,
        $entityManager->getConnection()->fetchFirstColumn('SELECT id FROM Sticker ORDER BY id'),
    );
}

function inverseSideRecorder(): RecordingMiddleware
{
    return test()->getContainer()->get(RecordingMiddleware::class);
}

/**
 * Each recorded statement's leading keyword, counted.
 *
 * @param list<string> $statements
 *
 * @return array<string, int>
 */
function inverseSideKinds(array $statements): array
{
    return array_count_values(array_map(
        static fn (string $sql): string => strtok($sql, ' ') ?: $sql,
        $statements,
    ));
}

/**
 * @param list<string> $statements
 *
 * @return list<string>
 */
function inverseSideWrites(array $statements): array
{
    return array_values(array_filter(
        $statements,
        static fn (string $sql): bool => ! str_starts_with($sql, 'SELECT'),
    ));
}

/*
 * The recorder keeps each statement's SQL and not its parameters, so the kept
 * member's silence is read off the counts: one DELETE and one INSERT, with the
 * kept row still there afterwards. Rewriting the kept member would make it two
 * of each.
 */
it('writes the join rows a to-many Select on the inverse side adds and drops, and leaves the kept one alone', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $tag = inverseSideSeedTags($entityManager);

    $select = Select::make('posts')->multiple()->preload()->relationship(titleAttribute: 'title');
    [$schema, $host] = inverseSideWire($select, $tag);

    $schema->fill($tag);
    $select->getOptions();

    expect($host->data['posts'])->toBe(['1', '2']);

    $recorder = inverseSideRecorder();
    $recorder->reset();

    $host->data['posts'] = ['1', '3'];

    expect($schema->getData())->toBe($tag);

    $entityManager->flush();

    // One read probes the submitted labels; the other two load the owning
    // collection of the dropped post and of the added one.
    expect(inverseSideKinds($recorder->statements()))->toBe(['SELECT' => 3, 'BEGIN' => 1, 'DELETE' => 1, 'INSERT' => 1, 'COMMIT' => 1])
        ->and(inverseSideWrites($recorder->statements()))->toBe([
            'BEGIN',
            'DELETE FROM post_tag WHERE post_id = ? AND tag_id = ?',
            'INSERT INTO post_tag (post_id, tag_id) VALUES (?, ?)',
            'COMMIT',
        ])
        ->and(inverseSideTagRows($entityManager))->toBe([
            ['post_id' => 1, 'tag_id' => 1],
            ['post_id' => 3, 'tag_id' => 1],
            ['post_id' => 3, 'tag_id' => 2],
        ]);
});

it('writes the same join rows through a CheckboxList, which rides the same arm', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $tag = inverseSideSeedTags($entityManager);

    $list = CheckboxList::make('posts')->relationship(titleAttribute: 'title');
    [$schema, $host] = inverseSideWire($list, $tag);

    $schema->fill($tag);
    $list->getOptions();

    expect($host->data['posts'])->toBe(['1', '2']);

    $recorder = inverseSideRecorder();
    $recorder->reset();

    $host->data['posts'] = ['1', '3'];

    expect($schema->getData())->toBe($tag);

    $entityManager->flush();

    expect(inverseSideKinds($recorder->statements()))->toBe(['SELECT' => 2, 'BEGIN' => 1, 'DELETE' => 1, 'INSERT' => 1, 'COMMIT' => 1])
        ->and(inverseSideWrites($recorder->statements()))->toBe([
            'BEGIN',
            'DELETE FROM post_tag WHERE post_id = ? AND tag_id = ?',
            'INSERT INTO post_tag (post_id, tag_id) VALUES (?, ?)',
            'COMMIT',
        ])
        ->and(inverseSideTagRows($entityManager))->toBe([
            ['post_id' => 1, 'tag_id' => 1],
            ['post_id' => 3, 'tag_id' => 1],
            ['post_id' => 3, 'tag_id' => 2],
        ]);
});

/*
 * A live host saves and re-renders in the same request, re-filling from the same
 * record instance — so the record's own collection has to carry the submission
 * too, or the form would show the old selection over rows that hold the new one.
 */
it('shows the new selection when the same field is re-filled in the same request, and the join rows agree', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $tag = inverseSideSeedTags($entityManager);

    $select = Select::make('posts')->multiple()->preload()->relationship(titleAttribute: 'title');
    [$schema, $host] = inverseSideWire($select, $tag);

    $schema->fill($tag);

    $host->data['posts'] = ['1', '3'];

    $schema->getData();
    $entityManager->flush();

    $schema->fill($tag);

    expect($host->data['posts'])->toBe(['1', '3'])
        ->and(inverseSideTagRows($entityManager))->toBe([
            ['post_id' => 1, 'tag_id' => 1],
            ['post_id' => 3, 'tag_id' => 1],
            ['post_id' => 3, 'tag_id' => 2],
        ]);
});

it('deletes every join row of the record on an empty submission, and no other', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $tag = inverseSideSeedTags($entityManager);

    $list = CheckboxList::make('posts')->relationship(titleAttribute: 'title');
    [$schema, $host] = inverseSideWire($list, $tag);

    $schema->fill($tag);
    $list->getOptions();

    $recorder = inverseSideRecorder();
    $recorder->reset();

    $host->data['posts'] = [];

    expect($schema->getData())->toBe($tag);

    $entityManager->flush();

    expect(inverseSideWrites($recorder->statements()))->toBe([
        'BEGIN',
        'DELETE FROM post_tag WHERE post_id = ? AND tag_id = ?',
        'DELETE FROM post_tag WHERE post_id = ? AND tag_id = ?',
        'COMMIT',
    ])
        ->and(inverseSideTagRows($entityManager))->toBe([
            ['post_id' => 3, 'tag_id' => 2],
        ]);
});

/*
 * The create page, which the same form() backs: the record is persisted only
 * after the field has been filled, so the arm reaches the owning side of a
 * record that has no identifier yet and whose own collection is empty — the
 * two things every case above starts with the opposite of. A regression that
 * wrote join rows only for a record already holding one would leave every
 * other case here green and this one with an empty join table.
 */
it('writes the join rows of a record persisted only after the field was filled', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);

    inverseSideSeedPosts($entityManager);

    $entityManager->flush();
    $entityManager->clear();

    $tag = new Tag();
    $tag->setName('fresh');

    $select = Select::make('posts')->multiple()->preload()->relationship(titleAttribute: 'title');
    [$schema, $host] = inverseSideWire($select, $tag);

    $schema->fill($tag);
    $select->getOptions();

    expect($host->data['posts'])->toBe([]);

    $recorder = inverseSideRecorder();
    $recorder->reset();

    $host->data['posts'] = ['1', '3'];

    expect($schema->getData())->toBe($tag);

    $entityManager->persist($tag);
    $entityManager->flush();

    expect(inverseSideWrites($recorder->statements()))->toBe([
        'BEGIN',
        'INSERT INTO Tag (name, parent_id) VALUES (?, ?)',
        'INSERT INTO post_tag (post_id, tag_id) VALUES (?, ?)',
        'INSERT INTO post_tag (post_id, tag_id) VALUES (?, ?)',
        'COMMIT',
    ])
        ->and(inverseSideTagRows($entityManager))->toBe([
            ['post_id' => 1, 'tag_id' => 1],
            ['post_id' => 3, 'tag_id' => 1],
        ]);
});

it('writes the join rows of a record keyed by two columns holding the inverse side', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $pair = inverseSideSeedPairs($entityManager);

    $select = Select::make('posts')->multiple()->preload()->relationship(titleAttribute: 'title');
    [$schema, $host] = inverseSideWire($select, $pair);

    $schema->fill($pair);
    $select->getOptions();

    expect($host->data['posts'])->toBe(['1', '2']);

    $recorder = inverseSideRecorder();
    $recorder->reset();

    $host->data['posts'] = ['1', '3'];

    expect($schema->getData())->toBe($pair);

    $entityManager->flush();

    expect(inverseSideKinds($recorder->statements()))->toBe(['SELECT' => 3, 'BEGIN' => 1, 'DELETE' => 1, 'INSERT' => 1, 'COMMIT' => 1])
        ->and(inverseSideWrites($recorder->statements()))->toBe([
            'BEGIN',
            'DELETE FROM post_composite_pair WHERE post_id = ? AND composite_pair_a = ? AND composite_pair_b = ?',
            'INSERT INTO post_composite_pair (post_id, composite_pair_a, composite_pair_b) VALUES (?, ?, ?)',
            'COMMIT',
        ])
        ->and(inverseSidePairRows($entityManager))->toBe([
            ['post_id' => 1, 'composite_pair_a' => 1, 'composite_pair_b' => 2],
            ['post_id' => 3, 'composite_pair_a' => 1, 'composite_pair_b' => 2],
            ['post_id' => 3, 'composite_pair_a' => 3, 'composite_pair_b' => 4],
        ]);
});

/*
 * The owning side of this one association declares orphanRemoval, so Doctrine
 * holds its members privately owned: taking this record out of it deletes the
 * record rather than the join row. The save refuses by name before it writes
 * anything, and only when a member is actually dropped — the two cases below
 * are that pair. Every other case in this file rides the same arm over an
 * owning side that declares no orphanRemoval, which is the control.
 */
it('refuses by name a save that would drop a member of an owning collection declaring orphanRemoval, and writes nothing', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $sticker = inverseSideSeedStickers($entityManager);

    $select = Select::make('leaflets')->multiple()->preload()->relationship(titleAttribute: 'title');
    [$schema, $host] = inverseSideWire($select, $sticker);

    $schema->fill($sticker);
    $select->getOptions();

    expect($host->data['leaflets'])->toBe(['1', '2']);

    $recorder = inverseSideRecorder();
    $recorder->reset();

    $host->data['leaflets'] = ['1'];

    $refusal = null;

    try {
        $schema->getData();
    } catch (LogicException $logicException) {
        $refusal = $logicException->getMessage();
    }

    $entityManager->flush();

    expect($refusal)->toBe('[' . Leaflet::class . '::$stickers] declares orphanRemoval, so a member dropped from it is deleted rather than unlinked — deselecting one in the [leaflets] field would delete the record this form is saving rather than its join row. Put the field on the owning side of the association, or drop orphanRemoval.')
        ->and(inverseSideWrites($recorder->statements()))->toBe([])
        ->and(inverseSideStickerIds($entityManager))->toBe([1, 2])
        ->and(inverseSideLeafletRows($entityManager))->toBe([
            ['leaflet_id' => 1, 'sticker_id' => 1],
            ['leaflet_id' => 2, 'sticker_id' => 1],
            ['leaflet_id' => 3, 'sticker_id' => 2],
        ]);
});

it('writes the join row of a save that only adds to such a mapping, which drops nothing', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $sticker = inverseSideSeedStickers($entityManager);

    $select = Select::make('leaflets')->multiple()->preload()->relationship(titleAttribute: 'title');
    [$schema, $host] = inverseSideWire($select, $sticker);

    $schema->fill($sticker);
    $select->getOptions();

    $recorder = inverseSideRecorder();
    $recorder->reset();

    $host->data['leaflets'] = ['1', '2', '3'];

    expect($schema->getData())->toBe($sticker);

    $entityManager->flush();

    expect(inverseSideWrites($recorder->statements()))->toBe([
        'BEGIN',
        'INSERT INTO leaflet_sticker (leaflet_id, sticker_id) VALUES (?, ?)',
        'COMMIT',
    ])
        ->and(inverseSideStickerIds($entityManager))->toBe([1, 2])
        ->and(inverseSideLeafletRows($entityManager))->toBe([
            ['leaflet_id' => 1, 'sticker_id' => 1],
            ['leaflet_id' => 2, 'sticker_id' => 1],
            ['leaflet_id' => 3, 'sticker_id' => 1],
            ['leaflet_id' => 3, 'sticker_id' => 2],
        ]);
});

/*
 * The control: the owning side of the same association, which the inverse arm
 * must leave exactly as it was — the adder and remover write the collection
 * Doctrine persists, and nothing else is asked of the reader.
 */
it('saves the owning side of the same association statement for statement as before', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    inverseSideSeedTags($entityManager);

    $post = $entityManager->find(Post::class, 2);
    assert($post instanceof Post);

    $select = Select::make('tags')->multiple()->preload()->relationship(titleAttribute: 'name');
    [$schema, $host] = inverseSideWire($select, $post);

    $schema->fill($post);
    $select->getOptions();

    expect($host->data['tags'])->toBe(['1']);

    $recorder = inverseSideRecorder();
    $recorder->reset();

    $host->data['tags'] = ['2'];

    expect($schema->getData())->toBe($post);

    $entityManager->flush();

    expect($recorder->statements())->toBe([
        'SELECT t0_.id AS id_0, t0_.name AS name_1, t0_.parent_id AS parent_id_2 FROM Tag t0_ WHERE t0_.id IN (?)',
        'SELECT t0.id AS id_1, t0.heading AS heading_2, t0.body AS body_3, t0.position AS position_4, t0.post_id AS post_id_5, t0.author_id AS author_id_6 FROM PostSection t0 WHERE t0.post_id = ? ORDER BY t0.position ASC',
        'BEGIN',
        'DELETE FROM post_tag WHERE post_id = ? AND tag_id = ?',
        'INSERT INTO post_tag (post_id, tag_id) VALUES (?, ?)',
        'COMMIT',
    ])
        ->and(inverseSideTagRows($entityManager))->toBe([
            ['post_id' => 1, 'tag_id' => 1],
            ['post_id' => 2, 'tag_id' => 2],
            ['post_id' => 3, 'tag_id' => 2],
        ]);
});
