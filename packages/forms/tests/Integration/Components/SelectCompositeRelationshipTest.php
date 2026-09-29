<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components;

use Closure;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Sylament\Forms\Components\Select;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\CompositePair;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Greenhouse;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Planter;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Post;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\PostSeal;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\PostTranslation;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Tag;
use Sylament\Schemas\Exceptions\ValidationException;
use Sylament\Schemas\Schema;
use Sylament\Testing\Doctrine\RecordingMiddleware;
use Sylament\Testing\Doctrine\TestPlatform;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use function Sylament\Forms\Tests\renderToHtml;

/*
 * Select::relationship() where the target is keyed by more than one field — a
 * scalar pair, a derived identity and a single derived identity. Everything the
 * field speaks is the record key: the option value, the filled state, the
 * selection it resolves and the labels it asks back for. The three query sites
 * go through IdentifierConstraint now, so each one is pinned twice: once for a
 * composite target and once for the single-identifier text, which did not move.
 */

/**
 * Bind a field to a record instance through a `data`-bag host, keyed and
 * validator-fed so `getData()` can run its validation pass.
 *
 * @return array{Schema, FieldRenderHost}
 */
function compositeSelectWire(object $field, object $record): array
{
    $host = new FieldRenderHost();
    $host->setSchemaStateValidator(test()->getContainer()->get(ValidatorInterface::class));

    $schema = Schema::make($host)->statePath('data')->record($record)->components([$field]);

    $host->cacheSchema('schema', $schema);
    $schema->getComponents();

    return [$schema, $host];
}

/**
 * Two posts and the three composite flavours around them: pairs `1~2`, `3~4` and
 * `5~6` (the last parented to the first), translations `1~en`, `1~zh=20Hans` and
 * `1~a=2Eb` under the first post and `2~de` under the second. The first post
 * holds `pair = 1~2`, `translation = 1~en`, pairs `1~2` and `3~4` and linked
 * translations `1~en` and `1~zh=20Hans`.
 *
 * No seal is seeded: the create-option cases below assert an empty `PostSeal`
 * table, and {@see compositeSelectSealPost()} is what puts a row in it.
 *
 * @return array{Post, Post}
 */
function compositeSelectSeed(EntityManagerInterface $entityManager): array
{
    $post = new Post();
    $post->setTitle('First');

    $other = new Post();
    $other->setTitle('Second');

    $entityManager->persist($post);
    $entityManager->persist($other);
    $entityManager->flush();

    $first = new CompositePair(1, 2, 'one-two');
    $second = new CompositePair(3, 4, 'three-four');
    $third = new CompositePair(5, 6, 'five-six');
    $third->setParent($first);

    $entityManager->persist($first);
    $entityManager->persist($second);
    $entityManager->persist($third);

    $position = 1;

    foreach (['en' => 'English', 'zh Hans' => 'Chinese', 'a.b' => 'Dotted'] as $locale => $title) {
        $translation = new PostTranslation();
        $translation->setLocale($locale);
        $translation->setTitle($title);
        $translation->setPosition($position);

        $post->addTranslation($translation);

        $position++;
    }

    $foreign = new PostTranslation();
    $foreign->setLocale('de');
    $foreign->setTitle('German');

    $other->addTranslation($foreign);

    $entityManager->flush();

    $post->setPair($first);
    $post->setTranslation($post->getTranslations()->first() ?: null);
    $post->addPair($first);
    $post->addPair($second);
    $post->addLinkedTranslation($post->getTranslations()->first() ?: null);
    $post->addLinkedTranslation($post->getTranslations()->get(1) ?: null);

    $entityManager->flush();

    return [$post, $other];
}

function compositeSelectSealPost(EntityManagerInterface $entityManager, Post $post): PostSeal
{
    $seal = new PostSeal();
    $seal->setNote('sealed');
    $seal->setPost($post);

    $entityManager->persist($seal);
    $entityManager->flush();

    return $seal;
}

function compositeSelectRecorder(): RecordingMiddleware
{
    return test()->getContainer()->get(RecordingMiddleware::class);
}

