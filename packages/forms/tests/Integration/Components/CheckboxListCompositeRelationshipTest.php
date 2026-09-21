<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Forms\Components\CheckboxList;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\CompositePair;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Post;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\PostTranslation;
use Sylament\Schemas\Schema;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/*
 * CheckboxList::relationship() over a target keyed by more than one field. The
 * field has no key handling of its own — it reads and writes through the same
 * to-many arm `Select::multiple()` rides — so these cases are what proves the
 * shared arm needed no second edit to speak a record key, and that the members
 * it writes are the identity-map instances `attach()`/`detach()` compare with.
 */

/**
 * Bind a checkbox list to a record instance through a `data`-bag host, keyed and
 * validator-fed so `getData()` can run its validation pass.
 *
 * @return array{Schema, FieldRenderHost}
 */
function compositeSelectListWire(CheckboxList $field, object $record): array
{
    $host = new FieldRenderHost();
    $host->setSchemaStateValidator(test()->getContainer()->get(ValidatorInterface::class));

    $schema = Schema::make($host)->statePath('data')->record($record)->components([$field]);

    $host->cacheSchema('schema', $schema);
    $schema->getComponents();

    return [$schema, $host];
}

/**
 * One post holding pairs `1~2` and `3~4` and the linked translation `1~en`, with
 * `5~6` and `2~de` available as the unchecked rows. The identity map is cleared,
 * so every reference the write resolves comes off a cold map — which is where a
 * key decoded to the wrong shape raises rather than hiding behind a live object.
 */
function compositeSelectListSeed(EntityManagerInterface $entityManager): void
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

    $entityManager->persist($first);
    $entityManager->persist($second);
    $entityManager->persist($third);

    $english = new PostTranslation();
    $english->setLocale('en');
    $english->setTitle('English');

    $post->addTranslation($english);

    $german = new PostTranslation();
    $german->setLocale('de');
    $german->setTitle('German');

    $other->addTranslation($german);

    $entityManager->flush();

    $post->addPair($first);
    $post->addPair($second);
    $post->addLinkedTranslation($english);

    $entityManager->flush();
    $entityManager->clear();
}

it('enumerates a composite target and fills the stored keys', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    compositeSelectListSeed($entityManager);

    $post = $entityManager->find(Post::class, 1);

    $field = CheckboxList::make('pairs')->relationship(titleAttribute: 'label');
    [$schema, $host] = compositeSelectListWire($field, $post ?? new Post());

    $schema->fill($post);

    // The field enumerates in database order; ordering by the title attribute is
    // the select's arm, not this one's.
    expect($field->getOptions())->toBe([
        '1~2' => 'one-two',
        '3~4' => 'three-four',
        '5~6' => 'five-six',
    ])
        ->and($host->data['pairs'])->toBe(['1~2', '3~4']);
});

it('writes the checked composite keys back as the instances a find() answers', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    compositeSelectListSeed($entityManager);

    $post = $entityManager->find(Post::class, 1);

    $field = CheckboxList::make('pairs')->relationship(titleAttribute: 'label');
    [$schema, $host] = compositeSelectListWire($field, $post ?? new Post());

    $schema->fill($post);

    $host->data['pairs'] = ['1~2', '5~6'];

    expect($schema->getData())->toBe($post)
        ->and(array_values($post instanceof Post ? $post->getPairs()->toArray() : []))->toBe([
            $entityManager->find(CompositePair::class, ['a' => 1, 'b' => 2]),
            $entityManager->find(CompositePair::class, ['a' => 5, 'b' => 6]),
        ]);

    $entityManager->flush();
    $entityManager->clear();

    $reloaded = $entityManager->find(Post::class, 1);

    $labels = array_map(
        static fn (CompositePair $pair): string => $pair->getLabel(),
        $reloaded instanceof Post ? $reloaded->getPairs()->toArray() : [],
    );

    sort($labels);

    expect($labels)->toBe(['five-six', 'one-two']);
});

it('empties a composite many-to-many on an empty submission', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    compositeSelectListSeed($entityManager);

    $post = $entityManager->find(Post::class, 1);

    $field = CheckboxList::make('pairs')->relationship(titleAttribute: 'label');
    [$schema, $host] = compositeSelectListWire($field, $post ?? new Post());

    $schema->fill($post);

    $host->data['pairs'] = [];

    expect($schema->getData())->toBe($post);

    $entityManager->flush();
    $entityManager->clear();

    $reloaded = $entityManager->find(Post::class, 1);

    expect($reloaded instanceof Post ? $reloaded->getPairs() : null)->toHaveCount(0)
        ->and($entityManager->getRepository(CompositePair::class)->count([]))->toBe(3);
});

it('lists and writes a derived-identity target through the same to-many arm', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    compositeSelectListSeed($entityManager);

    $post = $entityManager->find(Post::class, 1);

    $field = CheckboxList::make('linkedTranslations')->relationship(titleAttribute: 'title');
    [$schema, $host] = compositeSelectListWire($field, $post ?? new Post());

    $schema->fill($post);

    expect($field->getOptions())->toBe(['1~en' => 'English', '2~de' => 'German'])
        ->and($host->data['linkedTranslations'])->toBe(['1~en']);

    $host->data['linkedTranslations'] = ['2~de'];

    expect($schema->getData())->toBe($post)
        ->and(array_values($post instanceof Post ? $post->getLinkedTranslations()->toArray() : []))->toBe([
            $entityManager->find(PostTranslation::class, ['post' => 2, 'locale' => 'de']),
        ]);
});
