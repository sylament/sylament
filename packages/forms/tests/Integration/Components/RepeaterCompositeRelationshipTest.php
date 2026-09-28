<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components;

use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Sylament\Forms\Components\Repeater;
use Sylament\Forms\Components\TextInput;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Post;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\PostTranslation;
use Sylament\Schemas\Exceptions\ValidationException;
use Sylament\Schemas\Schema;
use Sylament\Testing\Doctrine\RecordingMiddleware;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/*
 * Repeater::relationship() over children whose identifier is an association plus
 * a scalar. Every item key here is a real record key of two fields, so the
 * escaping the codec applies is what keeps the dotted state path addressable,
 * and every write is diffed and flushed by both key columns. The statements are
 * read off the recording middleware rather than inferred, because two of these
 * promises — an unchanged save and a refused save — are promises that nothing
 * ran at all.
 */

/**
 * A post carrying three ordered translations whose locales exercise the three
 * spellings a key can take: a plain one, one holding a space and one holding a
 * dot.
 */
function compositeRepeaterSeedPost(EntityManagerInterface $entityManager): Post
{
    $post = new Post();
    $post->setTitle('one');

    $entityManager->persist($post);
    $entityManager->flush();

    $position = 0;

    foreach (['en' => 'English', 'zh Hans' => 'Chinese', 'a.b' => 'Dotted'] as $locale => $title) {
        $translation = new PostTranslation();
        $translation->setLocale($locale);
        $translation->setTitle($title);
        $translation->setPosition($position++);

        $post->addTranslation($translation);
    }

    $entityManager->flush();

    return $post;
}

/**
 * Bind a translations repeater to a post through a `data`-bag host, validator-fed
 * so `getData()` runs its validation pass. Dropping `locale` from the components
 * is what leaves a new item without half of its identifier.
 *
 * @return array{Repeater, Schema, FieldRenderHost}
 */
function compositeRepeaterOverTranslations(Post $post, bool $withLocale = true): array
{
    $repeater = Repeater::make('translations')
        ->relationship()
        ->orderColumn('position')
        ->components($withLocale
            ? [TextInput::make('locale'), TextInput::make('title')]
            : [TextInput::make('title')]);

    $host = new FieldRenderHost();
    $host->setSchemaStateValidator(test()->getContainer()->get(ValidatorInterface::class));

    $schema = Schema::make($host)->statePath('data')->record($post)->components([$repeater]);

    $host->cacheSchema('schema', $schema);
    $schema->getComponents();

    return [$repeater, $schema, $host];
}

/**
 * Run the add action and answer the item key it minted, which is a client-side
 * uuid until the save gives the child a record key.
 */
function compositeRepeaterAddItem(Repeater $repeater, FieldRenderHost $host): string
{
    $before = array_map(strval(...), array_keys($host->data['translations'] ?? []));

    $repeater->getAction($repeater->getAddActionName())?->call();

    return (string) array_first(array_diff(array_map(strval(...), array_keys($host->data['translations'])), $before));
}

it('keys an item by its whole record key, with the separator characters escaped', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $post = compositeRepeaterSeedPost($entityManager);

    [$repeater, $schema, $host] = compositeRepeaterOverTranslations($post);
    $schema->fill($post);

    $paths = [];

    foreach ($repeater->getChildSchemas() as $itemSchema) {
        foreach ($itemSchema->getComponents() as $component) {
            $paths[$component->getStatePath()] = $component->getState();
        }
    }

    expect(array_keys($host->data['translations']))->toBe(['record-1~en', 'record-1~zh=20Hans', 'record-1~a=2Eb'])
        ->and($paths)->toBe([
            'data.translations.record-1~en.locale' => 'en',
            'data.translations.record-1~en.title' => 'English',
            'data.translations.record-1~zh=20Hans.locale' => 'zh Hans',
            'data.translations.record-1~zh=20Hans.title' => 'Chinese',
            'data.translations.record-1~a=2Eb.locale' => 'a.b',
            'data.translations.record-1~a=2Eb.title' => 'Dotted',
        ]);
});

