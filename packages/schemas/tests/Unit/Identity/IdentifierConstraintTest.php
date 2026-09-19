<?php

declare(strict_types=1);

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Sylament\Schemas\Identity\IdentifierConstraint;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Listing;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\ListingNote;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Placement;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Post;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\PostSeal;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\PostSealNote;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\PostTranslation;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Redirect;
use Sylament\Schemas\Tests\Fixtures\Doctrine\IdentityRig;

/**
 * @param class-string $class
 */
function identityQuery(EntityManager $entityManager, string $class, string $alias = 'record'): QueryBuilder
{
    return $entityManager->createQueryBuilder()->select($alias)->from($class, $alias);
}

/**
 * @return list<string>
 */
function identityTitles(QueryBuilder $query): array
{
    /** @var list<PostTranslation> $translations */
    $translations = $query->getQuery()->getResult();

    $titles = array_map(static fn (PostTranslation $translation): string => $translation->getTitle(), $translations);

    sort($titles);

    return $titles;
}

it('emits a single identifier exactly as the hand-written constraints did, now typed', function (): void {
    $entityManager = IdentityRig::create();

    $equals = identityQuery($entityManager, Post::class, 'post');
    $in = identityQuery($entityManager, Post::class, 'post');
    $notIn = identityQuery($entityManager, Post::class, 'post');
    $notEquals = identityQuery($entityManager, Post::class, 'post');

    expect(IdentifierConstraint::equals($equals, 'post', Post::class, '1', 'sylament_record_key'))->toBe('post.id = :sylament_record_key')
        ->and($equals->getParameter('sylament_record_key')?->getValue())->toBe(1)
        ->and($equals->getParameter('sylament_record_key')?->getType())->toBe(ParameterType::INTEGER)
        ->and(IdentifierConstraint::in($in, 'post', Post::class, ['1', '3', 'abc', '3'], 'sylament_selected_record_keys'))->toBe('post.id IN (:sylament_selected_record_keys)')
        ->and($in->getParameter('sylament_selected_record_keys')?->getValue())->toBe([1, 3])
        ->and($in->getParameter('sylament_selected_record_keys')?->getType())->toBe(ArrayParameterType::INTEGER)
        ->and(IdentifierConstraint::notIn($notIn, 'post', Post::class, ['2'], 'sylament_selected_record_keys'))->toBe('post.id NOT IN (:sylament_selected_record_keys)')
        ->and(IdentifierConstraint::notEquals($notEquals, 'post', Post::class, '1', 'sylament_ignored_record'))->toBe('post.id <> :sylament_ignored_record')
        ->and($notEquals->andWhere('post.id <> :sylament_ignored_record')->getQuery()->getResult())->toHaveCount(0)
        ->and($equals->andWhere('post.id = :sylament_record_key')->getQuery()->getResult())->toHaveCount(1);
});

it('refuses a parameter base that already ends in an index, which a composite key would collide with', function (): void {
    $query = identityQuery(IdentityRig::create(), PostTranslation::class);

    expect(fn (): ?string => IdentifierConstraint::equals($query, 'record', PostTranslation::class, '1~en', 'keys_1'))
        ->toThrow(LogicException::class, 'ends in an index of its own')
        ->and(fn (): ?string => IdentifierConstraint::notEquals($query, 'record', PostTranslation::class, '1~en', 'keys_1'))
        ->toThrow(LogicException::class, 'ends in an index of its own')
        ->and(fn (): string => IdentifierConstraint::in($query, 'record', PostTranslation::class, ['1~en'], 'keys_1_0'))
        ->toThrow(LogicException::class, 'ends in an index of its own');
});

it('narrows to the one record a composite key names', function (): void {
    $entityManager = IdentityRig::create();
    $query = identityQuery($entityManager, PostTranslation::class);

    $constraint = IdentifierConstraint::equals($query, 'record', PostTranslation::class, '1~en=2FGB', 'sylament_record_key');

    expect($constraint)->toBe('record.post = :sylament_record_key_0 AND record.locale = :sylament_record_key_1')
        ->and($query->getParameter('sylament_record_key_0')?->getType())->toBe(ParameterType::INTEGER)
        ->and($query->getParameter('sylament_record_key_1')?->getType())->toBe(ParameterType::STRING)
        ->and(identityTitles($query->andWhere((string) $constraint)))->toBe(['en/GB']);
});

