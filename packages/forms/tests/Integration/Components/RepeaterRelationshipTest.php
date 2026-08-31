<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components;

use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Sylament\Forms\Components\CheckboxList;
use Sylament\Forms\Components\Repeater;
use Sylament\Forms\Components\Select;
use Sylament\Forms\Components\TextInput;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Author;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Post;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\PostSection;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\SectionNote;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Tag;
use Sylament\Schemas\Schema;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/*
 * Repeater::relationship() against a real Doctrine entity manager: items are
 * keyed by the identity of the children the collection holds, and getData()
 * diffs the submitted items back onto that collection — updating the kept
 * instances in place, constructing the added ones, and dropping the missing ones
 * for orphanRemoval to delete. Nothing here is faked: the item keys are real
 * record keys, so no test calls Repeater::fake().
 */

/**
 * Bind a repeater to a record instance through a `data`-bag host, keyed and
 * validator-fed so `getData()` can run its validation pass.
 *
 * @return array{Schema, FieldRenderHost}
 */
function wireRelationshipRepeater(Repeater $repeater, object $record): array
{
    $host = new FieldRenderHost();
    $host->setSchemaStateValidator(test()->getContainer()->get(ValidatorInterface::class));

    $schema = Schema::make($host)->statePath('data')->record($record)->components([$repeater]);

    $host->cacheSchema('schema', $schema);
    $schema->getComponents();

    return [$schema, $host];
}

/**
 * A flushed post carrying two ordered sections, every entity still managed.
 *
 * @return array{Post, PostSection, PostSection}
 */
function seedSectionedPost(EntityManagerInterface $entityManager): array
{
    $post = new Post();
    $post->setTitle('t');

    $one = new PostSection();
    $one->setHeading('one');
    $one->setPosition(0);

    $two = new PostSection();
    $two->setHeading('two');
    $two->setPosition(1);

    $post->addSection($one);
    $post->addSection($two);

    $entityManager->persist($post);
    $entityManager->flush();

    return [$post, $one, $two];
}

/**
 * Two flushed authors, so a nested relation select has one to fill from and a
 * second to swap to.
 *
 * @return array{Author, Author}
 */
function seedSectionAuthorPair(EntityManagerInterface $entityManager): array
{
    $one = new Author();
    $one->setName('ada');

    $two = new Author();
    $two->setName('bob');

    $entityManager->persist($one);
    $entityManager->persist($two);
    $entityManager->flush();

    return [$one, $two];
}

/**
 * Three flushed tags, so a nested to-many can keep one, drop one and add one.
 *
 * @return array{Tag, Tag, Tag}
 */
function seedSectionTagTrio(EntityManagerInterface $entityManager): array
{
    $tags = [];

    foreach (['a', 'b', 'c'] as $name) {
        $tag = new Tag();
        $tag->setName($name);

        $entityManager->persist($tag);

        $tags[] = $tag;
    }

    $entityManager->flush();

    return [$tags[0], $tags[1], $tags[2]];
}

/**
 * A flushed note on a section, so a nested relationship repeater has children to
 * fill from.
 */
function seedSectionNote(EntityManagerInterface $entityManager, PostSection $section, string $body): SectionNote
{
    $note = new SectionNote();
    $note->setBody($body);

    $section->addNote($note);

    $entityManager->persist($note);
    $entityManager->flush();

    return $note;
}

/**
 * The item key the add action minted — the one key the submitted map carries
 * that the filled map did not.
 *
 * @param array<array-key, mixed> $items
 * @param list<string>            $filledKeys
 */
function addedItemKey(array $items, array $filledKeys): string
{
    return (string) array_first(array_diff(array_map(strval(...), array_keys($items)), $filledKeys));
}

it('fills loaded sections as record-keyed items', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, $one, $two] = seedSectionedPost($entityManager);

    $repeater = Repeater::make('sections')
        ->relationship()
        ->components([TextInput::make('heading'), TextInput::make('body')]);

    [$schema, $host] = wireRelationshipRepeater($repeater, $post);

    $schema->fill($post);

    expect(array_keys($host->data['sections']))->toBe(['record-' . $one->getId(), 'record-' . $two->getId()])
        ->and($host->data['sections']['record-' . $one->getId()]['heading'])->toBe('one')
        ->and($host->data['sections']['record-' . $two->getId()]['heading'])->toBe('two')
        ->and($host->data['sections']['record-' . $one->getId()]['body'])->toBeNull();
});

