<?php

declare(strict_types=1);

use Sylament\Testing\Doctrine\SqlNormalizer;

it("renumbers the walker's table aliases and the result aliases by first appearance", function (): void {
    expect(SqlNormalizer::normalize('SELECT s0_.code AS code_0, s0_.caption AS caption_1, s0_.catalogue_id AS catalogue_id_2 FROM Stamp s0_ WHERE s0_.catalogue_id = ? AND (s0_.catalogue_id = ? AND s0_.code = ?)'))
        ->toBe('SELECT a0_.code AS code_0, a0_.caption AS caption_1, a0_.catalogue_id AS catalogue_id_2 FROM Stamp a0_ WHERE a0_.catalogue_id = ? AND (a0_.catalogue_id = ? AND a0_.code = ?)');
});

it("renumbers the persister's aliases apart from the walker's", function (): void {
    expect(SqlNormalizer::normalize('SELECT t0.code AS code_1, t0.title AS title_2, t0.binder_id AS binder_id_3 FROM Sheet t0 WHERE t0.binder_id = ? AND t0.code = ?'))
        ->toBe('SELECT p0.code AS code_0, p0.title AS title_1, p0.binder_id AS binder_id_2 FROM Sheet p0 WHERE p0.binder_id = ? AND p0.code = ?');
});

it('answers the same text whatever numbers Doctrine minted', function (): void {
    $minted = 'SELECT r0_.id AS id_0, c1_.name AS name_1 FROM Residency r0_ LEFT JOIN Circuit c1_ ON r0_.related_region = c1_.region';
    $renumbered = 'SELECT r4_.id AS id_7, c9_.name AS name_8 FROM Residency r4_ LEFT JOIN Circuit c9_ ON r4_.related_region = c9_.region';

    expect(SqlNormalizer::normalize($renumbered))->toBe(SqlNormalizer::normalize($minted))
        ->and(SqlNormalizer::normalize($minted))->toBe('SELECT a0_.id AS id_0, a1_.name AS name_1 FROM Residency a0_ LEFT JOIN Circuit a1_ ON a0_.related_region = a1_.region');
});

it('keeps two distinct aliases distinct', function (): void {
    expect(SqlNormalizer::normalize('SELECT p1_.id FROM Post p1_ INNER JOIN Tag t2_ ON t2_.post_id = p1_.id WHERE t2_.name = ?'))
        ->toBe('SELECT a0_.id FROM Post a0_ INNER JOIN Tag a1_ ON a1_.post_id = a0_.id WHERE a1_.name = ?');
});

it('renumbers a scalar result alias wherever the statement names it', function (): void {
    expect(SqlNormalizer::normalize('SELECT p3_.id AS id_0, 1 AS sclr_2 FROM Post p3_ ORDER BY sclr_2 DESC'))
        ->toBe('SELECT a0_.id AS id_0, 1 AS sclr_1 FROM Post a0_ ORDER BY sclr_1 DESC');
});

it('leaves a statement with no minted name untouched', function (): void {
    $sql = 'UPDATE "order" SET "select" = NULL WHERE "group" IN (?)';

    expect(SqlNormalizer::normalize($sql))->toBe($sql);
});

it('renumbers an alias the toolkit names itself in the one sequence', function (): void {
    expect(SqlNormalizer::normalize('SELECT SUM(s0_.hits) AS sy_summary_1 FROM SiteRedirect s0_'))
        ->toBe('SELECT SUM(a0_.hits) AS sy_summary_0 FROM SiteRedirect a0_');
});
