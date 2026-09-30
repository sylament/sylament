<?php

declare(strict_types=1);

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use Sylament\Schemas\Identity\IdentifierConstraint;
use Sylament\Schemas\Tests\Fixtures\Doctrine\CompositeOwnerRig;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\CompositePair;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Crate;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Listing;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\ListingNote;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Pallet;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Placement;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Post;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\PostSeal;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\PostSealNote;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\PostTranslation;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Redirect;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Shortlink;
use Sylament\Schemas\Tests\Fixtures\Doctrine\EntityManagerFactory;
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

it('binds a single string identifier as a list of strings, not of integers', function (): void {
    $entityManager = IdentityRig::create();
    $shortlinks = [];

    foreach (['go', 'stay'] as $code) {
        $shortlink = new Shortlink();
        $shortlink->setCode($code);
        $shortlink->setTarget("/{$code}");
        $shortlinks[] = $shortlink;
    }

    EntityManagerFactory::seed($entityManager, ...$shortlinks);

    $query = identityQuery($entityManager, Shortlink::class, 'link');

    $constraint = IdentifierConstraint::in($query, 'link', Shortlink::class, ['go'], 'sylament_selected_record_keys');

    /** @var list<Shortlink> $found */
    $found = $query->andWhere($constraint)->getQuery()->getResult();

    expect($constraint)->toBe('link.code IN (:sylament_selected_record_keys)')
        ->and($query->getParameter('sylament_selected_record_keys')?->getType())->toBe(ArrayParameterType::STRING)
        ->and(array_map(static fn (Shortlink $shortlink): string => $shortlink->getCode(), $found))->toBe(['go']);
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
    $composite = identityQuery($entityManager, PostTranslation::class)->orderBy('record.locale', SortDirection::Descending);
    $single = identityQuery($entityManager, Post::class, 'post')->orderBy('post.title', SortDirection::Ascending);
    $ordered = identityQuery($entityManager, Post::class, 'post')->orderBy('post.id', SortDirection::Descending);

    IdentifierConstraint::orderBy($composite, 'record', PostTranslation::class, 'desc');
    IdentifierConstraint::orderBy($single, 'post', Post::class, 'asc');
    IdentifierConstraint::orderBy($ordered, 'post', Post::class, 'asc');

    expect($composite->getDQL())->toEndWith('ORDER BY record.locale DESC, record.post DESC')
        ->and($single->getDQL())->toEndWith('ORDER BY post.title ASC, post.id ASC')
        ->and($ordered->getDQL())->toEndWith('ORDER BY post.id DESC')
        ->and(identityTitles($composite))->toBe(['en', 'en/GB', 'pt-BR']);
});

it('chunks a key list to what one statement carries', function (): void {
    $entityManager = IdentityRig::create();

    expect(IdentifierConstraint::chunk($entityManager, PostTranslation::class, []))->toBe([])
        ->and(array_map(count(...), IdentifierConstraint::chunk($entityManager, PostTranslation::class, array_map(static fn (int $index): string => "{$index}~en", range(1, 1201)))))->toBe([500, 500, 201])
        ->and(IdentifierConstraint::chunk($entityManager, PostTranslation::class, ['1~en', '1~en', '2~en']))->toBe([['1~en', '2~en']])
        ->and(array_map(count(...), IdentifierConstraint::chunk($entityManager, Post::class, array_map(strval(...), range(1, 4001)))))->toBe([2000, 2000, 1]);
});

