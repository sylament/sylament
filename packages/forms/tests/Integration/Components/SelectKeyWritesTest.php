<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components;

use Closure;
use Doctrine\ORM\EntityManagerInterface;
use Pest\Expectation;
use Sylament\Actions\Action;
use Sylament\Forms\Components\Select;
use Sylament\Forms\Components\TextInput;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Emblem;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Guild;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Post;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\PostSeal;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\PostTranslation;
use Sylament\Schemas\Exceptions\ValidationException;
use Sylament\Schemas\Schema;
use Sylament\Support\Tests\Doctrine\RecordingMiddleware;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/*
 * What a relationship select's writes do with an assigned key: its to-one arm
 * and its edit-option default refuse a changed key and put it back, its
 * create-option default refuses a key a stored or managed record already holds,
 * and an option created under a type that reads a key back in another spelling
 * is named by the spelling a read of its row hands back.
 */

/**
 * Two posts, the first carrying `en` and `de` and pointing its singular
 * translation at `en`, and a seal on the first. The identity map is cleared, so
 * nothing is managed until a case reads it.
 */
function selectKeyWritesSeed(EntityManagerInterface $entityManager): void
{
    $post = new Post();
    $post->setTitle('First');

    $other = new Post();
    $other->setTitle('Second');

    $entityManager->persist($post);
    $entityManager->persist($other);
    $entityManager->flush();

    foreach (['en' => 'English', 'de' => 'German'] as $locale => $title) {
        $translation = new PostTranslation();
        $translation->setLocale($locale);
        $translation->setTitle($title);

        $post->addTranslation($translation);
    }

    $seal = new PostSeal();
    $seal->setNote('sealed');
    $seal->setPost($post);

    $entityManager->persist($seal);
    $entityManager->flush();

    $post->setTranslation($post->getTranslations()->first() ?: null);

    $entityManager->flush();
    $entityManager->clear();
}

/**
 * Bind a schema to a record through a `data`-bag host, validator-fed so
 * `getData()` runs the record check.
 *
 * @param list<object> $components
 *
 * @return array{Schema, FieldRenderHost}
 */
function selectKeyWritesWire(object $record, array $components): array
{
    $host = new FieldRenderHost();
    $host->setSchemaStateValidator(test()->getContainer()->get(ValidatorInterface::class));

    $schema = Schema::make($host)->statePath('data')->record($record)->components($components);

    $host->cacheSchema('schema', $schema);
    $schema->getComponents();

    return [$schema, $host];
}

/**
 * The errors a call refused with, or null when it answered.
 *
 * @return array<string, list<string>>|null
 */
function selectKeyWritesRefusal(Closure $call): ?array
{
    try {
        $call();
    } catch (ValidationException $validationException) {
        return $validationException->getErrors();
    }

    return null;
}

function selectKeyWritesRecorder(): RecordingMiddleware
{
    $recorder = test()->getContainer()->get(RecordingMiddleware::class);
    $recorder->reset();

    return $recorder;
}

/**
 * Every statement that is not a read, in order.
 *
 * @param list<string> $statements
 *
 * @return list<string>
 */
function selectKeyWritesWrites(array $statements): array
{
    return array_values(array_filter($statements, static fn (string $statement): bool => ! str_starts_with($statement, 'SELECT')));
}

/**
 * @return list<array<string, mixed>>
 */
function selectKeyWritesTranslationRows(EntityManagerInterface $entityManager): array
{
    return $entityManager->getConnection()->fetchAllAssociative('SELECT post_id, locale, title FROM PostTranslation ORDER BY post_id, locale');
}

it('refuses its to-one arm moving a record onto another post through the identifier association, and writes nothing on the next flush', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    selectKeyWritesSeed($entityManager);

    $translation = $entityManager->find(PostTranslation::class, ['post' => 1, 'locale' => 'en']);
    assert($translation instanceof PostTranslation);

    [$schema, $host] = selectKeyWritesWire($translation, [Select::make('post')->relationship('post', 'title'), TextInput::make('title')]);
    $schema->fill($translation);

    $host->data['post'] = '2';

    $recorder = selectKeyWritesRecorder();

    expect(selectKeyWritesRefusal(static fn (): object => $schema->getData()))->toBe(['data.post' => ['This value cannot be changed.']])
        ->and($translation->getPost()?->getId())->toBe(1);

    $entityManager->flush();

    expect(selectKeyWritesWrites($recorder->statements()))->toBe([])
        ->and(selectKeyWritesTranslationRows($entityManager))->toBe([
            ['post_id' => 1, 'locale' => 'de', 'title' => 'German'],
            ['post_id' => 1, 'locale' => 'en', 'title' => 'English'],
        ]);
});