it('narrows by every other flavour too', function (string $class, string $key, string $expected): void {
    $query = identityQuery(IdentityRig::create(), $class);

    $constraint = IdentifierConstraint::equals($query, 'record', $class, $key, 'key');

    expect($constraint)->toBe($expected)
        ->and($query->andWhere((string) $constraint)->getQuery()->getResult())->toHaveCount(1);
})->with([
    'two scalar fields' => [Redirect::class, IdentityRig::HOSTILE_KEY, 'record.host = :key_0 AND record.path = :key_1'],
    'a single derived identity' => [PostSeal::class, '1', 'record.post = :key'],
    'a backed enum component' => [Placement::class, 'footer~2', 'record.zone = :key_0 AND record.slot = :key_1'],
    'a chained derived identity' => [PostSealNote::class, '1~n1', 'record.seal = :key_0 AND record.code = :key_1'],
    'an association onto a converting identifier' => [ListingNote::class, 'AB-123~n1', 'record.listing = :key_0 AND record.code = :key_1'],
]);

/*
 * A DBAL type's convertToDatabaseValue() is contracted for the PHP value, and a strict
 * one refuses the bare string a key carries — so the component goes through
 * convertToPHPValue() first, and what is bound is what the column holds.
 */
it('binds a strict value-object identifier as the value its column holds', function (): void {
    $query = identityQuery(IdentityRig::create(), Listing::class);

    $constraint = IdentifierConstraint::equals($query, 'record', Listing::class, 'AB-123', 'key');

    expect($constraint)->toBe('record.sku = :key')
        ->and($query->getParameter('key')?->getValue())->toBe('ab-123')
        ->and($query->andWhere((string) $constraint)->getQuery()->getResult())->toHaveCount(1)
        ->and(IdentifierConstraint::equals($query, 'record', Listing::class, 'not-a-sku', 'other'))->toBeNull()
        ->and(IdentifierConstraint::equals($query, 'record', Listing::class, 'ab-123', 'other'))->toBeNull();
});

it('names no record, and binds nothing, for a malformed key', function (): void {
    $query = identityQuery(IdentityRig::create(), PostTranslation::class);

    expect(IdentifierConstraint::equals($query, 'record', PostTranslation::class, 'one~en', 'key'))->toBeNull()
        ->and(IdentifierConstraint::equals($query, 'record', PostTranslation::class, '1', 'key'))->toBeNull()
        ->and(IdentifierConstraint::notEquals($query, 'record', PostTranslation::class, '1~en~x', 'key'))->toBeNull()
        ->and($query->getParameters())->toHaveCount(0);
});

it('matches a batch of composite keys as a balanced disjunction, dropping what is malformed or repeated', function (): void {
    $query = identityQuery(IdentityRig::create(), PostTranslation::class);

    $constraint = IdentifierConstraint::in($query, 'record', PostTranslation::class, ['1~en', 'junk', '1~pt-BR', '1~en', '1~en=2FGB'], 'keys');

    expect($constraint)->toBe(
        '((record.post = :keys_0_0 AND record.locale = :keys_0_1) OR ((record.post = :keys_1_0 AND record.locale = :keys_1_1) OR (record.post = :keys_2_0 AND record.locale = :keys_2_1)))'
    )->and(identityTitles($query->andWhere($constraint)))->toBe(['en', 'en/GB', 'pt-BR']);
});

it('excludes a batch of composite keys, and a single one', function (): void {
    $entityManager = IdentityRig::create();
    $batch = identityQuery($entityManager, PostTranslation::class);
    $single = identityQuery($entityManager, PostTranslation::class);

    $notIn = IdentifierConstraint::notIn($batch, 'record', PostTranslation::class, ['1~en', '1~pt-BR'], 'keys');
    $notEquals = IdentifierConstraint::notEquals($single, 'record', PostTranslation::class, '1~en', 'ignored');

    expect($notIn)->toBe('NOT (((record.post = :keys_0_0 AND record.locale = :keys_0_1) OR (record.post = :keys_1_0 AND record.locale = :keys_1_1)))')
        ->and(identityTitles($batch->andWhere((string) $notIn)))->toBe(['en/GB'])
        ->and($notEquals)->toBe('NOT (record.post = :ignored_0 AND record.locale = :ignored_1)')
        ->and(identityTitles($single->andWhere((string) $notEquals)))->toBe(['en/GB', 'pt-BR']);
});

it('answers an empty batch in PHP, since an empty disjunction does not parse', function (): void {
    $query = identityQuery(IdentityRig::create(), PostTranslation::class);

    expect(IdentifierConstraint::in($query, 'record', PostTranslation::class, [], 'keys'))->toBe('1 = 0')
        ->and(IdentifierConstraint::in($query, 'record', PostTranslation::class, ['junk'], 'keys'))->toBe('1 = 0')
        ->and(IdentifierConstraint::notIn($query, 'record', PostTranslation::class, [], 'keys'))->toBeNull()
        ->and(IdentifierConstraint::notIn($query, 'record', PostTranslation::class, ['junk'], 'keys'))->toBeNull()
        ->and($query->getParameters())->toHaveCount(0)
        ->and(identityTitles($query->andWhere('1 = 0')))->toBe([]);
});