it('binds a raw-bytes uuid as the sixteen bytes its column holds, alone and as a batch', function (): void {
    $entityManager = CompositeOwnerRig::create();

    $equals = identityQuery($entityManager, Crate::class);
    $notEquals = identityQuery($entityManager, Crate::class);
    $in = identityQuery($entityManager, Crate::class);
    $notIn = identityQuery($entityManager, Crate::class);

    $labels = static function (QueryBuilder $query): array {
        /** @var list<Crate> $crates */
        $crates = $query->getQuery()->getResult();
        $labels = array_map(static fn (Crate $crate): string => $crate->getLabel(), $crates);

        sort($labels);

        return $labels;
    };

    $equalsConstraint = IdentifierConstraint::equals($equals, 'record', Crate::class, 'a1b2c3d4-0000-4000-8000-000000000001', 'key');
    $notEqualsConstraint = IdentifierConstraint::notEquals($notEquals, 'record', Crate::class, 'a1b2c3d4-0000-4000-8000-000000000001', 'key');
    $inConstraint = IdentifierConstraint::in($in, 'record', Crate::class, ['a1b2c3d4-0000-4000-8000-000000000001', 'a1b2c3d4-0000-4000-8000-000000000002', 'a1b2c3d4-0000-4000-8000-000000000003', 'A1B2C3D4-0000-4000-8000-000000000001'], 'keys');
    $notInConstraint = IdentifierConstraint::notIn($notIn, 'record', Crate::class, ['a1b2c3d4-0000-4000-8000-000000000001'], 'keys');

    /** @var list<string> $inValues */
    $inValues = $in->getParameter('keys')?->getValue() ?? [];

    expect($equalsConstraint)->toBe('record.id = :key')
        ->and(bin2hex((string) $equals->getParameter('key')?->getValue()))->toBe('a1b2c3d4000040008000000000000001')
        ->and($equals->getParameter('key')?->getType())->toBe(ParameterType::BINARY)
        ->and($labels($equals->andWhere((string) $equalsConstraint)))->toBe(['mine'])
        ->and($notEqualsConstraint)->toBe('record.id <> :key')
        ->and($notEquals->getParameter('key')?->getType())->toBe(ParameterType::BINARY)
        ->and($labels($notEquals->andWhere((string) $notEqualsConstraint)))->toBe(['apart'])
        ->and($inConstraint)->toBe('record.id IN (:keys)')
        ->and(array_map(bin2hex(...), $inValues))->toBe(['a1b2c3d4000040008000000000000001', 'a1b2c3d4000040008000000000000002', 'a1b2c3d4000040008000000000000003'])
        ->and($in->getParameter('keys')?->getType())->toBe(ArrayParameterType::BINARY)
        ->and($labels($in->andWhere($inConstraint)))->toBe(['apart', 'mine'])
        ->and($notInConstraint)->toBe('record.id NOT IN (:keys)')
        ->and($notIn->getParameter('keys')?->getType())->toBe(ArrayParameterType::BINARY)
        ->and($labels($notIn->andWhere((string) $notInConstraint)))->toBe(['apart']);
});

it('binds a raw-bytes half and a string half each under its own type', function (): void {
    $entityManager = CompositeOwnerRig::create();

    $equals = identityQuery($entityManager, Pallet::class);
    $notEquals = identityQuery($entityManager, Pallet::class);
    $in = identityQuery($entityManager, Pallet::class);
    $notIn = identityQuery($entityManager, Pallet::class);

    $lanes = static function (QueryBuilder $query): array {
        /** @var list<Pallet> $pallets */
        $pallets = $query->getQuery()->getResult();
        $lanes = array_map(static fn (Pallet $pallet): string => $pallet->getBatch() . '/' . $pallet->getLane(), $pallets);

        sort($lanes);

        return $lanes;
    };

    $equalsConstraint = IdentifierConstraint::equals($equals, 'record', Pallet::class, 'b1b2c3d4-0000-4000-8000-000000000001~north', 'key');
    $notEqualsConstraint = IdentifierConstraint::notEquals($notEquals, 'record', Pallet::class, 'b1b2c3d4-0000-4000-8000-000000000001~north', 'key');
    $inConstraint = IdentifierConstraint::in($in, 'record', Pallet::class, ['b1b2c3d4-0000-4000-8000-000000000001~north', 'b1b2c3d4-0000-4000-8000-000000000002~north', 'b1b2c3d4-0000-4000-8000-000000000003~north'], 'keys');
    $notInConstraint = IdentifierConstraint::notIn($notIn, 'record', Pallet::class, ['b1b2c3d4-0000-4000-8000-000000000001~north', 'b1b2c3d4-0000-4000-8000-000000000001~south'], 'keys');

    expect($equalsConstraint)->toBe('record.batch = :key_0 AND record.lane = :key_1')
        ->and(bin2hex((string) $equals->getParameter('key_0')?->getValue()))->toBe('b1b2c3d4000040008000000000000001')
        ->and($equals->getParameter('key_0')?->getType())->toBe(ParameterType::BINARY)
        ->and($equals->getParameter('key_1')?->getValue())->toBe('north')
        ->and($equals->getParameter('key_1')?->getType())->toBe(ParameterType::STRING)
        ->and($lanes($equals->andWhere((string) $equalsConstraint)))->toBe(['b1b2c3d4-0000-4000-8000-000000000001/north'])
        ->and($notEqualsConstraint)->toBe('NOT (record.batch = :key_0 AND record.lane = :key_1)')
        ->and($lanes($notEquals->andWhere((string) $notEqualsConstraint)))->toBe(['b1b2c3d4-0000-4000-8000-000000000001/south', 'b1b2c3d4-0000-4000-8000-000000000002/north'])
        ->and($inConstraint)->toBe('((record.batch = :keys_0_0 AND record.lane = :keys_0_1) OR ((record.batch = :keys_1_0 AND record.lane = :keys_1_1) OR (record.batch = :keys_2_0 AND record.lane = :keys_2_1)))')
        ->and($in->getParameter('keys_1_0')?->getType())->toBe(ParameterType::BINARY)
        ->and($in->getParameter('keys_1_1')?->getType())->toBe(ParameterType::STRING)
        ->and($lanes($in->andWhere($inConstraint)))->toBe(['b1b2c3d4-0000-4000-8000-000000000001/north', 'b1b2c3d4-0000-4000-8000-000000000002/north'])
        ->and($notInConstraint)->toBe('NOT (((record.batch = :keys_0_0 AND record.lane = :keys_0_1) OR (record.batch = :keys_1_0 AND record.lane = :keys_1_1)))')
        ->and($lanes($notIn->andWhere((string) $notInConstraint)))->toBe(['b1b2c3d4-0000-4000-8000-000000000002/north']);
});

