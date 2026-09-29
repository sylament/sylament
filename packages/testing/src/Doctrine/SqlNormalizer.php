<?php

declare(strict_types=1);

namespace Sylament\Testing\Doctrine;

use RuntimeException;

/**
 * Renumbers the names Doctrine mints into a statement by order of first appearance,
 * so a pin on the statement survives a release that renumbers them while distinct
 * names stay distinct:
 * - the SQL walker's table aliases (`s0_`) become `a0_`, `a1_`, …;
 * - the persister's (`t0`) become `p0`, `p1`, …;
 * - each result alias (`code_1`, `sclr_2`) keeps its stem and takes the next number.
 */
final class SqlNormalizer
{
    public static function normalize(string $sql): string
    {
        $walker = [];
        $sql = preg_replace_callback('/\b[a-z]\d+_(?![A-Za-z0-9_])/', static function (array $match) use (&$walker): string {
            return $walker[$match[0]] ??= 'a' . count($walker) . '_';
        }, $sql) ?? throw new RuntimeException(preg_last_error_msg());

        $persister = [];
        $sql = preg_replace_callback('/\bt\d+\b(?=\.|\s|$)/', static function (array $match) use (&$persister): string {
            return $persister[$match[0]] ??= 'p' . count($persister);
        }, $sql) ?? throw new RuntimeException(preg_last_error_msg());

        preg_match_all('/\bAS ((\w+?)_\d+)\b/', $sql, $matches, PREG_SET_ORDER);
        $results = [];

        foreach ($matches as [, $alias, $stem]) {
            $results[$alias] ??= $stem . '_' . count($results);
        }

        if ($results === []) {
            return $sql;
        }

        $aliases = implode('|', array_map(static fn (string $alias): string => preg_quote($alias, '/'), array_keys($results)));

        return preg_replace_callback('/\b(?:' . $aliases . ')\b/', static fn (array $match): string => $results[$match[0]], $sql)
            ?? throw new RuntimeException(preg_last_error_msg());
    }
}