it('updates an existing child in place through getData', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, $one] = seedSectionedPost($entityManager);

    $repeater = Repeater::make('sections')
        ->relationship()
        ->components([TextInput::make('heading')]);

    [$schema, $host] = wireRelationshipRepeater($repeater, $post);

    $schema->fill($post);

    $host->data['sections']['record-' . $one->getId()]['heading'] = 'edited';

    expect($schema->getData())->toBe($post)
        ->and($one->getHeading())->toBe('edited')
        ->and($post->getSections()->contains($one))->toBeTrue()
        ->and($post->getSections()->count())->toBe(2);
});

it('creates an added item and deletes a removed one on flush', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, $one, $two] = seedSectionedPost($entityManager);

    $postId = $post->getId();
    $twoId = $two->getId();

    $repeater = Repeater::make('sections')
        ->relationship()
        ->orderColumn('position')
        ->components([TextInput::make('heading'), TextInput::make('body')]);

    [$schema, $host] = wireRelationshipRepeater($repeater, $post);

    $schema->fill($post);

    $filledKeys = array_map(strval(...), array_keys($host->data['sections']));

    $repeater->getAction($repeater->getDeleteActionName())?->__invoke(['item' => 'record-' . $twoId])->call();
    $repeater->getAction($repeater->getAddActionName())?->call();

    $addedKey = addedItemKey($host->data['sections'], $filledKeys);

    expect($addedKey)->not->toStartWith('record-');

    $host->data['sections'][$addedKey]['heading'] = 'three';

    expect($schema->getData())->toBe($post);

    $entityManager->flush();
    $entityManager->clear();

    $reloaded = $entityManager->find(Post::class, $postId);
    assert($reloaded instanceof Post);

    $headings = array_map(static fn (PostSection $section): string => $section->getHeading(), $reloaded->getSections()->toArray());

    expect($entityManager->find(PostSection::class, $twoId))->toBeNull()
        ->and($headings)->toBe(['one', 'three'])
        ->and($reloaded->getSections()->last()->getPost())->toBe($reloaded)
        ->and($one->getId())->not->toBeNull();
});

it('stamps the order column from item order', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, $one, $two] = seedSectionedPost($entityManager);

    $repeater = Repeater::make('sections')
        ->relationship()
        ->orderColumn('position')
        ->components([TextInput::make('heading')]);

    [$schema, $host] = wireRelationshipRepeater($repeater, $post);

    $schema->fill($post);

    $repeater->getAction($repeater->getMoveUpActionName())?->__invoke(['item' => 'record-' . $two->getId()])->call();

    expect(array_keys($host->data['sections']))->toBe(['record-' . $two->getId(), 'record-' . $one->getId()]);

    $schema->getData();

    expect($repeater->getOrderColumn())->toBe('position')
        ->and($two->getPosition())->toBe(0)
        ->and($one->getPosition())->toBe(1);
});

it('runs the mutate hooks at their moments', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, $one, $two] = seedSectionedPost($entityManager);

    $repeater = Repeater::make('sections')
        ->relationship()
        ->mutateRelationshipDataBeforeFillUsing(static fn (array $state): array => [
            ...$state,
            'heading' => mb_strtoupper((string) $state['heading']),
        ])
        ->mutateRelationshipDataBeforeCreateUsing(static fn (array $state): array => [
            ...$state,
            'body' => 'created',
        ])
        ->mutateRelationshipDataBeforeSaveUsing(static fn (array $state, PostSection $record): array => [
            ...$state,
            'body' => 'saved ' . $record->getHeading(),
        ])
        ->components([TextInput::make('heading'), TextInput::make('body')]);

    [$schema, $host] = wireRelationshipRepeater($repeater, $post);

    $schema->fill($post);

    expect($host->data['sections']['record-' . $one->getId()]['heading'])->toBe('ONE');

    $filledKeys = array_map(strval(...), array_keys($host->data['sections']));

    $repeater->getAction($repeater->getAddActionName())?->call();

    $addedKey = addedItemKey($host->data['sections'], $filledKeys);
    $host->data['sections'][$addedKey]['heading'] = 'three';

    $schema->getData();

    expect($one->getHeading())->toBe('ONE')
        ->and($one->getBody())->toBe('saved one')
        ->and($two->getBody())->toBe('saved two')
        ->and($post->getSections()->last()->getBody())->toBe('created');
});

