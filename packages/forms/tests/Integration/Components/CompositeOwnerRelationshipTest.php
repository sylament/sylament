<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use Sylament\Forms\Components\CheckboxList;
use Sylament\Forms\Components\Repeater;
use Sylament\Forms\Components\Select;
use Sylament\Forms\Components\TextInput;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\CompositePair;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Post;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\PostTranslation;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\TranslationMark;
use Sylament\Schemas\Schema;
use Sylament\Support\Tests\Doctrine\RecordingMiddleware;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/*
 * The relation tier from the other side: the RECORD a relation-backed field is
 * bound to is keyed by two columns, rather than the target it offers. Forms
 * reads the owner's collection off the record through the property accessor and
 * its options off the whole target table, so nothing here scopes a query to the
 * owner — the last case is the characterization that pins exactly that, and the
 * rest are the writes it has to keep making: both foreign-key columns of a
 * re-pointed child, a repeater's added, edited and removed item, and a to-one
 * selection saved from a record of two identifier fields.
 */

/**
 * A post with two translations — the composite owners — and three marks: two
 * under `1~en` and one under `1~zh=20Hans`, so a write can move a child between
 * two owners whose foreign key is two columns wide.
 *
 * @return array{PostTranslation, PostTranslation}
 */
function compositeOwnerSeed(EntityManagerInterface $entityManager): array
{
    $post = new Post();
    $post->setTitle('one');

    $entityManager->persist($post);
    $entityManager->flush();

    $translations = [];
    $position = 0;

    foreach (['en' => 'English', 'zh Hans' => 'Chinese'] as $locale => $title) {
        $translation = new PostTranslation();
        $translation->setLocale($locale);
        $translation->setTitle($title);
        $translation->setPosition($position++);

        $post->addTranslation($translation);

        $translations[] = $translation;
    }

    $entityManager->flush();

    foreach ([[$translations[0], 'first', 0], [$translations[0], 'second', 1], [$translations[1], 'foreign', 0]] as [$owner, $body, $order]) {
        $mark = new TranslationMark();
        $mark->setBody($body);
        $mark->setPosition($order);

        $owner->addMark($mark);
    }

    $entityManager->flush();

    return [$translations[0], $translations[1]];
}

/**
 * The same three marks over two composite owners hanging off DIFFERENT posts —
 * `1~en` and `2~zh=20Hans` — so a move between them changes both foreign-key
 * columns rather than only the locale, and an expectation reading both columns
 * back discriminates. (Doctrine writes every join column of a changed to-one in
 * one UPDATE, so what the second post buys is discriminance, not protection
 * against a half-written key.) It is a seed of its own rather than a widening of
 * {@see compositeOwnerSeed()} because the repeater case reads the untouched
 * foreign mark's stored pair, which the second post would move.
 *
 * @return array{PostTranslation, PostTranslation}
 */
function compositeOwnerSeedAcrossPosts(EntityManagerInterface $entityManager): array
{
    $posts = [];

    foreach (['one', 'two'] as $title) {
        $post = new Post();
        $post->setTitle($title);

        $entityManager->persist($post);

        $posts[] = $post;
    }

    $entityManager->flush();

    $translations = [];
    $position = 0;

    foreach (['en' => 'English', 'zh Hans' => 'Chinese'] as $locale => $title) {
        $translation = new PostTranslation();
        $translation->setLocale($locale);
        $translation->setTitle($title);
        $translation->setPosition($position);

        $posts[$position]->addTranslation($translation);

        $translations[] = $translation;

        $position++;
    }

    $entityManager->flush();

    foreach ([[$translations[0], 'first', 0], [$translations[0], 'second', 1], [$translations[1], 'foreign', 0]] as [$owner, $body, $order]) {
        $mark = new TranslationMark();
        $mark->setBody($body);
        $mark->setPosition($order);

        $owner->addMark($mark);
    }

    $entityManager->flush();

    return [$translations[0], $translations[1]];
}

/**
 * Three pairs, the last unparented, so a to-one selection saved from a composite
 * record has two candidates and one writer.
 */
function compositeOwnerSeedPairs(EntityManagerInterface $entityManager): CompositePair
{
    $five = new CompositePair(5, 6, 'five-six');

    $entityManager->persist(new CompositePair(1, 2, 'one-two'));
    $entityManager->persist(new CompositePair(3, 4, 'three-four'));
    $entityManager->persist($five);
    $entityManager->flush();

    return $five;
}