it("keeps two applications on one builder apart through the caller's parameter base", function (): void {
    $query = identityQuery(IdentityRig::create(), PostTranslation::class);

    $query
        ->andWhere(IdentifierConstraint::in($query, 'record', PostTranslation::class, ['1~en', '1~pt-BR'], 'sylament_selected_record_keys'))
        ->andWhere(IdentifierConstraint::in($query, 'record', PostTranslation::class, ['1~pt-BR'], 'sylament_selectable_record_keys'));

    expect(identityTitles($query))->toBe(['pt-BR'])
        ->and($query->getParameters())->toHaveCount(6);
});

it('survives more keys than a flat disjunction could carry on SQLite', function (): void {
    $query = identityQuery(IdentityRig::create(), PostTranslation::class);
    $keys = ['1~en'];

    for ($index = 0; $index < 1500; $index++) {
        $keys[] = "{$index}~absent";
    }

    expect(identityTitles($query->andWhere(IdentifierConstraint::in($query, 'record', PostTranslation::class, $keys, 'keys'))))->toBe(['en']);
});

it('restricts a DQL delete to a batch of composite keys', function (): void {
    $entityManager = IdentityRig::create();
    $delete = $entityManager->createQueryBuilder()->delete(PostTranslation::class, 'sylament_deleted_record');

    $delete->where(IdentifierConstraint::in($delete, 'sylament_deleted_record', PostTranslation::class, ['1~en', '1~pt-BR'], 'sylament_selected_record_keys'));

    expect($delete->getQuery()->getSQL())->toBe('DELETE FROM PostTranslation WHERE ((post_id = ? AND locale = ?) OR (post_id = ? AND locale = ?))')
        ->and($delete->getQuery()->execute())->toBe(2)
        ->and(identityTitles(identityQuery($entityManager, PostTranslation::class)))->toBe(['en/GB']);
});

it('correlates an inner alias to an outer one on every identifier field', function (): void {
    $query = identityQuery(IdentityRig::create(), PostTranslation::class, 'outer_record');

    $correlation = IdentifierConstraint::correlate($query, 'inner_record', PostTranslation::class, 'outer_record');

    $query
        ->andWhere(sprintf('EXISTS (SELECT 1 FROM %s inner_record WHERE %s AND inner_record.title LIKE :title)', PostTranslation::class, $correlation))
        ->setParameter('title', 'pt%');

    expect($correlation)->toBe('inner_record.post = outer_record.post AND inner_record.locale = outer_record.locale')
        ->and(identityTitles($query))->toBe(['pt-BR']);
});

it('groups by the plain path of every identifier field', function (): void {
    $query = IdentityRig::create()->createQueryBuilder()->from(PostTranslation::class, 'record');
    $selection = IdentifierConstraint::select($query, 'record', PostTranslation::class);

    $groupBy = IdentifierConstraint::groupBy($query, 'record', PostTranslation::class);

    expect($groupBy)->toBe(['record.post', 'record.locale'])
        ->and($query->select(...$selection->expressions)->addSelect('COUNT(record.title) AS sy_value')->groupBy(...$groupBy)->getQuery()->getScalarResult())
        ->toHaveCount(3);
});

it('orders by the identifier fields that are not ordered yet', function (): void {
    $entityManager = IdentityRig::create();
    $composite = identityQuery($entityManager, PostTranslation::class)->orderBy('record.locale', 'desc');
    $single = identityQuery($entityManager, Post::class, 'post')->orderBy('post.title', 'asc');
    $ordered = identityQuery($entityManager, Post::class, 'post')->orderBy('post.id', 'desc');

    IdentifierConstraint::orderBy($composite, 'record', PostTranslation::class, 'desc');
    IdentifierConstraint::orderBy($single, 'post', Post::class, 'asc');
    IdentifierConstraint::orderBy($ordered, 'post', Post::class, 'asc');

    expect($composite->getDQL())->toEndWith('ORDER BY record.locale desc, record.post desc')
        ->and($single->getDQL())->toEndWith('ORDER BY post.title asc, post.id asc')
        ->and($ordered->getDQL())->toEndWith('ORDER BY post.id desc')
        ->and(identityTitles($composite))->toBe(['en', 'en/GB', 'pt-BR']);
});

it('chunks a key list to what one statement carries', function (): void {
    $entityManager = IdentityRig::create();

    expect(IdentifierConstraint::chunk($entityManager, PostTranslation::class, []))->toBe([])
        ->and(array_map(count(...), IdentifierConstraint::chunk($entityManager, PostTranslation::class, array_map(static fn (int $index): string => "{$index}~en", range(1, 1201)))))->toBe([500, 500, 201])
        ->and(IdentifierConstraint::chunk($entityManager, PostTranslation::class, ['1~en', '1~en', '2~en']))->toBe([['1~en', '2~en']])
        ->and(array_map(count(...), IdentifierConstraint::chunk($entityManager, Post::class, array_map(strval(...), range(1, 4001)))))->toBe([2000, 2000, 1]);
});
