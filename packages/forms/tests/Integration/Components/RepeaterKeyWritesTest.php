<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Forms\Components\Repeater;
use Sylament\Forms\Components\Select;
use Sylament\Forms\Components\TextInput;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Band;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Booking;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Festival;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Headliner;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Post;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\PostTranslation;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Raffle;
use Sylament\Forms\Tests\Fixtures\Doctrine\RecordingMiddleware;
use Sylament\Schemas\Exceptions\ValidationException;
use Sylament\Schemas\Schema;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use function Sylament\Forms\Tests\renderToHtml;

/*
 * What a relationship repeater's save does with an assigned key: a new item
 * whose key a stored item, a managed item or a sibling already claims is refused
 * under the item's own field before anything is written, and an existing item
 * whose key the form changed is refused and put back, so a later flush writes
 * nothing. Every write is read off the recording middleware or the connection,
 * never off a collection.
 */

/**
 * Two posts: the first carrying `en` and `de`, the second carrying `xx`. The
 * identity map is cleared and the first post read back, so every item the fill
 * loads is managed and nothing else is.
 */
function repeaterKeyWritesSeedPost(EntityManagerInterface $entityManager): Post
{
    $post = new Post();
    $post->setTitle('one');

    $other = new Post();
    $other->setTitle('two');

    $entityManager->persist($post);
    $entityManager->persist($other);
    $entityManager->flush();

    $position = 0;

    foreach (['en' => 'English', 'de' => 'German'] as $locale => $title) {
        $translation = new PostTranslation();
        $translation->setLocale($locale);
        $translation->setTitle($title);
        $translation->setPosition($position++);

        $post->addTranslation($translation);
    }

    $foreign = new PostTranslation();
    $foreign->setLocale('xx');
    $foreign->setTitle('Other');

    $other->addTranslation($foreign);

    $entityManager->flush();
    $entityManager->clear();

    $reloaded = $entityManager->find(Post::class, 1);
    assert($reloaded instanceof Post);

    return $reloaded;
}

/**
 * Bind a schema to a record through a `data`-bag host, validator-fed so
 * `getData()` runs the record check.
 *
 * @param list<object> $components
 *
 * @return array{Schema, FieldRenderHost}
 */
function repeaterKeyWritesWire(object $record, array $components): array
{
    $host = new FieldRenderHost();
    $host->setSchemaStateValidator(test()->getContainer()->get(ValidatorInterface::class));

    $schema = Schema::make($host)->statePath('data')->record($record)->components($components);

    $host->cacheSchema('schema', $schema);
    $schema->getComponents();

    return [$schema, $host];
}

function repeaterKeyWritesTranslations(bool $withPostSelect = false): Repeater
{
    $components = [TextInput::make('locale'), TextInput::make('title')];

    if ($withPostSelect) {
        $components[] = Select::make('post')->relationship('post', 'title');
    }

    return Repeater::make('translations')->relationship()->orderColumn('position')->components($components);
}

/**
 * The errors `getData()` refused with, or null when it answered.
 *
 * @return array<string, list<string>>|null
 */
function repeaterKeyWritesRefusal(Schema $schema): ?array
{
    try {
        $schema->getData();
    } catch (ValidationException $validationException) {
        return $validationException->getErrors();
    }

    return null;
}

function repeaterKeyWritesRecorder(): RecordingMiddleware
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
function repeaterKeyWritesWrites(array $statements): array
{
    return array_values(array_filter($statements, static fn (string $statement): bool => ! str_starts_with($statement, 'SELECT')));
}

/**
 * @return list<array<string, mixed>>
 */
function repeaterKeyWritesTranslationRows(EntityManagerInterface $entityManager): array
{
    return $entityManager->getConnection()->fetchAllAssociative('SELECT post_id, locale, title FROM PostTranslation ORDER BY post_id, locale');
}

/**
 * A festival booking one band through both of its collections, and a second
 * band it does not book yet. The identity map is cleared and the festival read
 * back.
 */