it('skips creating an item whose before-create hook returns null', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post] = seedSectionedPost($entityManager);

    $postId = $post->getId();

    $repeater = Repeater::make('sections')
        ->relationship()
        ->mutateRelationshipDataBeforeCreateUsing(static fn (): null => null)
        ->components([TextInput::make('heading')]);

    [$schema, $host] = wireRelationshipRepeater($repeater, $post);

    $schema->fill($post);

    $filledKeys = array_map(strval(...), array_keys($host->data['sections']));

    $repeater->getAction($repeater->getAddActionName())?->call();

    $host->data['sections'][addedItemKey($host->data['sections'], $filledKeys)]['heading'] = 'three';

    $schema->getData();

    expect($post->getSections()->count())->toBe(2);

    $entityManager->flush();
    $entityManager->clear();

    $reloaded = $entityManager->find(Post::class, $postId);
    assert($reloaded instanceof Post);

    expect(array_map(static fn (PostSection $section): string => $section->getHeading(), $reloaded->getSections()->toArray()))
        ->toBe(['one', 'two']);
});

it('leaves a child whose before-save hook returns null untouched and still kept', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, $one, $two] = seedSectionedPost($entityManager);

    $repeater = Repeater::make('sections')
        ->relationship()
        ->mutateRelationshipDataBeforeSaveUsing(
            static fn (array $state, PostSection $record): ?array => ($record->getHeading() === 'two') ? null : $state,
        )
        ->components([TextInput::make('heading')]);

    [$schema, $host] = wireRelationshipRepeater($repeater, $post);

    $schema->fill($post);

    $host->data['sections']['record-' . $one->getId()]['heading'] = 'edited one';
    $host->data['sections']['record-' . $two->getId()]['heading'] = 'edited two';

    $schema->getData();

    expect($one->getHeading())->toBe('edited one')
        ->and($two->getHeading())->toBe('two')
        ->and($post->getSections()->contains($two))->toBeTrue()
        ->and($post->getSections()->count())->toBe(2);
});

it('injects the child record by type into the before-save hook', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, $one] = seedSectionedPost($entityManager);

    $repeater = Repeater::make('sections')
        ->relationship()
        ->mutateRelationshipDataBeforeSaveUsing(static fn (array $state, PostSection $section): array => [
            ...$state,
            'body' => 'saved ' . $section->getHeading(),
        ])
        ->components([TextInput::make('heading'), TextInput::make('body')]);

    [$schema] = wireRelationshipRepeater($repeater, $post);

    $schema->fill($post);
    $schema->getData();

    expect($one->getBody())->toBe('saved one');
});

it('keeps refusing an object collection without relationship()', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post] = seedSectionedPost($entityManager);

    [$schema] = wireRelationshipRepeater(
        Repeater::make('sections')->components([TextInput::make('heading')]),
        $post,
    );

    expect(fn (): Schema => $schema->fill($post))
        ->toThrow(LogicException::class, 'is an object collection')
        ->and(fn (): Schema => $schema->fill($post))
        ->toThrow(LogicException::class, 'Declare relationship() on this repeater');
});

it("fills a nested relation select from each item's own child", function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, $one] = seedSectionedPost($entityManager);
    [$ada] = seedSectionAuthorPair($entityManager);

    $one->setAuthor($ada);
    $entityManager->flush();

    $repeater = Repeater::make('sections')
        ->relationship()
        ->components([TextInput::make('heading'), Select::make('author')->relationship(titleAttribute: 'name')]);

    [$schema, $host] = wireRelationshipRepeater($repeater, $post);

    $schema->fill($post);

    expect($host->data['sections']['record-' . $one->getId()]['author'])->toBe((string) $ada->getId())
        ->and($host->data['sections']['record-' . $one->getId()]['heading'])->toBe('one')
        ->and(array_last($host->data['sections'])['author'])->toBeNull();
});

it("saves a changed nested association onto the item's child", function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, $one] = seedSectionedPost($entityManager);
    [$ada, $bob] = seedSectionAuthorPair($entityManager);

    $one->setAuthor($ada);
    $entityManager->flush();

    $oneId = $one->getId();

    $repeater = Repeater::make('sections')
        ->relationship()
        ->components([TextInput::make('heading'), Select::make('author')->relationship(titleAttribute: 'name')]);

    [$schema, $host] = wireRelationshipRepeater($repeater, $post);

    $schema->fill($post);

    $host->data['sections']['record-' . $oneId]['author'] = (string) $bob->getId();

    expect($schema->getData())->toBe($post)
        ->and($one->getAuthor())->toBe($bob);

    $entityManager->clear();

    $reloaded = $entityManager->find(PostSection::class, $oneId);
    assert($reloaded instanceof PostSection);

    expect($reloaded->getAuthor()?->getName())->toBe('ada');
});