it('issues no statement for an unchanged save and one UPDATE over both key columns for a changed field', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $post = compositeRepeaterSeedPost($entityManager);

    [, $schema, $host] = compositeRepeaterOverTranslations($post);
    $schema->fill($post);

    $recorder = $this::getContainer()->get(RecordingMiddleware::class);
    $recorder->reset();

    $schema->getData();
    $entityManager->flush();

    expect($recorder->statements())->toBe([]);

    $host->data['translations']['record-1~zh=20Hans']['title'] = 'Chinese, edited';

    $recorder->reset();

    $schema->getData();
    $entityManager->flush();

    expect($recorder->statements())->toBe([
        'BEGIN',
        'UPDATE PostTranslation SET title = ? WHERE post_id = ? AND locale = ?',
        'COMMIT',
    ]);
});

it("looks a new item's key up once, then inserts it with the owner already assigned, and keys it on the next fill", function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $post = compositeRepeaterSeedPost($entityManager);

    [$repeater, $schema, $host] = compositeRepeaterOverTranslations($post);
    $schema->fill($post);

    $added = compositeRepeaterAddItem($repeater, $host);

    $host->data['translations'][$added]['locale'] = 'de';
    $host->data['translations'][$added]['title'] = 'German';

    $recorder = $this::getContainer()->get(RecordingMiddleware::class);
    $recorder->reset();

    $schema->getData();
    $entityManager->flush();

    expect($recorder->statements())->toBe([
        'SELECT t0.locale AS locale_1, t0.title AS title_2, t0.position AS position_3, t0.post_id AS post_id_4 FROM PostTranslation t0 WHERE t0.post_id = ? AND t0.locale = ?',
        'BEGIN',
        'INSERT INTO PostTranslation (locale, title, position, post_id) VALUES (?, ?, ?, ?)',
        'COMMIT',
    ]);

    $entityManager->clear();

    $reloaded = $entityManager->find(Post::class, 1);
    assert($reloaded instanceof Post);

    [, $refilled, $refilledHost] = compositeRepeaterOverTranslations($reloaded);
    $refilled->fill($reloaded);

    expect(array_keys($refilledHost->data['translations']))
        ->toBe(['record-1~en', 'record-1~zh=20Hans', 'record-1~a=2Eb', 'record-1~de']);
});

it('refuses a new item whose identifier field the components do not carry, before anything is written', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $post = compositeRepeaterSeedPost($entityManager);

    [$repeater, $schema, $host] = compositeRepeaterOverTranslations($post, withLocale: false);
    $schema->fill($post);

    $added = compositeRepeaterAddItem($repeater, $host);

    $host->data['translations'][$added]['title'] = 'Untitled';

    $recorder = $this::getContainer()->get(RecordingMiddleware::class);
    $recorder->reset();

    expect(fn (): object => $schema->getData())
        ->toThrow(LogicException::class, 'The [translations] repeater is saving an item of [' . PostTranslation::class . '] whose identifier is incomplete, so no record key can be minted for it.')
        ->and($recorder->statements())->toBe([]);

    $entityManager->clear();

    expect($entityManager->getRepository(PostTranslation::class)->count([]))->toBe(3);
});

it('leaves the item-key map where a refused save found it', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $post = compositeRepeaterSeedPost($entityManager);

    [$repeater, $schema, $host] = compositeRepeaterOverTranslations($post, withLocale: false);
    $schema->fill($post);

    // State order and collection order are pulled apart first, so a map read
    // off the collection is a different array from the keys the state carries.
    $repeater->getAction($repeater->getMoveUpActionName())?->__invoke(['item' => 'record-1~a=2Eb'])->call();

    $added = compositeRepeaterAddItem($repeater, $host);

    $host->data['translations'][$added]['title'] = 'Untitled';

    $itemKeys = $repeater->getItemKeys();

    expect($itemKeys)->toBe(['record-1~en', 'record-1~a=2Eb', 'record-1~zh=20Hans', $added]);

    expect(fn (): object => $schema->getData())
        ->toThrow(LogicException::class, 'whose identifier is incomplete, so no record key can be minted for it')
        ->and($repeater->getItemKeys())->toBe($itemKeys);
});