function repeaterKeyWritesSeedFestival(EntityManagerInterface $entityManager): Festival
{
    $festival = new Festival();
    $festival->setName('Glade');

    $aurora = new Band();
    $aurora->setName('Aurora');

    $boreal = new Band();
    $boreal->setName('Boreal');

    $entityManager->persist($festival);
    $entityManager->persist($aurora);
    $entityManager->persist($boreal);
    $entityManager->flush();

    $booking = new Booking();
    $booking->setBand($aurora);
    $booking->setSlot('noon');

    $festival->addBooking($booking);

    $headliner = new Headliner();
    $headliner->setBand($aurora);
    $headliner->setSlot('noon');

    $festival->addHeadliner($headliner);

    $entityManager->flush();
    $entityManager->clear();

    $reloaded = $entityManager->find(Festival::class, 1);
    assert($reloaded instanceof Festival);

    return $reloaded;
}

function repeaterKeyWritesFestivalRepeater(string $collection): Repeater
{
    return Repeater::make($collection)->relationship()->components([
        Select::make('band')->relationship('band', 'name'),
        TextInput::make('slot'),
    ]);
}

it("refuses a new item on the key an existing item keeps, under the new item's own field, and writes nothing", function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $post = repeaterKeyWritesSeedPost($entityManager);

    [$schema, $host] = repeaterKeyWritesWire($post, [repeaterKeyWritesTranslations()]);
    $schema->fill($post);

    $host->data['translations']['new-a'] = ['locale' => 'en', 'title' => 'English again'];

    $recorder = repeaterKeyWritesRecorder();

    // The one read is `Post::$sections`' `#[Assert\Valid]` loading that
    // collection, as before; the item holding `en` is managed, so the refusal
    // adds none.
    expect(repeaterKeyWritesRefusal($schema))->toBe(['data.translations.new-a.locale' => ['This value is already used.']])
        ->and($recorder->statements())->toBe([
            'SELECT t0.id AS id_1, t0.heading AS heading_2, t0.body AS body_3, t0.position AS position_4, t0.post_id AS post_id_5, t0.author_id AS author_id_6 FROM PostSection t0 WHERE t0.post_id = ? ORDER BY t0.position ASC',
        ])
        ->and($entityManager->isOpen())->toBeTrue()
        ->and(repeaterKeyWritesTranslationRows($entityManager))->toBe([
            ['post_id' => 1, 'locale' => 'de', 'title' => 'German'],
            ['post_id' => 1, 'locale' => 'en', 'title' => 'English'],
            ['post_id' => 2, 'locale' => 'xx', 'title' => 'Other'],
        ])
        ->and(mb_substr_count(renderToHtml($schema), '<li class="sy-fo-field-error-message">This value is already used.</li>'))->toBe(1);
});

it('refuses the second of two new items claiming one key, after one read for the first', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $post = repeaterKeyWritesSeedPost($entityManager);

    [$schema, $host] = repeaterKeyWritesWire($post, [repeaterKeyWritesTranslations()]);
    $schema->fill($post);

    $host->data['translations']['new-a'] = ['locale' => 'fr', 'title' => 'French'];
    $host->data['translations']['new-b'] = ['locale' => 'fr', 'title' => 'French again'];

    $recorder = repeaterKeyWritesRecorder();

    expect(repeaterKeyWritesRefusal($schema))->toBe(['data.translations.new-b.locale' => ['This value is already used.']])
        ->and($recorder->statements())->toBe([
            'SELECT t0.id AS id_1, t0.heading AS heading_2, t0.body AS body_3, t0.position AS position_4, t0.post_id AS post_id_5, t0.author_id AS author_id_6 FROM PostSection t0 WHERE t0.post_id = ? ORDER BY t0.position ASC',
            'SELECT t0.locale AS locale_1, t0.title AS title_2, t0.position AS position_3, t0.post_id AS post_id_4 FROM PostTranslation t0 WHERE t0.post_id = ? AND t0.locale = ?',
        ])
        ->and($entityManager->isOpen())->toBeTrue()
        ->and(repeaterKeyWritesTranslationRows($entityManager))->toHaveCount(3);
});

it('refuses the second of two new items claiming one key under an owner that has no identifier yet, reading nothing', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);

    [$schema, $host] = repeaterKeyWritesWire(new Post(), [TextInput::make('title'), repeaterKeyWritesTranslations()]);
    $schema->fill();

    $host->data['title'] = 'fresh';
    $host->data['translations'] = [
        'new-a' => ['locale' => 'fr', 'title' => 'French'],
        'new-b' => ['locale' => 'fr', 'title' => 'French again'],
    ];

    $recorder = repeaterKeyWritesRecorder();

    expect(repeaterKeyWritesRefusal($schema))->toBe(['data.translations.new-b.locale' => ['This value is already used.']])
        ->and($recorder->statements())->toBe([])
        ->and($entityManager->isOpen())->toBeTrue()
        ->and(repeaterKeyWritesTranslationRows($entityManager))->toBe([]);
});