it('writes the same post back through its to-one arm without refusing', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    selectKeyWritesSeed($entityManager);

    $translation = $entityManager->find(PostTranslation::class, ['post' => 1, 'locale' => 'en']);
    assert($translation instanceof PostTranslation);

    [$schema, $host] = selectKeyWritesWire($translation, [Select::make('post')->relationship('post', 'title'), TextInput::make('title')]);
    $schema->fill($translation);

    $host->data['title'] = 'English, edited';

    $recorder = selectKeyWritesRecorder();

    expect(selectKeyWritesRefusal(static fn (): object => $schema->getData()))->toBeNull();

    $entityManager->flush();

    expect(selectKeyWritesWrites($recorder->normalizedStatements()))->toBe([
        'BEGIN',
        'UPDATE PostTranslation SET title = ? WHERE post_id = ? AND locale = ?',
        'COMMIT',
    ]);
});

it("refuses a created option on a key a record already holds, under the option form's own field, and writes nothing", function (string $holder, array $lookups): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    selectKeyWritesSeed($entityManager);

    if ($holder === 'managed') {
        $entityManager->find(PostTranslation::class, ['post' => 1, 'locale' => 'de']);
    }

    $post = $entityManager->find(Post::class, 1);
    assert($post instanceof Post);

    $select = Select::make('translation')->relationship(titleAttribute: 'title');
    selectKeyWritesWire($post, [$select]);

    $created = new PostTranslation();
    $created->setPost($post);

    [$optionSchema] = selectKeyWritesWire($created, [TextInput::make('locale'), TextInput::make('title')]);
    $optionSchema->fill(['locale' => 'de', 'title' => 'German again']);

    $createOptionUsing = $select->getCreateOptionUsing();
    assert($createOptionUsing instanceof Closure);

    $recorder = selectKeyWritesRecorder();

    expect(selectKeyWritesRefusal(static fn (): mixed => $createOptionUsing($select, $optionSchema, Action::make('createOption')->data($optionSchema->getState()))))->toBe(['data.locale' => ['This value is already used.']])
        ->and($recorder->normalizedStatements())->toHaveCount(count($lookups))
        ->each(static fn (Expectation $statement, int $index): Expectation => $statement->toContain($lookups[$index]))
        ->and($entityManager->isOpen())->toBeTrue()
        ->and(selectKeyWritesTranslationRows($entityManager))->toHaveCount(2);
})->with([
    'stored' => ['stored', ['FROM PostTranslation p0 WHERE p0.post_id = ? AND p0.locale = ?']],
    'managed' => ['managed', []],
]);

it("refuses a created option whose whole key is a post that already has one, at the option form's own path", function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    selectKeyWritesSeed($entityManager);

    $post = $entityManager->find(Post::class, 1);
    assert($post instanceof Post);

    $select = Select::make('seal')->relationship(titleAttribute: 'note');
    selectKeyWritesWire($post, [$select]);

    $created = new PostSeal();
    $created->setPost($post);

    [$optionSchema] = selectKeyWritesWire($created, [TextInput::make('note')]);
    $optionSchema->fill(['note' => 'sealed again']);

    $createOptionUsing = $select->getCreateOptionUsing();
    assert($createOptionUsing instanceof Closure);

    $recorder = selectKeyWritesRecorder();

    expect(selectKeyWritesRefusal(static fn (): mixed => $createOptionUsing($select, $optionSchema, Action::make('createOption')->data($optionSchema->getState()))))->toBe(['data' => ['This value is already used.']])
        ->and(selectKeyWritesWrites($recorder->statements()))->toBe([])
        ->and($entityManager->isOpen())->toBeTrue()
        ->and($entityManager->getConnection()->fetchAllAssociative('SELECT post_id, note FROM PostSeal'))->toBe([
            ['post_id' => 1, 'note' => 'sealed'],
        ]);
});