it('clears a nested association to null', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, $one] = seedSectionedPost($entityManager);
    [$ada] = seedSectionAuthorPair($entityManager);

    $one->setAuthor($ada);
    $entityManager->flush();

    $repeater = Repeater::make('sections')
        ->relationship()
        ->components([TextInput::make('heading'), Select::make('author')->relationship(titleAttribute: 'name')]);

    [$schema, $host] = wireRelationshipRepeater($repeater, $post);

    $schema->fill($post);

    $host->data['sections']['record-' . $one->getId()]['author'] = '';

    $schema->getData();

    expect($one->getAuthor())->toBeNull();
});

it('creates an added item carrying a nested association', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post] = seedSectionedPost($entityManager);
    [, $bob] = seedSectionAuthorPair($entityManager);

    $repeater = Repeater::make('sections')
        ->relationship()
        ->components([TextInput::make('heading'), Select::make('author')->relationship(titleAttribute: 'name')]);

    [$schema, $host] = wireRelationshipRepeater($repeater, $post);

    $schema->fill($post);

    $filledKeys = array_map(strval(...), array_keys($host->data['sections']));

    $repeater->getAction($repeater->getAddActionName())?->call();

    $addedKey = addedItemKey($host->data['sections'], $filledKeys);

    $host->data['sections'][$addedKey]['heading'] = 'three';
    $host->data['sections'][$addedKey]['author'] = (string) $bob->getId();

    $schema->getData();

    $added = $post->getSections()->last();
    assert($added instanceof PostSection);

    expect($added->getHeading())->toBe('three')
        ->and($added->getAuthor())->toBe($bob);
});

it("round-trips a nested to-many through the item's child", function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, $one] = seedSectionedPost($entityManager);
    [$a, $b, $c] = seedSectionTagTrio($entityManager);

    $one->addTag($a);
    $one->addTag($b);

    $entityManager->flush();

    $repeater = Repeater::make('sections')
        ->relationship()
        ->components([TextInput::make('heading'), CheckboxList::make('tags')->relationship(titleAttribute: 'name')]);

    [$schema, $host] = wireRelationshipRepeater($repeater, $post);

    $schema->fill($post);

    expect($host->data['sections']['record-' . $one->getId()]['tags'])
        ->toBe([(string) $a->getId(), (string) $b->getId()]);

    $host->data['sections']['record-' . $one->getId()]['tags'] = [(string) $b->getId(), (string) $c->getId()];

    $schema->getData();

    expect($one->getTags()->contains($a))->toBeFalse()
        ->and($one->getTags()->contains($b))->toBeTrue()
        ->and($one->getTags()->contains($c))->toBeTrue();
});

it('recurses into a nested relationship repeater', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, $one, $two] = seedSectionedPost($entityManager);

    $kept = seedSectionNote($entityManager, $one, 'kept');
    $dropped = seedSectionNote($entityManager, $one, 'dropped');
    $droppedId = $dropped->getId();

    $repeater = Repeater::make('sections')
        ->relationship()
        ->components([
            TextInput::make('heading'),
            Repeater::make('notes')->relationship()->components([TextInput::make('body')]),
        ]);

    [$schema, $host] = wireRelationshipRepeater($repeater, $post);

    $schema->fill($post);

    expect(array_keys($host->data['sections']['record-' . $one->getId()]['notes']))
        ->toBe(['record-' . $kept->getId(), 'record-' . $droppedId])
        ->and($host->data['sections']['record-' . $one->getId()]['notes']['record-' . $kept->getId()]['body'])->toBe('kept')
        ->and($host->data['sections']['record-' . $two->getId()]['notes'])->toBe([]);

    $notes = $host->data['sections']['record-' . $one->getId()]['notes'];

    unset($notes['record-' . $droppedId]);

    $notes['record-' . $kept->getId()]['body'] = 'edited';
    $notes['added-note'] = ['body' => 'added'];

    $host->data['sections']['record-' . $one->getId()]['notes'] = $notes;

    $schema->getData();

    expect(array_map(static fn (SectionNote $note): string => $note->getBody(), $one->getNotes()->getValues()))
        ->toBe(['edited', 'added'])
        ->and($one->getNotes()->last()->getSection())->toBe($one);

    $entityManager->flush();
    $entityManager->clear();

    expect($entityManager->find(SectionNote::class, $droppedId))->toBeNull();
});