/*
 * A recorded limit, not a defect: Doctrine inserts before it deletes, so this
 * save could never flush, and the refusal is correct — but it names the key the
 * user just removed.
 */
it('refuses a new item on the key of an item the same save removes', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $post = repeaterKeyWritesSeedPost($entityManager);

    [$schema, $host] = repeaterKeyWritesWire($post, [repeaterKeyWritesTranslations()]);
    $schema->fill($post);

    unset($host->data['translations']['record-1~en']);
    $host->data['translations']['new-a'] = ['locale' => 'en', 'title' => 'English, rewritten'];

    $recorder = repeaterKeyWritesRecorder();

    expect(repeaterKeyWritesRefusal($schema))->toBe(['data.translations.new-a.locale' => ['This value is already used.']])
        ->and(repeaterKeyWritesWrites($recorder->statements()))->toBe([])
        ->and($entityManager->isOpen())->toBeTrue()
        ->and(repeaterKeyWritesTranslationRows($entityManager))->toHaveCount(3);
});

it("refuses an existing item's key half edited, puts the old one back, and writes nothing on the next flush", function (string $locale): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $post = repeaterKeyWritesSeedPost($entityManager);

    [$schema, $host] = repeaterKeyWritesWire($post, [repeaterKeyWritesTranslations()]);
    $schema->fill($post);

    $host->data['translations']['record-1~en']['locale'] = $locale;

    $recorder = repeaterKeyWritesRecorder();

    expect(repeaterKeyWritesRefusal($schema))->toBe(['data.translations.record-1~en.locale' => ['This value cannot be changed.']])
        ->and($post->getTranslations()->map(static fn (PostTranslation $translation): string => $translation->getLocale())->toArray())->toBe(['en', 'de']);

    $entityManager->flush();

    expect(repeaterKeyWritesWrites($recorder->statements()))->toBe([])
        ->and($entityManager->isOpen())->toBeTrue()
        ->and(repeaterKeyWritesTranslationRows($entityManager))->toBe([
            ['post_id' => 1, 'locale' => 'de', 'title' => 'German'],
            ['post_id' => 1, 'locale' => 'en', 'title' => 'English'],
            ['post_id' => 2, 'locale' => 'xx', 'title' => 'Other'],
        ]);
})->with([
    'a free key' => ['fr'],
    "a sibling's key" => ['de'],
]);

it("refuses an item's own relationship select moving it onto another post, and writes nothing on the next flush", function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $post = repeaterKeyWritesSeedPost($entityManager);

    [$schema, $host] = repeaterKeyWritesWire($post, [repeaterKeyWritesTranslations(withPostSelect: true)]);
    $schema->fill($post);

    $host->data['translations']['record-1~en']['post'] = '2';

    $recorder = repeaterKeyWritesRecorder();

    $moved = $post->getTranslations()->first();
    assert($moved instanceof PostTranslation);

    expect(repeaterKeyWritesRefusal($schema))->toBe(['data.translations.record-1~en.post' => ['This value cannot be changed.']])
        ->and($moved->getPost())->toBe($post);

    $entityManager->flush();

    expect(repeaterKeyWritesWrites($recorder->statements()))->toBe([])
        ->and(repeaterKeyWritesTranslationRows($entityManager))->toBe([
            ['post_id' => 1, 'locale' => 'de', 'title' => 'German'],
            ['post_id' => 1, 'locale' => 'en', 'title' => 'English'],
            ['post_id' => 2, 'locale' => 'xx', 'title' => 'Other'],
        ]);
});