/**
 * Bind a field to a record through a `data`-bag host, validator-fed so
 * `getData()` runs its validation pass.
 *
 * @return array{Schema, FieldRenderHost}
 */
function compositeOwnerWire(object $field, object $record): array
{
    $host = new FieldRenderHost();
    $host->setSchemaStateValidator(test()->getContainer()->get(ValidatorInterface::class));

    $schema = Schema::make($host)->statePath('data')->record($record)->components([$field]);

    $host->cacheSchema('schema', $schema);
    $schema->getComponents();

    return [$schema, $host];
}

/**
 * Every mark row as the database holds it, both foreign-key columns included —
 * read through the connection rather than the identity map, so a cleared manager
 * cannot answer from memory.
 *
 * @return list<array<string, mixed>>
 */
function compositeOwnerMarkRows(EntityManagerInterface $entityManager): array
{
    return $entityManager->getConnection()->fetchAllAssociative(
        'SELECT id, body, position, translation_post_id, translation_locale FROM TranslationMark ORDER BY id',
    );
}

function compositeOwnerRecorder(): RecordingMiddleware
{
    return test()->getContainer()->get(RecordingMiddleware::class);
}

it("fills a to-many relation-backed field from a record keyed by two columns and writes both of a moved child's key columns", function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$english] = compositeOwnerSeedAcrossPosts($entityManager);

    $list = CheckboxList::make('marks')->relationship(titleAttribute: 'body');
    [$schema, $host] = compositeOwnerWire($list, $english);
    $schema->fill($english);

    expect($list->getOptions())->toBe([1 => 'first', 2 => 'second', 3 => 'foreign'])
        ->and($host->data['marks'])->toBe(['1', '2'])
        // Mark 3 starts under the other post's Chinese translation, so both of
        // its key columns have to move for it to land here.
        ->and(compositeOwnerMarkRows($entityManager)[2])
        ->toBe(['id' => 3, 'body' => 'foreign', 'position' => 0, 'translation_post_id' => 2, 'translation_locale' => 'zh Hans']);

    $host->data['marks'] = ['1', '3'];

    expect($schema->getData())->toBe($english);

    $entityManager->flush();
    $entityManager->clear();

    expect(compositeOwnerMarkRows($entityManager))->toBe([
        ['id' => 1, 'body' => 'first', 'position' => 0, 'translation_post_id' => 1, 'translation_locale' => 'en'],
        ['id' => 3, 'body' => 'foreign', 'position' => 0, 'translation_post_id' => 1, 'translation_locale' => 'en'],
    ]);
});

it('runs a relationship repeater on a composite-owner record through an added, an edited and a removed item', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$english] = compositeOwnerSeed($entityManager);

    $repeater = Repeater::make('marks')->relationship()->orderColumn('position')->components([TextInput::make('body')]);
    [$schema, $host] = compositeOwnerWire($repeater, $english);
    $schema->fill($english);

    expect(array_keys($host->data['marks']))->toBe(['record-1', 'record-2']);

    $host->data['marks']['record-1']['body'] = 'first, edited';

    $before = array_keys($host->data['marks']);
    $repeater->getAction($repeater->getAddActionName())?->call();
    $added = (string) array_first(array_diff(array_map(strval(...), array_keys($host->data['marks'])), array_map(strval(...), $before)));

    $host->data['marks'][$added]['body'] = 'third';

    unset($host->data['marks']['record-2']);

    expect($schema->getData())->toBe($english);

    $entityManager->flush();
    $entityManager->clear();

    expect(compositeOwnerMarkRows($entityManager))->toBe([
        ['id' => 1, 'body' => 'first, edited', 'position' => 0, 'translation_post_id' => 1, 'translation_locale' => 'en'],
        ['id' => 3, 'body' => 'foreign', 'position' => 0, 'translation_post_id' => 1, 'translation_locale' => 'zh Hans'],
        ['id' => 4, 'body' => 'third', 'position' => 1, 'translation_post_id' => 1, 'translation_locale' => 'en'],
    ]);
});

