<?php

declare(strict_types=1);

namespace Sylament\Schemas\State\Casts;

use Sylament\Schemas\State\Casts\Contracts\StateCast;

use function Sylament\Support\blank;
use function Sylament\Support\wrap;

class OptionsArrayStateCast implements StateCast
{
    protected OptionStateCast $optionStateCast;

    public function __construct(
        protected bool $isNullable = false,
    ) {
        $this->optionStateCast = new OptionStateCast();
    }

    /**
     * @return list<string|int>|null
     */
    public function get(mixed $state): ?array
    {
        if (blank($state)) {
            return $this->isNullable ? null : [];
        }

        return $this->normalize($state, $this->optionStateCast->get(...));
    }

    /**
     * @return list<string>|null
     */
    public function set(mixed $state): ?array
    {
        if (blank($state)) {
            return $this->isNullable ? null : [];
        }

        /** @var list<string> */
        return $this->normalize($state, $this->optionStateCast->set(...));
    }

    /**
     * @template TValue of string|int
     *
     * @param callable(mixed): ?TValue $castItem
     *
     * @return list<TValue>
     */
    protected function normalize(mixed $state, callable $castItem): array
    {
        $items = array_map($castItem, wrap($state));

        return array_values(array_filter($items, fn (string|int|null $item): bool => $item !== null));
    }
}