it('refuses a new join-entity item on a band the festival already has, under the band rather than the festival', function (string $collection, string $table): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $festival = repeaterKeyWritesSeedFestival($entityManager);

    [$schema, $host] = repeaterKeyWritesWire($festival, [repeaterKeyWritesFestivalRepeater($collection)]);
    $schema->fill($festival);

    $host->data[$collection]['new-a'] = ['band' => '1', 'slot' => 'dusk'];

    $recorder = repeaterKeyWritesRecorder();

    expect(repeaterKeyWritesRefusal($schema))->toBe(['data.' . $collection . '.new-a.band' => ['This value is already used.']])
        ->and(repeaterKeyWritesWrites($recorder->statements()))->toBe([])
        ->and($entityManager->isOpen())->toBeTrue()
        ->and($entityManager->getConnection()->fetchOne('SELECT COUNT(*) FROM ' . $table))->toBe(1);
})->with([
    'festival declared first' => ['bookings', 'Booking'],
    'band declared first' => ['headliners', 'Headliner'],
]);

it('refuses the second of two new join-entity items naming one band, the band compared as the same record', function (string $collection, string $table): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $festival = repeaterKeyWritesSeedFestival($entityManager);

    [$schema, $host] = repeaterKeyWritesWire($festival, [repeaterKeyWritesFestivalRepeater($collection)]);
    $schema->fill($festival);

    $host->data[$collection]['new-a'] = ['band' => '2', 'slot' => 'dusk'];
    $host->data[$collection]['new-b'] = ['band' => '2', 'slot' => 'dawn'];

    $recorder = repeaterKeyWritesRecorder();

    expect(repeaterKeyWritesRefusal($schema))->toBe(['data.' . $collection . '.new-b.band' => ['This value is already used.']])
        ->and(repeaterKeyWritesWrites($recorder->statements()))->toBe([])
        ->and($entityManager->getConnection()->fetchOne('SELECT COUNT(*) FROM ' . $table))->toBe(1);
})->with([
    'festival declared first' => ['bookings', 'Booking'],
    'band declared first' => ['headliners', 'Headliner'],
]);

it('saves two new items whose single string key their prePersist callback draws from blank', function (string $owner): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);

    if ($owner === 'existing') {
        $raffle = new Raffle();
        $raffle->setName('spring');

        $entityManager->persist($raffle);
        $entityManager->flush();
        $entityManager->clear();

        $raffle = $entityManager->find(Raffle::class, 1);
    } else {
        $raffle = new Raffle();
    }

    assert($raffle instanceof Raffle);

    [$schema, $host] = repeaterKeyWritesWire($raffle, [
        TextInput::make('name'),
        Repeater::make('coupons')->relationship()->components([TextInput::make('label')]),
    ]);
    $schema->fill($raffle);

    $host->data['name'] = 'spring';
    $host->data['coupons'] = ['new-a' => ['label' => 'first'], 'new-b' => ['label' => 'second']];

    expect(repeaterKeyWritesRefusal($schema))->toBeNull();

    $entityManager->persist($raffle);
    $entityManager->flush();

    $rows = $entityManager->getConnection()->fetchAllAssociative('SELECT raffle_id, label, length(code) AS code_length FROM Coupon ORDER BY label');

    expect($rows)->toBe([
        ['raffle_id' => 1, 'label' => 'first', 'code_length' => 8],
        ['raffle_id' => 1, 'label' => 'second', 'code_length' => 8],
    ])
        ->and($entityManager->getConnection()->fetchOne('SELECT COUNT(DISTINCT code) FROM Coupon'))->toBe(2);
})->with(['existing', 'new']);

it('saves two new join-entity items naming two different bands', function (string $collection, string $table): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    $festival = repeaterKeyWritesSeedFestival($entityManager);

    $cirrus = new Band();
    $cirrus->setName('Cirrus');

    $entityManager->persist($cirrus);
    $entityManager->flush();

    [$schema, $host] = repeaterKeyWritesWire($festival, [repeaterKeyWritesFestivalRepeater($collection)]);
    $schema->fill($festival);

    $host->data[$collection]['new-a'] = ['band' => '2', 'slot' => 'dusk'];
    $host->data[$collection]['new-b'] = ['band' => (string) $cirrus->getId(), 'slot' => 'dawn'];

    expect(repeaterKeyWritesRefusal($schema))->toBeNull();

    $entityManager->flush();

    expect($entityManager->getConnection()->fetchOne('SELECT COUNT(*) FROM ' . $table))->toBe(3);
})->with([
    'festival declared first' => ['bookings', 'Booking'],
    'band declared first' => ['headliners', 'Headliner'],
]);