it('stamps item schemas with the child record', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, $one] = seedSectionedPost($entityManager);

    $repeater = Repeater::make('sections')
        ->relationship()
        ->components([TextInput::make('heading')]);

    [$schema, $host] = wireRelationshipRepeater($repeater, $post);

    $schema->fill($post);

    $filledKeys = array_map(strval(...), array_keys($host->data['sections']));

    $repeater->getAction($repeater->getAddActionName())?->call();

    $loadedField = $repeater->getChildSchema('record-' . $one->getId())?->getFlatComponents(withActions: false)[0] ?? null;
    $addedField = $repeater->getChildSchema(addedItemKey($host->data['sections'], $filledKeys))?->getFlatComponents(withActions: false)[0] ?? null;

    expect($loadedField?->getRecord())->toBe($one)
        ->and($loadedField?->getRecordClass())->toBe(PostSection::class)
        ->and($addedField?->getRecord())->toBeNull()
        ->and($addedField?->getRecordClass())->toBe(PostSection::class);
});

it('lets a mutate hook rewrite a delegated key', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, $one] = seedSectionedPost($entityManager);
    [$ada, $bob] = seedSectionAuthorPair($entityManager);

    $one->setAuthor($ada);
    $entityManager->flush();

    $bobId = (string) $bob->getId();

    $repeater = Repeater::make('sections')
        ->relationship()
        ->mutateRelationshipDataBeforeSaveUsing(static fn (array $state): array => [...$state, 'author' => $bobId])
        ->components([TextInput::make('heading'), Select::make('author')->relationship(titleAttribute: 'name')]);

    [$schema, $host] = wireRelationshipRepeater($repeater, $post);

    $schema->fill($post);

    expect($host->data['sections']['record-' . $one->getId()]['author'])->toBe((string) $ada->getId());

    $schema->getData();

    expect($one->getAuthor())->toBe($bob);
});

it('vouches only for an item path its items declare a hook at', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, $one] = seedSectionedPost($entityManager);

    $repeater = Repeater::make('sections')
        ->relationship()
        ->components([
            TextInput::make('heading'),
            Select::make('author')->relationship(titleAttribute: 'name'),
            Repeater::make('notes')->relationship()->components([TextInput::make('body')]),
        ]);

    [$schema] = wireRelationshipRepeater($repeater, $post);

    $schema->fill($post);

    $itemKey = 'record-' . $one->getId();

    expect(array_keys($repeater->getChildSchema($itemKey)?->getNestedRelationshipHooks() ?? []))
        ->toBe(['author', 'notes'])
        ->and($repeater->routesNestedRelationshipHookAt($itemKey . '.author'))->toBeTrue()
        ->and($repeater->routesNestedRelationshipHookAt($itemKey . '.notes'))->toBeTrue()
        ->and($repeater->routesNestedRelationshipHookAt($itemKey . '.body'))->toBeFalse()
        ->and($repeater->routesNestedRelationshipHookAt('author'))->toBeFalse()
        ->and($repeater->routesNestedRelationshipHookAt($itemKey . '.'))->toBeFalse()
        ->and($repeater->routesNestedRelationshipHookAt('.author'))->toBeFalse();
});

it('leaves a decoration schema on the owner record', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post] = seedSectionedPost($entityManager);

    $seen = 'unset';

    $repeater = Repeater::make('sections')
        ->relationship()
        ->belowContent([
            TextInput::make('note')->label(static function (?object $record) use (&$seen): string {
                $seen = $record;

                return 'note';
            }),
        ])
        ->components([TextInput::make('heading')]);

    [$schema] = wireRelationshipRepeater($repeater, $post);

    $schema->fill($post);

    $decoration = $repeater->getChildSchema(Repeater::BELOW_CONTENT_SCHEMA_KEY);
    $decoration?->getComponents(withActions: false)[0]->getLabel();

    expect($seen)->toBe($post)
        ->and($decoration?->hasOwnRecord())->toBeFalse()
        ->and($repeater->getChildSchema()?->hasOwnRecord())->toBeTrue();
});

