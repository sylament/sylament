<?php

declare(strict_types=1);

namespace Sylament\Forms;

/**
 * Swap the entry at `$key` with the one that follows it, preserving every key and
 * value. An unknown key, or a key that is already last, returns the array as it is.
 * The key is matched by its string form, so the `'2'` a client sends finds the
 * integer key `2` a positionally keyed list carries.
 *
 * @template TKey of array-key
 * @template TValue
 *
 * @param array<TKey, TValue> $array
 *
 * @return array<TKey, TValue>
 */
function array_move_after(array $array, int|string $key): array
{
    $keys = array_keys($array);
    $index = array_search((string) $key, array_map(strval(...), $keys), true);

    if ($index === false) {
        return $array;
    }

    $nextKey = $keys[$index + 1] ?? null;

    if ($nextKey === null) {
        return $array;
    }

    $keys[$index + 1] = $keys[$index];
    $keys[$index] = $nextKey;

    $moved = [];

    foreach ($keys as $movedKey) {
        $moved[$movedKey] = $array[$movedKey];
    }

    return $moved;
}

/**
 * Swap the entry at `$key` with the one that precedes it, preserving every key and
 * value. An unknown key, or a key that is already first, returns the array as it is.
 * The key is matched by its string form, so the `'2'` a client sends finds the
 * integer key `2` a positionally keyed list carries.
 *
 * @template TKey of array-key
 * @template TValue
 *
 * @param array<TKey, TValue> $array
 *
 * @return array<TKey, TValue>
 */
function array_move_before(array $array, int|string $key): array
{
    $keys = array_keys($array);
    $index = array_search((string) $key, array_map(strval(...), $keys), true);

    if ($index === false) {
        return $array;
    }

    $previousKey = $keys[$index - 1] ?? null;

    if ($previousKey === null) {
        return $array;
    }

    $keys[$index - 1] = $keys[$index];
    $keys[$index] = $previousKey;

    $moved = [];

    foreach ($keys as $movedKey) {
        $moved[$movedKey] = $array[$movedKey];
    }

    return $moved;
}
