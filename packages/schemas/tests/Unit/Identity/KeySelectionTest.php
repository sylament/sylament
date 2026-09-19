<?php

declare(strict_types=1);

use Sylament\Schemas\Identity\IdentifierConstraint;
use Sylament\Schemas\Identity\RecordKey;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Listing;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\ListingNote;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Placement;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Post;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\PostSeal;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\PostSealNote;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\PostTranslation;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Redirect;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Ticket;
use Sylament\Schemas\Tests\Fixtures\Doctrine\IdentityRig;

it('selects an identifier association through IDENTITY() and a scalar field by its path', function (): void {
    $query = IdentityRig::create()->createQueryBuilder()->from(PostTranslation::class, 'record');

    expect(IdentifierConstraint::select($query, 'record', PostTranslation::class)->expressions)
        ->toBe(['IDENTITY(record.post) AS sy_key_0', 'record.locale AS sy_key_1'])
        ->and(IdentifierConstraint::select($query, 'record', PostTranslation::class, 'sy_owner')->expressions)
        ->toBe(['IDENTITY(record.post) AS sy_owner_0', 'record.locale AS sy_owner_1']);
});

/*
 * No hydrator converts every key column: the scalar one converts none, the array one
 * converts a field path but not IDENTITY(), which it types as a string. So a row is keyed
 * by converting each raw value through its field's own type, and both shapes must agree
 * with the record.
 */
it('mints from a result row the key the record mints, whichever way the row was hydrated', function (string $class, string $hydration): void {
    $entityManager = IdentityRig::create();
    $query = $entityManager->createQueryBuilder()->from($class, 'record');
    $selection = IdentifierConstraint::select($query, 'record', $class);
    $query->select(...$selection->expressions);

    /** @var list<array<string, mixed>> $rows */
    $rows = $hydration === 'scalar' ? $query->getQuery()->getScalarResult() : $query->getQuery()->getArrayResult();
    $rowKeys = array_map($selection->keyOf(...), $rows);

    /** @var list<object> $records */
    $records = $entityManager->createQueryBuilder()->select('record')->from($class, 'record')->getQuery()->getResult();
    $recordKeys = array_map(static fn (object $record): string => RecordKey::of($entityManager, $record), $records);

    sort($rowKeys);
    sort($recordKeys);

    expect($rowKeys)->not->toBe([])
        ->and($rowKeys)->toBe($recordKeys);
})->with([
    'two scalar fields' => [Redirect::class],
    'a derived identity' => [PostTranslation::class],
    'a single derived identity' => [PostSeal::class],
    'a chained derived identity' => [PostSealNote::class],
    'a backed enum component' => [Placement::class],
    'a single identifier' => [Post::class],
    'a converting scalar identifier' => [Listing::class],
    'an association onto a converting identifier' => [ListingNote::class],
    'a guid and a bigint' => [Ticket::class],
])->with(['scalar', 'array']);

it('keys a row that carries other columns beside the key', function (): void {
    $query = IdentityRig::create()->createQueryBuilder()->from(PostTranslation::class, 'record');
    $selection = IdentifierConstraint::select($query, 'record', PostTranslation::class);

    /** @var list<array<string, mixed>> $rows */
    $rows = $query
        ->select(...$selection->expressions)
        ->addSelect('record.title AS sy_title')
        ->where("record.locale = 'pt-BR'")
        ->getQuery()
        ->getArrayResult();

    expect($rows)->toHaveCount(1)
        ->and($selection->keyOf($rows[0]))->toBe('1~pt-BR');
});

it('refuses a row that was not read through the selection', function (): void {
    $query = IdentityRig::create()->createQueryBuilder()->from(PostTranslation::class, 'record');

    expect(fn (): string => IdentifierConstraint::select($query, 'record', PostTranslation::class)->keyOf(['sy_key_0' => 1]))
        ->toThrow(LogicException::class, 'lacks the key column [sy_key_1]');
});