it('offers a record key per option and fills the stored one, for a scalar pair and a derived identity', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post] = compositeSelectSeed($entityManager);

    $pair = Select::make('pair')->relationship(titleAttribute: 'label');
    [$pairSchema, $pairHost] = compositeSelectWire($pair, $post);
    $pairSchema->fill($post);

    $translation = Select::make('translation')->relationship(titleAttribute: 'title');
    [$translationSchema, $translationHost] = compositeSelectWire($translation, $post);
    $translationSchema->fill($post);

    expect(array_keys($pair->getOptions()))->toBe(['5~6', '1~2', '3~4'])
        ->and($pairHost->data['pair'])->toBe('1~2')
        ->and(array_keys($translation->getOptions()))->toBe(['1~zh=20Hans', '1~a=2Eb', '1~en', '2~de'])
        ->and($translationHost->data['translation'])->toBe('1~en');
});

/*
 * A single derived identity is one identifier field that happens to be an
 * association, so its key is its target's verbatim — no separator, and PHP
 * coerces the numeric string back to an int array key exactly as a single-`id`
 * target's does. It is the flavour `isIdentifierComposite` misses.
 */
it('keys a single derived identity by its target, with no separator', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post] = compositeSelectSeed($entityManager);
    compositeSelectSealPost($entityManager, $post);

    $select = Select::make('seal')->relationship(titleAttribute: 'note');
    [$schema, $host] = compositeSelectWire($select, $post);

    $post->setSeal($entityManager->find(PostSeal::class, ['post' => 1]));
    $schema->fill($post);

    expect(array_keys($select->getOptions()))->toBe([1])
        ->and($host->data['seal'])->toBe('1');
});

it('renders a composite option value verbatim and marks the selected one', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post] = compositeSelectSeed($entityManager);

    $select = Select::make('pair')->relationship(titleAttribute: 'label');
    [$schema] = compositeSelectWire($select, $post);
    $schema->fill($post);

    $html = renderToHtml($schema);

    expect($html)->toContain('<option value="5~6"')
        ->toContain('<option value="3~4"')
        ->toContain('<option value="1~2" selected');
});

it('resolves the selected record for a scalar pair, a derived identity and a single derived identity', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post] = compositeSelectSeed($entityManager);
    compositeSelectSealPost($entityManager, $post);

    $pair = Select::make('pair')->relationship(titleAttribute: 'label');
    [, $pairHost] = compositeSelectWire($pair, $post);
    $pairHost->data['pair'] = '3~4';

    $translation = Select::make('translation')->relationship(titleAttribute: 'title');
    [, $translationHost] = compositeSelectWire($translation, $post);
    $translationHost->data['translation'] = '1~zh=20Hans';

    $seal = Select::make('seal')->relationship(titleAttribute: 'note');
    [, $sealHost] = compositeSelectWire($seal, $post);
    $sealHost->data['seal'] = '1';

    $selectedPair = $pair->getSelectedRecord();
    $selectedTranslation = $translation->getSelectedRecord();
    $selectedSeal = $seal->getSelectedRecord();

    expect($selectedPair)->toBeInstanceOf(CompositePair::class)
        ->and($selectedPair instanceof CompositePair ? $selectedPair->getLabel() : null)->toBe('three-four')
        ->and($selectedTranslation)->toBeInstanceOf(PostTranslation::class)
        ->and($selectedTranslation instanceof PostTranslation ? $selectedTranslation->getLocale() : null)->toBe('zh Hans')
        ->and($selectedSeal)->toBeInstanceOf(PostSeal::class)
        ->and($selectedSeal instanceof PostSeal ? $selectedSeal->getNote() : null)->toBe('sealed');
});

/*
 * A key the codec refuses never reaches the database: the predicate is null and
 * the lookup answers null without binding anything. Recorded statements are the
 * pin — a platform's own leniency is not one.
 */
it('resolves no record for a malformed selected key and issues no statement', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post] = compositeSelectSeed($entityManager);

    $recorder = compositeSelectRecorder();

    $answered = [];

    foreach (['1', '1~en~x', '5abc', '05', '1~a=2eb', '1~', '~en', '1~zh=20Hans~'] as $state) {
        $select = Select::make('translation')->relationship(titleAttribute: 'title');
        [, $host] = compositeSelectWire($select, $post);
        $host->data['translation'] = $state;

        $recorder->reset();

        $answered[$state] = [$select->getSelectedRecord(), $recorder->statements()];
    }

    expect($answered)->toBe([
        '1' => [null, []],
        '1~en~x' => [null, []],
        '5abc' => [null, []],
        '05' => [null, []],
        '1~a=2eb' => [null, []],
        '1~' => [null, []],
        '~en' => [null, []],
        '1~zh=20Hans~' => [null, []],
    ]);
});

