<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Forms\Components\TextInput;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Emblem;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Guild;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Post;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\PostTranslation;
use Sylament\Forms\Tests\Fixtures\Doctrine\RecordingMiddleware;
use Sylament\Schemas\Components\Group;
use Sylament\Schemas\Exceptions\ValidationException;
use Sylament\Schemas\Schema;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use function Sylament\Forms\Tests\renderToHtml;

/*
 * What a save does with the key of a record the form reaches through a to-one:
 * a singular relationship constructing a related record on a taken key is
 * refused under the field that carries the key, and a related record whose key
 * a singular relationship, a dotted field or application code changed is
 * refused and put back, so a later flush writes nothing.
 */

/**
 * Bind a schema to a record through a `data`-bag host, validator-fed so
 * `getData()` runs the record check.
 *
 * @param list<object> $components
 *
 * @return array{Schema, FieldRenderHost}
 */
function entangledKeyWritesWire(object $record, array $components): array
{
    $host = new FieldRenderHost();
    $host->setSchemaStateValidator(test()->getContainer()->get(ValidatorInterface::class));

    $schema = Schema::make($host)->statePath('data')->record($record)->components($components);

    $host->cacheSchema('schema', $schema);
    $schema->getComponents();

    return [$schema, $host];
}

/**
 * The errors `getData()` refused with, or null when it answered.
 *
 * @return array<string, list<string>>|null
 */
function entangledKeyWritesRefusal(Schema $schema): ?array
{
    try {
        $schema->getData();
    } catch (ValidationException $validationException) {
        return $validationException->getErrors();
    }

    return null;
}

function entangledKeyWritesRecorder(): RecordingMiddleware
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
function entangledKeyWritesWrites(array $statements): array
{
    return array_values(array_filter($statements, static fn (string $statement): bool => ! str_starts_with($statement, 'SELECT')));
}

/**
 * A post whose singular translation is `en`, beside a second translation `de`.
 * The identity map is cleared and the post read back.
 */
function entangledKeyWritesSeedPost(EntityManagerInterface $entityManager): Post
{
    $post = new Post();
    $post->setTitle('one');

    $entityManager->persist($post);
    $entityManager->flush();

    foreach (['en' => 'English', 'de' => 'German'] as $locale => $title) {
        $translation = new PostTranslation();
        $translation->setLocale($locale);
        $translation->setTitle($title);

        $post->addTranslation($translation);
    }

    $entityManager->flush();

    $post->setTranslation($post->getTranslations()->first() ?: null);

    $entityManager->flush();
    $entityManager->clear();

    $reloaded = $entityManager->find(Post::class, 1);
    assert($reloaded instanceof Post);

    return $reloaded;
}

/**
 * @return list<array<string, mixed>>
 */
function entangledKeyWritesRows(EntityManagerInterface $entityManager): array
{
    return $entityManager->getConnection()->fetchAllAssociative('SELECT post_id, locale, title FROM PostTranslation ORDER BY locale');
}

it('refuses a related record a singular relationship constructs on a taken key, under the field that carries the key', function (string $holder, string $typed, array $statements): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);

    $emblem = new Emblem();
    $emblem->setCode('GOLD');
    $emblem->setLabel('Gold');

    $guild = new Guild();
    $guild->setName('Masons');

    $entityManager->persist($emblem);
    $entityManager->persist($guild);
    $entityManager->flush();
    $entityManager->clear();

    if ($holder === 'managed') {
        $entityManager->find(Emblem::class, 'GOLD');
    }

    $guild = $entityManager->find(Guild::class, 1);
    assert($guild instanceof Guild);

    [$schema, $host] = entangledKeyWritesWire($guild, [
        Group::make()->relationship('emblem')->components([TextInput::make('code'), TextInput::make('label')]),
    ]);
    $schema->fill($guild);

    $host->data['emblem'] = ['code' => $typed, 'label' => 'Gold again'];

    $recorder = entangledKeyWritesRecorder();

    expect(entangledKeyWritesRefusal($schema))->toBe(['data.emblem.code' => ['This value is already used.']])
        ->and($recorder->statements())->toBe($statements)
        ->and($entityManager->isOpen())->toBeTrue()
        ->and($entityManager->getConnection()->fetchAllAssociative('SELECT code, label FROM Emblem'))->toBe([
            ['code' => 'gold', 'label' => 'Gold'],
        ])
        ->and(mb_substr_count(renderToHtml($schema), '<li class="sy-fo-field-error-message">This value is already used.</li>'))->toBe(1);
})->with([
    'stored' => ['stored', 'GOLD', ['SELECT t0.code AS code_1, t0.label AS label_2 FROM Emblem t0 WHERE t0.code = ?']],
    'managed' => ['managed', 'GOLD', []],
    'stored, typed in another spelling' => ['stored', 'gold', ['SELECT t0.code AS code_1, t0.label AS label_2 FROM Emblem t0 WHERE t0.code = ?']],
    'managed, typed in another spelling' => ['managed', 'gold', ['SELECT t0.code AS code_1, t0.label AS label_2 FROM Emblem t0 WHERE t0.code = ?']],
]);