it('leaves a blank identifier field the components do carry to the record validation', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $post = compositeRepeaterSeedPost($entityManager);

    [$repeater, $schema, $host] = compositeRepeaterOverTranslations($post);
    $schema->fill($post);

    $added = compositeRepeaterAddItem($repeater, $host);

    $host->data['translations'][$added]['locale'] = '';
    $host->data['translations'][$added]['title'] = 'Untitled';

    $recorder = $this::getContainer()->get(RecordingMiddleware::class);
    $recorder->reset();

    expect(fn (): object => $schema->getData())->toThrow(ValidationException::class)
        ->and($host->getErrorsForSchemaStatePath('data.translations.' . $added . '.locale'))
        ->toBe(['This value should not be blank.'])
        ->and($recorder->statements())->toBe([]);

    $entityManager->clear();

    expect($entityManager->getRepository(PostTranslation::class)->count([]))->toBe(3);
});

it('deletes an item by both key columns and reorders the rest without re-keying them', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $post = compositeRepeaterSeedPost($entityManager);

    [$repeater, $schema, $host] = compositeRepeaterOverTranslations($post);
    $schema->fill($post);

    $repeater->getAction($repeater->getDeleteActionName())?->__invoke(['item' => 'record-1~zh=20Hans'])->call();
    $repeater->getAction($repeater->getMoveUpActionName())?->__invoke(['item' => 'record-1~a=2Eb'])->call();

    expect(array_keys($host->data['translations']))->toBe(['record-1~a=2Eb', 'record-1~en']);

    $recorder = $this::getContainer()->get(RecordingMiddleware::class);
    $recorder->reset();

    $schema->getData();
    $entityManager->flush();

    expect($recorder->statements())->toBe([
        'BEGIN',
        'UPDATE PostTranslation SET position = ? WHERE post_id = ? AND locale = ?',
        'UPDATE PostTranslation SET position = ? WHERE post_id = ? AND locale = ?',
        'DELETE FROM PostTranslation WHERE post_id = ? AND locale = ?',
        'COMMIT',
    ]);

    $entityManager->clear();

    $reloaded = $entityManager->find(Post::class, 1);
    assert($reloaded instanceof Post);

    [, $refilled, $refilledHost] = compositeRepeaterOverTranslations($reloaded);
    $refilled->fill($reloaded);

    expect(array_keys($refilledHost->data['translations']))->toBe(['record-1~a=2Eb', 'record-1~en']);
});

it('refuses an item key the collection no longer holds, with the message it already had', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $post = compositeRepeaterSeedPost($entityManager);

    [, $schema, $host] = compositeRepeaterOverTranslations($post);
    $schema->fill($post);

    $host->data['translations']['record-1~fr'] = $host->data['translations']['record-1~en'];

    expect(fn (): object => $schema->getData())
        ->toThrow(LogicException::class, 'The [translations] repeater carries item "record-1~fr", but the [translations] association no longer holds a record with that identifier — it was deleted or detached since the schema was filled.');
});

it('saves a complete item under an owner that has no identifier yet', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);

    $post = new Post();
    $post->setTitle('brand new');

    [$repeater, $schema, $host] = compositeRepeaterOverTranslations($post);
    $schema->fill($post);

    $added = compositeRepeaterAddItem($repeater, $host);

    $host->data['translations'][$added]['locale'] = 'de';
    $host->data['translations'][$added]['title'] = 'German';

    $schema->getData();

    $entityManager->persist($post);
    $entityManager->flush();
    $entityManager->clear();

    $reloaded = $entityManager->find(Post::class, 1);
    assert($reloaded instanceof Post);

    [, $refilled, $refilledHost] = compositeRepeaterOverTranslations($reloaded);
    $refilled->fill($reloaded);

    expect(array_keys($refilledHost->data['translations']))->toBe(['record-1~de']);
});

it('refuses an item missing its own identifier field under an owner that has no identifier yet', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);

    $post = new Post();
    $post->setTitle('brand new');

    [$repeater, $schema, $host] = compositeRepeaterOverTranslations($post, withLocale: false);
    $schema->fill($post);

    $added = compositeRepeaterAddItem($repeater, $host);

    $host->data['translations'][$added]['title'] = 'Untitled';

    $recorder = $this::getContainer()->get(RecordingMiddleware::class);
    $recorder->reset();

    expect(fn (): object => $schema->getData())
        ->toThrow(LogicException::class, 'whose identifier is incomplete, so no record key can be minted for it')
        ->and($recorder->statements())->toBe([]);

    $entityManager->clear();

    expect($entityManager->getRepository(PostTranslation::class)->count([]))->toBe(0);
});