/*
 * The escape grammar is `=[0-9A-F]{2}`, so lowercase is malformed only where the
 * escape carries a hex letter: `1~a=2eb` above is refused, while `1~zh=20hans`
 * is a well-formed key for the locale `zh hans` — asking for it is a real query.
 * It names no row under a case-sensitive collation; MySQL's `utf8mb4_0900_ai_ci`
 * equates it with the stored `zh Hans`, the key self-aliasing `record-key.md`
 * records, so there it resolves that one row.
 */
it('asks the database once for a well-formed key whose escape carries no hex letter', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post] = compositeSelectSeed($entityManager);

    $select = Select::make('translation')->relationship(titleAttribute: 'title');
    [, $host] = compositeSelectWire($select, $post);
    $host->data['translation'] = '1~zh=20hans';

    $recorder = compositeSelectRecorder();
    $recorder->reset();

    $selected = $select->getSelectedRecord();

    match (TestPlatform::current()) {
        TestPlatform::MySql => expect($selected)->not->toBeNull()
            ->and($selected instanceof PostTranslation ? $selected->getLocale() : null)->toBe('zh Hans'),
        TestPlatform::Sqlite, TestPlatform::PostgreSql => expect($selected)->toBeNull(),
    };

    expect($recorder->statements())->toHaveCount(1);
});

/*
 * The label list is raw client state, so an entry that is not a scalar or does
 * not decode is dropped and the rest of the batch is still answered — one bad
 * value in a multiple selection must not cost the labels of the good ones, and
 * a nested array must not be coerced into a key-shaped string on the way in.
 */
it('drops a malformed or non-scalar entry from the label list and keeps the good ones', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post] = compositeSelectSeed($entityManager);

    $select = Select::make('linkedTranslations')->relationship(titleAttribute: 'title')->multiple();
    compositeSelectWire($select, $post);

    $both = $select->getOptionLabelsFromRelationship(['1~en', '2~de']);
    ksort($both);

    expect($select->getOptionLabelsFromRelationship(['1~en', '5abc']))->toBe(['1~en' => 'English'])
        ->and($select->getOptionLabelsFromRelationship(['1~en', ['a'], null, '5abc']))->toBe(['1~en' => 'English'])
        ->and($both)->toBe([
            '1~en' => 'English',
            '2~de' => 'German',
        ]);
});

/*
 * One early return, after the scalar filter: a list holding nothing askable is
 * answered without a query, while a list of scalars that all fail to decode is
 * one `WHERE 1 = 0` — the `in()` predicate is what makes that empty answer, and
 * asking for it is the only shape that keeps the parameter bookkeeping honest.
 */
it('issues no statement for a label list with no scalar entry, and one where every scalar entry is malformed', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post] = compositeSelectSeed($entityManager);

    $select = Select::make('linkedTranslations')->relationship(titleAttribute: 'title')->multiple();
    compositeSelectWire($select, $post);

    $recorder = compositeSelectRecorder();
    $recorder->reset();

    $nonScalar = $select->getOptionLabelsFromRelationship([['a'], null]);
    $afterNonScalar = $recorder->statements();

    $malformed = $select->getOptionLabelsFromRelationship(['5abc', '05']);

    expect($nonScalar)->toBe([])
        ->and($afterNonScalar)->toBe([])
        ->and($malformed)->toBe([])
        ->and($recorder->statements())->toHaveCount(1);
});

it('excludes a composite record from both option arms under ignoreRecord', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    compositeSelectSeed($entityManager);

    $pair = $entityManager->find(CompositePair::class, ['a' => 1, 'b' => 2]);

    $options = Select::make('parent')->relationship(titleAttribute: 'label', ignoreRecord: true);
    compositeSelectWire($options, $pair);

    $search = Select::make('parent')->searchable(['label'])->relationship(titleAttribute: 'label', ignoreRecord: true);
    compositeSelectWire($search, $pair);

    // 'one-two' matches the term too, and is the row the flag takes out.
    expect(array_keys($options->getOptions()))->toBe(['5~6', '3~4'])
        ->and($search->getSearchResults('o'))->toBe(['3~4' => 'three-four']);
});

/*
 * A stored self-reference is legacy data the label arms must keep speaking, or
 * an untouched submission fails `Choice` against an option map that no longer
 * holds it — so the exclusion belongs to the option arms alone.
 */
