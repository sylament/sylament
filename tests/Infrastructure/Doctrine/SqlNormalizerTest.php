<?php

declare(strict_types=1);

use Sylament\Tests\Infrastructure\Doctrine\SqlNormalizer;

it("renumbers each name Doctrine mints by first appearance, keeping distinct names distinct", function (string $minted, string $normalized): void {
    expect(SqlNormalizer::normalize($minted))->toBe($normalized);
})->with([
    "the SQL walker's table aliases" => [
        'SELECT p4_.id FROM Post p4_ INNER JOIN Tag t9_ ON t9_.post_id = p4_.id',
        'SELECT a0_.id FROM Post a0_ INNER JOIN Tag a1_ ON a1_.post_id = a0_.id',
    ],
    "the persister's table aliases, apart from the walker's" => [
        'SELECT t3.code FROM Sheet t3 INNER JOIN Binder t7 ON t3.binder_id = t7.id',
        'SELECT p0.code FROM Sheet p0 INNER JOIN Binder p1 ON p0.binder_id = p1.id',
    ],
    'a result alias, wherever the statement names it' => [
        'SELECT s2_.id AS id_4, 1 AS sclr_7 FROM Post s2_ ORDER BY sclr_7 DESC',
        'SELECT a0_.id AS id_0, 1 AS sclr_1 FROM Post a0_ ORDER BY sclr_1 DESC',
    ],
    'an alias the toolkit names itself, in the same sequence' => [
        'SELECT SUM(s0_.hits) AS sy_summary_1 FROM SiteRedirect s0_',
        'SELECT SUM(a0_.hits) AS sy_summary_0 FROM SiteRedirect a0_',
    ],
    'no minted name' => [
        'UPDATE "order" SET "select" = NULL WHERE "group" IN (?)',
        'UPDATE "order" SET "select" = NULL WHERE "group" IN (?)',
    ],
]);
