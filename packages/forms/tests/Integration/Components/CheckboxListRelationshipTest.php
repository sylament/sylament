<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use LogicException;
use Sylament\Forms\Components\CheckboxList;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Post;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Tag;
use Sylament\Schemas\Schema;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Twig\Markup;

use function Sylament\Forms\Tests\renderToHtml;

/*
 * CheckboxList::relationship() against a real Doctrine entity manager. The field
 * is inherently multiple, so it rides the to-many arm of the same
 * `HasRelationship` mechanics `Select::multiple()->relationship()` uses — the
 * point of these cases is that the shared arm serves a second field unchanged,
 * plus the two record-shaped closures only this field declares.
 */

/**
 * Bind a checkbox list to a record instance through a `data`-bag host, keyed and
 * validator-fed so `getData()` can run its validation pass.
 *
 * @return array{Schema, FieldRenderHost}
 */
function wireRecordCheckboxList(CheckboxList $field, object $record): array
{
    $host = new FieldRenderHost();
    $host->setSchemaStateValidator(test()->getContainer()->get(ValidatorInterface::class));

    $schema = Schema::make($host)->statePath('data')->record($record)->components([$field]);

    $host->cacheSchema('schema', $schema);
    $schema->getComponents();

    return [$schema, $host];
}

/**
 * A flushed post tagged `a` and `b`, with `c` available as the third option.
 *
 * @return array{Post, Tag, Tag, Tag}
 */
function seedTagCatalogue(EntityManagerInterface $entityManager): array
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

it('enumerates the target table and fills the stored keys', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, $a, $b, $c] = seedTagCatalogue($entityManager);

    $field = CheckboxList::make('tags')->relationship(titleAttribute: 'name');
    [$schema, $host] = wireRecordCheckboxList($field, $post);

    $schema->fill($post);

    expect($field->getOptions())->toBe([
        $a->getId() => 'a',
        $b->getId() => 'b',
        $c->getId() => 'c',
    ])
        ->and($host->data['tags'])->toBe([(string) $a->getId(), (string) $b->getId()]);
});

it('writes the desired list back onto the association through getData', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, $a, , $c] = seedTagCatalogue($entityManager);

    $field = CheckboxList::make('tags')->relationship(titleAttribute: 'name');
    [$schema, $host] = wireRecordCheckboxList($field, $post);

    $schema->fill($post);

    $host->data['tags'] = [(string) $a->getId(), (string) $c->getId()];

    expect($schema->getData())->toBe($post);

    $entityManager->flush();

    $names = array_map(static fn (Tag $tag): string => $tag->getName(), $post->getTags()->toArray());
    sort($names);

    expect($names)->toBe(['a', 'c'])
        ->and($entityManager->getRepository(Tag::class)->count([]))->toBe(3);
});

it('empties the association on an empty submission', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post] = seedTagCatalogue($entityManager);

    $field = CheckboxList::make('tags')->relationship(titleAttribute: 'name');
    [$schema, $host] = wireRecordCheckboxList($field, $post);

    $schema->fill($post);

    $host->data['tags'] = [];

    expect($schema->getData())->toBe($post);

    $entityManager->flush();

    expect($post->getTags()->count())->toBe(0);
});

it('scopes the option query through modifyQueryUsing', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, $a] = seedTagCatalogue($entityManager);

    $field = CheckboxList::make('tags')->relationship(
        titleAttribute: 'name',
        modifyQueryUsing: fn (QueryBuilder $query): QueryBuilder => $query->where("record.name = 'a'"),
    );

    wireRecordCheckboxList($field, $post);

    expect($field->getOptions())->toBe([$a->getId() => 'a']);
});

it('refuses a to-one association, which this field can never speak', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post] = seedTagCatalogue($entityManager);

    $field = CheckboxList::make('author')->relationship(titleAttribute: 'name');
    wireRecordCheckboxList($field, $post);

    expect(fn (): array => $field->getOptions())
        ->toThrow(LogicException::class, 'holds a single');
});

it('throws loudly when the relationship declares no title attribute', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post] = seedTagCatalogue($entityManager);

    $field = CheckboxList::make('tags')->relationship();
    wireRecordCheckboxList($field, $post);

    expect(fn (): array => $field->getOptions())
        ->toThrow(LogicException::class, 'titleAttribute');
});

it('derives the label from the relationship name when none is set', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post] = seedTagCatalogue($entityManager);

    $derived = CheckboxList::make('tagIds')->relationship('tags', 'name');
    wireRecordCheckboxList($derived, $post);

    $explicit = CheckboxList::make('tagIds')->relationship('tags', 'name')->label('Labels');
    wireRecordCheckboxList($explicit, $post);

    expect($derived->getLabel())->toBe('Tags')
        ->and($explicit->getLabel())->toBe('Labels')
        ->and(CheckboxList::make('tagIds')->getLabel())->toBe('Tag ids');
});

it('builds labels and descriptions from the records themselves', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, $a, $b, $c] = seedTagCatalogue($entityManager);

    $field = CheckboxList::make('tags')
        ->relationship(titleAttribute: 'name')
        ->getOptionLabelFromRecordUsing(fn (Tag $record): string => mb_strtoupper($record->getName()))
        ->getOptionDescriptionFromRecordUsing(fn (object $record): ?string => ($record instanceof Tag && $record->getName() === 'a')
            ? 'the first one'
            : null);

    wireRecordCheckboxList($field, $post);

    expect($field->getOptions())->toBe([
        $a->getId() => 'A',
        $b->getId() => 'B',
        $c->getId() => 'C',
    ])
        ->and($field->hasOptionLabelFromRecordUsingCallback())->toBeTrue()
        ->and($field->hasOptionDescriptionFromRecordUsingCallback())->toBeTrue()
        ->and($field->getDescriptions())->toBe([$a->getId() => 'the first one'])
        ->and($field->hasDescription($b->getId()))->toBeFalse();
});

/*
 * A record-shaped label is `string|Markup` all the way to the template, so a
 * `Markup` one renders raw without `allowHtml()` — the house escaping contract,
 * which a `(string)` coercion anywhere along the option map would silently break.
 */
it('carries a Markup record label through the option map unflattened', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, $a] = seedTagCatalogue($entityManager);

    $field = CheckboxList::make('tags')
        ->relationship(titleAttribute: 'name')
        ->getOptionLabelFromRecordUsing(fn (Tag $record): Markup => new Markup('<b>' . $record->getName() . '</b>', 'UTF-8'));

    [$schema] = wireRecordCheckboxList($field, $post);

    expect($field->getOptions()[$a->getId()])->toBeInstanceOf(Markup::class)
        ->and(renderToHtml($schema))->toContain('<b>a</b>');
});

/*
 * The state stays in the dehydrated bag where Filament prunes it with
 * `dehydrated(false)`, because here the bag is what routes a path to its save
 * hook — the port-wide reading recorded against `relationship()`.
 */
it('keeps the relationship state in the dehydrated bag', function (): void {
    $entityManager = $this::getContainer()->get(EntityManagerInterface::class);
    [$post, $a] = seedTagCatalogue($entityManager);

    $field = CheckboxList::make('tags')->relationship(titleAttribute: 'name');
    [$schema] = wireRecordCheckboxList($field, $post);

    $schema->fill($post);

    expect($field->isDehydrated())->toBeTrue()
        ->and($schema->getRawState()['tags'] ?? null)->toContain((string) $a->getId());
});