it('still labels an excluded composite record through both label arms', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    compositeSelectSeed($entityManager);

    $pair = $entityManager->find(CompositePair::class, ['a' => 1, 'b' => 2]);

    $select = Select::make('parent')->relationship(titleAttribute: 'label', ignoreRecord: true);
    compositeSelectWire($select, $pair);
    $select->state('1~2');

    expect($select->getSelectedRecord())->toBe($pair)
        ->and($select->getOptionLabel())->toBe('one-two')
        ->and($select->getOptionLabelsFromRelationship(['1~2']))->toBe(['1~2' => 'one-two']);
});

/*
 * The three converted sites over a single-identifier target, as text: the
 * foundation is a rewrite of how the predicate is built, never of what it
 * compiles to for a one-field key.
 */
it('emits the single-identifier SQL text of all three converted sites', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post] = compositeSelectSeed($entityManager);

    $tag = new Tag();
    $tag->setName('root');

    $entityManager->persist($tag);
    $entityManager->flush();

    $recorder = compositeSelectRecorder();

    $selected = Select::make('author')->relationship(titleAttribute: 'name');
    [, $host] = compositeSelectWire($selected, $post);
    $host->data['author'] = '7';

    $recorder->reset();
    $selected->getSelectedRecord();
    $selectedStatements = $recorder->statements();

    $labels = Select::make('author')->relationship(titleAttribute: 'name');
    compositeSelectWire($labels, $post);

    $recorder->reset();
    $labels->getOptionLabelsFromRelationship(['7']);
    $labelStatements = $recorder->statements();

    $ignored = Select::make('parent')->relationship(titleAttribute: 'name', ignoreRecord: true);
    compositeSelectWire($ignored, $tag);

    $recorder->reset();
    $ignored->getOptions();
    $ignoredStatements = $recorder->statements();

    expect($selectedStatements)->toHaveCount(1)
        ->and($selectedStatements[0])->toContain('WHERE a0_.id = ?')
        ->and($labelStatements)->toHaveCount(1)
        ->and($labelStatements[0])->toContain('WHERE a0_.id IN (?)')
        ->and($ignoredStatements)->toHaveCount(1)
        ->and($ignoredStatements[0])->toContain('WHERE t0_.id <> ?');
});

/*
 * The to-one arm: one key in, one reference written onto the record. Every
 * other case reads a to-one selection; this is the one that saves one. The
 * clear() before the wire does not leave the map cold for the write — getData()
 * enumerates the options to validate the submitted key, hydrating the very row
 * the reference then resolves to.
 */
it('writes a single composite selection back onto its record', function (string $name, string $titleAttribute, string $key, Closure $label, string $expected): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post] = compositeSelectSeed($entityManager);
    compositeSelectSealPost($entityManager, $post);
    $entityManager->clear();

    $other = $entityManager->find(Post::class, 2);

    [$schema, $host] = compositeSelectWire(Select::make($name)->relationship(titleAttribute: $titleAttribute), $other);
    $schema->fill($other);

    expect($host->data[$name])->toBeNull();

    $host->data[$name] = $key;

    expect($schema->getData())->toBe($other);

    $entityManager->flush();
    $entityManager->clear();

    $reloaded = $entityManager->find(Post::class, 2);

    [$reloadedSchema, $reloadedHost] = compositeSelectWire(Select::make($name)->relationship(titleAttribute: $titleAttribute), $reloaded);
    $reloadedSchema->fill($reloaded);

    expect($reloadedHost->data[$name])->toBe($key)
        ->and($reloaded instanceof Post ? $label($reloaded) : null)->toBe($expected);
})->with([
    'a scalar pair' => ['pair', 'label', '3~4', static fn (Post $post): ?string => $post->getPair()?->getLabel(), 'three-four'],
    'a derived identity' => ['translation', 'title', '1~zh=20Hans', static fn (Post $post): ?string => $post->getTranslation()?->getTitle(), 'Chinese'],
    'a single derived identity' => ['seal', 'note', '1', static fn (Post $post): ?string => $post->getSeal()?->getNote(), 'sealed'],
]);