it('takes a hand-supplied item under a load-only hook without demanding a schema', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post] = seedSectionedPost($entityManager);

    $repeater = Repeater::make('sections')
        ->relationship()
        ->components([
            TextInput::make('heading'),
            TextInput::make('body')->loadStateFromRelationshipsUsing(static fn (): string => 'loaded'),
        ]);

    [$schema, $host] = wireRelationshipRepeater($repeater, $post);

    $schema->fill($post);

    expect(array_first($host->data['sections'])['body'])->toBe('loaded');

    $state = $schema->getState();
    $state['sections']['hand-supplied'] = ['heading' => 'three', 'body' => 'written'];

    $schema->getData($state);

    $added = $post->getSections()->last();
    assert($added instanceof PostSection);

    expect($post->getSections()->count())->toBe(3)
        ->and($added->getHeading())->toBe('three')
        ->and($added->getBody())->toBe('written');
});

it('takes a hand-supplied item through a nested relationship hook', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post] = seedSectionedPost($entityManager);
    [$ada] = seedSectionAuthorPair($entityManager);

    $repeater = Repeater::make('sections')
        ->relationship()
        ->components([
            TextInput::make('heading'),
            TextInput::make('body')->loadStateFromRelationshipsUsing(static fn (): string => 'loaded'),
            Select::make('author')->relationship(titleAttribute: 'name'),
        ]);

    [$schema] = wireRelationshipRepeater($repeater, $post);

    $schema->fill($post);

    $state = $schema->getState();
    $state['sections']['hand-supplied'] = [
        'heading' => 'three',
        'body' => 'written',
        'author' => (string) $ada->getId(),
    ];

    $schema->getData($state);

    $added = $post->getSections()->last();
    assert($added instanceof PostSection);

    expect($post->getSections()->count())->toBe(3)
        ->and($added->getHeading())->toBe('three')
        ->and($added->getBody())->toBe('written')
        ->and($added->getAuthor())->toBe($ada);
});

it('refuses a hooked select in a decoration schema loudly', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post] = seedSectionedPost($entityManager);

    [$schema] = wireRelationshipRepeater(
        Repeater::make('sections')
            ->relationship()
            ->belowContent([Select::make('author')->relationship(titleAttribute: 'name')])
            ->components([TextInput::make('heading')]),
        $post,
    );

    expect(fn (): Schema => $schema->fill($post))
        ->toThrow(LogicException::class, 'without routing the hooks beneath it');
});

it('refuses a dotted decoration path whose remainder names an item hook', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post] = seedSectionedPost($entityManager);

    [$schema] = wireRelationshipRepeater(
        Repeater::make('sections')
            ->relationship()
            ->belowContent([Select::make('meta.author')->relationship(titleAttribute: 'name')])
            ->components([TextInput::make('heading'), Select::make('author')->relationship(titleAttribute: 'name')]),
        $post,
    );

    expect(fn (): Schema => $schema->fill($post))
        ->toThrow(LogicException::class, 'Declare relationship() on that component');
});

it('refuses a hooked field inside a plain-array repeater', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post] = seedSectionedPost($entityManager);

    $repeater = Repeater::make('extras')
        ->components([TextInput::make('heading'), Select::make('author')->relationship(titleAttribute: 'name')]);

    [$schema, $host] = wireRelationshipRepeater($repeater, $post);

    $host->data['extras'] = [['heading' => 'one', 'author' => null]];

    expect(fn (): object => $schema->getData(['extras' => [['heading' => 'one', 'author' => null]]]))
        ->toThrow(LogicException::class, 'Declare relationship() on that component');
});

it('refuses relationship() beside another item mode', function (): void {
    expect(fn (): Repeater => Repeater::make('sections')->simple(TextInput::make('heading'))->relationship())
        ->toThrow(LogicException::class, 'relationship() cannot compose with simple()')
        ->and(fn (): Repeater => Repeater::make('sections')->itemsAs(PostSection::class)->relationship())
        ->toThrow(LogicException::class, 'relationship() cannot compose with itemsAs()')
        ->and(fn (): Repeater => Repeater::make('sections')->relationship()->simple(TextInput::make('heading')))
        ->toThrow(LogicException::class, 'simple() cannot compose with relationship()')
        ->and(fn (): Repeater => Repeater::make('sections')->relationship()->itemsAs(PostSection::class))
        ->toThrow(LogicException::class, 'itemsAs() cannot compose with relationship()');
});