it('appends composite membership parameters after the ones the builder already carries', function (): void {
    $entityManager = EntityManagerFactory::create();
    EntityManagerFactory::seed(
        $entityManager,
        new CompositePair(1, 2, 'one-two'),
        new CompositePair(3, 4, 'three-four'),
        new CompositePair(5, 6, 'five-six'),
    );

    $query = $entityManager->createQueryBuilder()
        ->select('pair')
        ->from(CompositePair::class, 'pair')
        ->andWhere('pair.a >= :floor')
        ->setParameter('floor', 3);

    $query->andWhere(IdentifierConstraint::in($query, 'pair', CompositePair::class, ['1~2', '3~4', '5~6'], 'keys'));

    expect(array_values(array_map(
        static fn (Parameter $parameter): string => $parameter->getName(),
        $query->getParameters()->toArray(),
    )))->toBe(['floor', 'keys_0_0', 'keys_0_1', 'keys_1_0', 'keys_1_1', 'keys_2_0', 'keys_2_1'])
        ->and(array_map(
            static fn (CompositePair $pair): string => $pair->getLabel(),
            $query->getQuery()->getResult(),
        ))->toBe(['three-four', 'five-six']);
});

it('refuses a parameter base already bound on the builder rather than binding its keys twice', function (): void {
    $query = EntityManagerFactory::create()->createQueryBuilder()->select('pair')->from(CompositePair::class, 'pair');
    $query->andWhere(IdentifierConstraint::in($query, 'pair', CompositePair::class, ['1~2'], 'keys'));

    expect(fn (): string => IdentifierConstraint::in($query, 'pair', CompositePair::class, ['3~4'], 'keys'))
        ->toThrow(LogicException::class, 'The parameter base [keys] is already bound on this query builder');
});

it('builds a composite membership over 5,000 keys in linear time', function (): void {
    $query = EntityManagerFactory::create()->createQueryBuilder()->select('pair')->from(CompositePair::class, 'pair');
    $keys = array_map(static fn (int $index): string => "{$index}~{$index}", range(1, 5000));

    $started = hrtime(true);
    IdentifierConstraint::in($query, 'pair', CompositePair::class, $keys, 'keys');
    $elapsedMilliseconds = (hrtime(true) - $started) / 1e6;

    expect($query->getParameters())->toHaveCount(10000)
        ->and($elapsedMilliseconds)->toBeLessThan(500.0);
});

it('batches a composite key list by the chunk rule and a single one whole', function (): void {
    $entityManager = EntityManagerFactory::create();
    $composite = array_map(static fn (int $index): string => "{$index}~1", range(1, 1201));
    $single = array_map(strval(...), range(1, 2500));

    expect(array_map(count(...), IdentifierConstraint::batch($entityManager, CompositePair::class, $composite)))->toBe([500, 500, 201])
        ->and(array_map(count(...), IdentifierConstraint::batch($entityManager, Post::class, $single)))->toBe([2500])
        ->and(IdentifierConstraint::batch($entityManager, CompositePair::class, []))->toBe([])
        ->and(IdentifierConstraint::batch($entityManager, Post::class, []))->toBe([]);
});