it('writes a multiple selection of composite keys and reads the same rows back', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post] = compositeSelectSeed($entityManager);

    $select = Select::make('linkedTranslations')->relationship(titleAttribute: 'title')->multiple();
    [$schema, $host] = compositeSelectWire($select, $post);
    $schema->fill($post);

    expect($host->data['linkedTranslations'])->toBe(['1~en', '1~zh=20Hans']);

    $host->data['linkedTranslations'] = ['1~en', '2~de'];

    expect($schema->getData())->toBe($post);

    $entityManager->flush();
    $entityManager->clear();

    $reloaded = $entityManager->find(Post::class, 1);

    $locales = array_map(
        static fn (PostTranslation $translation): string => $translation->getLocale(),
        $reloaded instanceof Post ? $reloaded->getLinkedTranslations()->toArray() : [],
    );

    sort($locales);

    expect($locales)->toBe(['de', 'en']);
});

/*
 * No to-many relation write moves a primary key. Writing this collection would
 * re-point a child whose owner is half its own identifier — Doctrine would
 * happily re-key the row and every key the client holds for it would go stale —
 * so the write is refused before a single reference is resolved.
 */
it('refuses a to-many write over a one-to-many whose mapped-by is half the target identifier', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post] = compositeSelectSeed($entityManager);

    $select = Select::make('translations')->relationship(titleAttribute: 'title')->multiple();
    [$schema, $host] = compositeSelectWire($select, $post);
    $schema->fill($post);

    $host->data['translations'] = ['2~de'];

    $recorder = compositeSelectRecorder();
    $recorder->reset();

    expect(fn (): object => $schema->getData())
        ->toThrow(LogicException::class, 'is part of the identifier of')
        // Validation reads labels for the submitted key; the refusal itself runs
        // ahead of the reference loop, so nothing writes.
        ->and(array_filter($recorder->statements(), static fn (string $sql): bool => ! str_starts_with($sql, 'SELECT')))->toBe([]);

    $foreign = $entityManager->find(PostTranslation::class, ['post' => 2, 'locale' => 'de']);

    expect($foreign instanceof PostTranslation ? $foreign->getPost()?->getId() : null)->toBe(2)
        ->and($post->getTranslations())->toHaveCount(3);
});

it('writes the same target through its many-to-many without refusing', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post] = compositeSelectSeed($entityManager);

    $select = Select::make('linkedTranslations')->relationship(titleAttribute: 'title')->multiple();
    [$schema, $host] = compositeSelectWire($select, $post);
    $schema->fill($post);

    $host->data['linkedTranslations'] = ['2~de'];

    expect($schema->getData())->toBe($post)
        ->and($post->getLinkedTranslations())->toHaveCount(1);
});

/*
 * The create-option default mints the key before it writes. A record whose
 * assigned identifier is still incomplete would commit a row no option could
 * ever name, so it is refused while the table is untouched — the create form is
 * missing an identifier field, and the message says so.
 */
it('refuses a created option whose identifier is incomplete before it writes anything', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post] = compositeSelectSeed($entityManager);

    $select = Select::make('seal')->relationship(titleAttribute: 'note');
    compositeSelectWire($select, $post);

    $incomplete = new PostSeal();
    $incomplete->setNote('orphan');

    $schema = Schema::make(new FieldRenderHost())->record($incomplete);
    $createOptionUsing = $select->getCreateOptionUsing();

    expect($createOptionUsing)->toBeInstanceOf(Closure::class)
        ->and(static fn (): string => $createOptionUsing instanceof Closure ? $createOptionUsing($select, $schema) : '')
        ->toThrow(LogicException::class, 'whose identifier is incomplete, so no option could name it');

    $entityManager->flush();

    expect($entityManager->getRepository(PostSeal::class)->count([]))->toBe(0);
});

it('refuses a created option whose key half is still blank after its prePersist callback ran, writing nothing', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);

    $greenhouse = new Greenhouse();
    $greenhouse->setName('North');

    $entityManager->persist($greenhouse);
    $entityManager->flush();

    $select = Select::make('planters')->relationship(titleAttribute: 'crop')->multiple();
    compositeSelectWire($select, $greenhouse);

    $planter = new Planter();
    $planter->setBed('north');
    $planter->setCrop('basil');

    $schema = Schema::make(new FieldRenderHost())->record($planter);
    $createOptionUsing = $select->getCreateOptionUsing();

    expect($createOptionUsing)->toBeInstanceOf(Closure::class)
        ->and(static fn (): string => $createOptionUsing instanceof Closure ? $createOptionUsing($select, $schema) : '')
        ->toThrow(LogicException::class, sprintf('The [planters] select created a [%s] whose identifier is incomplete, so no option could name it.', Planter::class));

    $entityManager->flush();

    expect($entityManager->getConnection()->fetchAllAssociative('SELECT bed, slot, crop, season FROM Planter'))->toBe([])
        ->and($entityManager->isOpen())->toBeTrue();
});