it('writes a to-one selection back onto a record of two identifier fields', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $five = compositeOwnerSeedPairs($entityManager);

    $select = Select::make('parent')->relationship(titleAttribute: 'label');
    [$schema, $host] = compositeOwnerWire($select, $five);
    $schema->fill($five);

    expect($host->data['parent'])->toBeNull();

    $host->data['parent'] = '3~4';

    expect($schema->getData())->toBe($five);

    $entityManager->flush();
    $entityManager->clear();

    $reloaded = $entityManager->find(CompositePair::class, ['a' => 5, 'b' => 6]);

    expect($reloaded instanceof CompositePair ? $reloaded->getParent()?->getLabel() : null)->toBe('three-four')
        ->and($entityManager->getConnection()->fetchAssociative('SELECT parent_a, parent_b FROM CompositePair WHERE a = 5 AND b = 6'))
        ->toBe(['parent_a' => 3, 'parent_b' => 4]);
});

/*
 * The characterization, and the reason this package needs no owner-side code:
 * the option query is the whole target table, and the collection is read and
 * written in memory through the property accessor. So no statement a fill or a
 * save issues carries the owner alias the reader's relationship queries mint,
 * and none of them correlates a subselect back to the owner at all.
 */
it('scopes no query to the owner on any arm of a relation-backed field over a composite-owner record', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$english] = compositeOwnerSeed($entityManager);
    $five = compositeOwnerSeedPairs($entityManager);

    $recorder = compositeOwnerRecorder();

    $list = CheckboxList::make('marks')->relationship(titleAttribute: 'body');
    [$listSchema, $listHost] = compositeOwnerWire($list, $english);

    $recorder->reset();
    $list->getOptions();
    $optionStatements = $recorder->statements();

    $recorder->reset();

    $listSchema->fill($english);
    $listHost->data['marks'] = ['1', '3'];
    $listSchema->getData();

    $repeater = Repeater::make('marks')->relationship()->orderColumn('position')->components([TextInput::make('body')]);
    [$repeaterSchema] = compositeOwnerWire($repeater, $english);
    $repeaterSchema->fill($english);
    $repeaterSchema->getData();

    $select = Select::make('parent')->relationship(titleAttribute: 'label');
    [$selectSchema, $selectHost] = compositeOwnerWire($select, $five);
    $selectSchema->fill($five);
    $selectHost->data['parent'] = '3~4';
    $selectSchema->getData();

    $entityManager->flush();

    $statements = [...$optionStatements, ...$recorder->statements()];

    // The narrowing is asserted over the reads themselves rather than over an
    // alias name: `sy_owner` is a DQL identification variable and never reaches
    // the driver, so a filter for it could not fail whatever the field did. What
    // can fail is that no read carries a `WHERE` at all — an owner-scoped arm
    // would have to, through a correlated subselect or through the two
    // foreign-key columns — and that no statement carries a subselect.
    expect($optionStatements)->toHaveCount(1)
        ->and($optionStatements[0])->toContain('FROM TranslationMark')
        ->and($optionStatements[0])->not->toContain('WHERE')
        ->and(array_filter($statements, static fn (string $sql): bool => str_starts_with($sql, 'SELECT') && str_contains($sql, 'WHERE')))->toBe([])
        ->and(array_filter($statements, static fn (string $sql): bool => str_contains($sql, 'EXISTS')))->toBe([]);
});

/*
 * The same characterization one level up, where the owner parameter would be
 * named: the builder the option arm hands its modifier is the target table
 * alone — one alias, no owner alias beside it, and not one bound parameter — so
 * there is nothing for a `sylament_relationship_owner` to be bound into.
 */
it('hands the option arm a builder carrying one alias and no bound parameter', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$english] = compositeOwnerSeed($entityManager);

    $captured = [];

    $list = CheckboxList::make('marks')->relationship(
        titleAttribute: 'body',
        modifyQueryUsing: static function (QueryBuilder $query) use (&$captured): QueryBuilder {
            $captured[] = [
                $query->getDQL(),
                $query->getAllAliases(),
                array_map(static fn (Parameter $parameter): string => $parameter->getName(), $query->getParameters()->toArray()),
            ];

            return $query;
        },
    );

    compositeOwnerWire($list, $english);
    $list->getOptions();

    expect($captured)->toBe([[
        'SELECT record FROM ' . TranslationMark::class . ' record',
        ['record'],
        [],
    ]]);
});
