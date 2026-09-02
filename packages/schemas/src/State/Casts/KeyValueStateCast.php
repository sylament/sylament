<?php

declare(strict_types=1);

namespace Sylament\Schemas\State\Casts;

use LogicException;
use Sylament\Schemas\State\Casts\Contracts\StateCast;

use function Sylament\Support\blank;

class KeyValueStateCast implements StateCast
{
    /**
     * @return array<array-key, mixed>
     */
    public function get(mixed $state): array
    {
        $state = $this->normalize($state);

        if (($state === []) || ! is_array(array_first($state))) {
            return $state;
        }

        $map = [];

        foreach ($state as $row) {
            $key = is_array($row) ? ($row['key'] ?? null) : null;

            $map[(is_string($key) || is_int($key)) ? $key : ''] = is_array($row) ? ($row['value'] ?? null) : null;
        }

        unset($map['']);

        return $map;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function set(mixed $state): array
    {
        $state = $this->normalize($state);

        if (($state === []) || is_array(array_first($state))) {
            return $state;
        }

        unset($state['']);

        return array_map(
            static fn (mixed $value, int|string $key): array => ['key' => $key, 'value' => $value],
            $state,
            array_keys($state),
        );
    }

    /**
     * @return array<array-key, mixed>
     */
    protected function normalize(mixed $state): array
    {
        if (blank($state)) {
            return [];
        }

        $decoded = is_string($state)
            ? json_decode($state, associative: true, flags: JSON_THROW_ON_ERROR)
            : $state;

        if (! is_array($decoded)) {
            throw new LogicException(sprintf(
                'KeyValueStateCast met a %s state; a key-value state is a list of rows, a map, or the JSON string of either.',
                get_debug_type($decoded),
            ));
        }

        return $decoded;
    }
}