it('creates an option that carries every identifier field and hands back its key', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [, $other] = compositeSelectSeed($entityManager);

    $select = Select::make('seal')->relationship(titleAttribute: 'note');
    compositeSelectWire($select, $other);

    $complete = new PostSeal();
    $complete->setNote('second');
    $complete->setPost($other);

    $schema = Schema::make(new FieldRenderHost())->record($complete);
    $createOptionUsing = $select->getCreateOptionUsing();

    expect($createOptionUsing)->toBeInstanceOf(Closure::class)
        ->and($createOptionUsing instanceof Closure ? $createOptionUsing($select, $schema) : null)->toBe('2')
        ->and($entityManager->getRepository(PostSeal::class)->count([]))->toBe(1);
});

it('reads the labels of a composite selection larger than one batch in batches', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post] = compositeSelectSeed($entityManager);

    $select = Select::make('linkedTranslations')->relationship(titleAttribute: 'title')->multiple();
    compositeSelectWire($select, $post);

    $recorder = compositeSelectRecorder();
    $recorder->reset();

    $labels = $select->getOptionLabelsFromRelationship(['1~en', ...array_map(static fn (int $index): string => "9~l{$index}", range(1, 1200))]);

    expect($labels)->toBe(['1~en' => 'English'])
        ->and(array_map(static fn (string $sql): int => mb_substr_count($sql, '?'), $recorder->statements()))->toBe([1000, 1000, 402]);
});

it('answers a multiple select past maxItems() with the count alone, reading no label', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post] = compositeSelectSeed($entityManager);

    $select = Select::make('linkedTranslations')->relationship(titleAttribute: 'title')->multiple()->maxItems(2);
    [$schema, $host] = compositeSelectWire($select, $post);
    $schema->fill($post);
    $host->data['linkedTranslations'] = ['1~en', '2~de', '9~xx'];

    $recorder = compositeSelectRecorder();
    $recorder->reset();

    try {
        $schema->getData();
        test()->fail('Expected the selection to be refused.');
    } catch (ValidationException $validationException) {
        expect($validationException->getErrors())->toBe(['data.linkedTranslations' => ['This collection should contain 2 elements or less.']]);
    }

    expect($recorder->statements())->toBe([]);
});

it('answers a forged key in a composite multiple select with the membership violation alone', function (?int $maxItems): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post] = compositeSelectSeed($entityManager);

    $select = Select::make('linkedTranslations')->relationship(titleAttribute: 'title')->multiple();

    if ($maxItems !== null) {
        $select->maxItems($maxItems);
    }

    [$schema, $host] = compositeSelectWire($select, $post);
    $schema->fill($post);
    $host->data['linkedTranslations'] = ['1~en', '9~xx'];

    try {
        $schema->getData();
        test()->fail('Expected the selection to be refused.');
    } catch (ValidationException $validationException) {
        expect($validationException->getErrors())->toBe(['data.linkedTranslations' => ['One or more of the given values is invalid.']]);
    }
})->with([
    'with no maxItems()' => [null],
    'with maxItems() at the submitted count' => [2],
]);

it('answers a single-identifier multiple select past maxItems() with the count and the membership violation', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post] = compositeSelectSeed($entityManager);

    $tags = [];

    foreach (['red', 'green'] as $name) {
        $tag = new Tag();
        $tag->setName($name);

        $entityManager->persist($tag);
        $tags[] = $tag;
    }

    $entityManager->flush();

    $select = Select::make('tags')->relationship(titleAttribute: 'name')->multiple()->maxItems(2);
    [$schema, $host] = compositeSelectWire($select, $post);
    $schema->fill($post);
    $host->data['tags'] = [(string) $tags[0]->getId(), (string) $tags[1]->getId(), '999'];

    try {
        $schema->getData();
        test()->fail('Expected the selection to be refused.');
    } catch (ValidationException $validationException) {
        expect($validationException->getErrors())->toBe(['data.tags' => [
            'This collection should contain 2 elements or less.',
            'One or more of the given values is invalid.',
        ]]);
    }
});