it("refuses its edit-option default changing the selected record's key, and leaves the select naming the same record", function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    selectKeyWritesSeed($entityManager);

    $post = $entityManager->find(Post::class, 1);
    assert($post instanceof Post);

    $select = Select::make('translation')->relationship(titleAttribute: 'title');
    [$schema] = selectKeyWritesWire($post, [$select]);
    $schema->fill($post);

    $selected = $select->getSelectedRecord();
    assert($selected instanceof PostTranslation);

    [$optionSchema, $optionHost] = selectKeyWritesWire($selected, [TextInput::make('locale'), TextInput::make('title')]);
    $optionSchema->fill($selected);

    $optionHost->data['locale'] = 'fr';

    $updateOptionUsing = $select->getUpdateOptionUsing();
    assert($updateOptionUsing instanceof Closure);

    $recorder = selectKeyWritesRecorder();

    expect(selectKeyWritesRefusal(static fn (): mixed => $updateOptionUsing($select, $optionSchema, Action::make('editOption')->data($optionSchema->getState()))))->toBe(['data.locale' => ['This value cannot be changed.']])
        ->and($select->getState())->toBe('1~en')
        ->and($selected->getLocale())->toBe('en');

    $entityManager->flush();

    expect(selectKeyWritesWrites($recorder->statements()))->toBe([])
        ->and(selectKeyWritesTranslationRows($entityManager))->toBe([
            ['post_id' => 1, 'locale' => 'de', 'title' => 'German'],
            ['post_id' => 1, 'locale' => 'en', 'title' => 'English'],
        ]);

    $select->forgetCachedRelationshipOptions();

    expect(array_map(strval(...), array_keys($select->getOptions())))->toContain('1~en');
});

it('names a created option by the key its row reads back, selects it, and saves it onto the parent', function (string $typed, string $key, string $stored): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);

    $seeded = new Emblem();
    $seeded->setCode('SEEDED');
    $seeded->setLabel('Seeded');

    $guild = new Guild();
    $guild->setName('Masons');

    $entityManager->persist($seeded);
    $entityManager->persist($guild);
    $entityManager->flush();

    $select = Select::make('emblem')->relationship(titleAttribute: 'label');
    [$schema, $host] = selectKeyWritesWire($guild, [$select]);
    $schema->fill($guild);

    $created = new Emblem();
    $created->setCode($typed);
    $created->setLabel('Created');

    [$optionSchema] = selectKeyWritesWire($created, [TextInput::make('code'), TextInput::make('label')]);
    $optionSchema->fill(['code' => $typed, 'label' => 'Created']);

    $createOptionUsing = $select->getCreateOptionUsing();
    assert($createOptionUsing instanceof Closure);

    $createdKey = $createOptionUsing($select, $optionSchema, Action::make('createOption')->data($optionSchema->getState()));

    $select->forgetCachedRelationshipOptions();
    $host->data['emblem'] = $createdKey;

    expect($createdKey)->toBe($key)
        ->and(array_map(strval(...), array_keys($select->getOptions())))->toBe([$key, 'SEEDED'])
        ->and($select->getSelectedRecord()?->getCode())->toBe($key);

    $schema->getData();
    $entityManager->flush();

    expect($entityManager->getConnection()->fetchAllAssociative('SELECT name, emblem_code FROM Guild'))->toBe([
        ['name' => 'Masons', 'emblem_code' => $stored],
    ]);
})->with([
    'typed in another spelling' => ['new-1', 'NEW-1', 'new-1'],
    'typed as it reads back' => ['NEW-2', 'NEW-2', 'new-2'],
]);

it('refuses its to-one arm clearing the identifier association, and puts the post back', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    selectKeyWritesSeed($entityManager);

    $translation = $entityManager->find(PostTranslation::class, ['post' => 1, 'locale' => 'en']);
    assert($translation instanceof PostTranslation);

    [$schema, $host] = selectKeyWritesWire($translation, [Select::make('post')->relationship('post', 'title'), TextInput::make('title')]);
    $schema->fill($translation);

    $host->data['post'] = null;

    expect(selectKeyWritesRefusal(static fn (): object => $schema->getData()))->toBe(['data.post' => ['This value cannot be changed.']])
        ->and($translation->getPost()?->getId())->toBe(1);
});