it("refuses an existing related record's key half edited through a singular relationship, and writes nothing on the next flush", function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $post = entangledKeyWritesSeedPost($entityManager);

    [$schema, $host] = entangledKeyWritesWire($post, [
        Group::make([TextInput::make('locale'), TextInput::make('title')])->relationship('translation'),
    ]);
    $schema->fill($post);

    $host->data['translation']['locale'] = 'fr';

    $recorder = entangledKeyWritesRecorder();

    expect(entangledKeyWritesRefusal($schema))->toBe(['data.translation.locale' => ['This value cannot be changed.']])
        ->and($post->getTranslation()?->getLocale())->toBe('en')
        ->and(mb_substr_count(renderToHtml($schema), '<li class="sy-fo-field-error-message">This value cannot be changed.</li>'))->toBe(1);

    $entityManager->flush();

    expect(entangledKeyWritesWrites($recorder->statements()))->toBe([])
        ->and(entangledKeyWritesRows($entityManager))->toBe([
            ['post_id' => 1, 'locale' => 'de', 'title' => 'German'],
            ['post_id' => 1, 'locale' => 'en', 'title' => 'English'],
        ]);
});

it("refuses a dotted field writing another key into a loaded related record's identifier, and writes nothing on the next flush", function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $post = entangledKeyWritesSeedPost($entityManager);

    [$schema, $host] = entangledKeyWritesWire($post, [
        TextInput::make('title'),
        TextInput::make('translation.locale'),
        TextInput::make('translation.title'),
    ]);
    $schema->fill($post);

    $host->data['translation']['locale'] = 'de';

    $recorder = entangledKeyWritesRecorder();

    expect(entangledKeyWritesRefusal($schema))->toBe(['data.translation.locale' => ['This value cannot be changed.']])
        ->and($post->getTranslation()?->getLocale())->toBe('en');

    $entityManager->flush();

    expect(entangledKeyWritesWrites($recorder->statements()))->toBe([])
        ->and(entangledKeyWritesRows($entityManager))->toBe([
            ['post_id' => 1, 'locale' => 'de', 'title' => 'German'],
            ['post_id' => 1, 'locale' => 'en', 'title' => 'English'],
        ]);
});

/*
 * A recorded limit, not a refusal: the fill reads only the proxy's key, so the
 * related record is never loaded, the walk leaves it alone, and Doctrine drops a
 * write to an unloaded record's key on its own.
 */
it('leaves a dotted field writing only the key of a related record nobody loaded to Doctrine, which drops the write', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $post = entangledKeyWritesSeedPost($entityManager);

    [$schema, $host] = entangledKeyWritesWire($post, [TextInput::make('title'), TextInput::make('translation.locale')]);
    $schema->fill($post);

    $host->data['translation']['locale'] = 'fr';

    $recorder = entangledKeyWritesRecorder();

    expect(entangledKeyWritesRefusal($schema))->toBeNull();

    $entityManager->flush();

    expect(entangledKeyWritesWrites($recorder->statements()))->toBe([])
        ->and(entangledKeyWritesRows($entityManager))->toBe([
            ['post_id' => 1, 'locale' => 'de', 'title' => 'German'],
            ['post_id' => 1, 'locale' => 'en', 'title' => 'English'],
        ]);
});

it("refuses a save after application code re-keyed a related record the form never maps, at the schema's own path", function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $post = entangledKeyWritesSeedPost($entityManager);

    [$schema] = entangledKeyWritesWire($post, [TextInput::make('title')]);
    $schema->fill($post);

    $translation = $post->getTranslation();
    assert($translation instanceof PostTranslation);

    // Read first: a proxy whose only write is its key is never initialized, and
    // Doctrine drops that write on its own.
    $translation->getTitle();
    $translation->setLocale('qq');

    $recorder = entangledKeyWritesRecorder();

    expect(entangledKeyWritesRefusal($schema))->toBe(['data' => ['This value cannot be changed.']])
        ->and($translation->getLocale())->toBe('en');

    $entityManager->flush();

    expect(entangledKeyWritesWrites($recorder->statements()))->toBe([])
        ->and(entangledKeyWritesRows($entityManager))->toBe([
            ['post_id' => 1, 'locale' => 'de', 'title' => 'German'],
            ['post_id' => 1, 'locale' => 'en', 'title' => 'English'],
        ]);
});
