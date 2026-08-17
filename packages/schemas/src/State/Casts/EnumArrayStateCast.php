<?php

declare(strict_types=1);

namespace Sylament\Schemas\State\Casts;

use BackedEnum;
use Sylament\Schemas\State\Casts\Contracts\StateCast;

use function Sylament\Support\blank;
use function Sylament\Support\wrap;

class EnumArrayStateCast implements StateCast
{
    protected EnumStateCast $enumStateCast;

    /**
     * @param class-string<BackedEnum> $enum
     */
    public function __construct(
        protected string $enum,
    ) {
        $this->enumStateCast = new EnumStateCast($enum);
    }

    /**
     * @return list<BackedEnum>
     */
    public function get(mixed $state): array
    {
        return $this->normalize($state, $this->enumStateCast->get(...));
    }

    /**
     * @return list<string>
     */
    public function set(mixed $state): array
    {
        return $this->normalize($state, $this->setItem(...));
    }

    /**
     * @template TValue of BackedEnum|string
     *
     * @param callable(mixed): ?TValue $castItem
     *
     * @return list<TValue>
     */
    protected function normalize(mixed $state, callable $castItem): array
    {
        $items = array_map($castItem, wrap($state));

        return array_values(array_filter($items, fn (BackedEnum|string|null $item): bool => $item !== null));
    }

    protected function setItem(mixed $state): ?string
    {
        if (blank($state)) {
            return null;
        }

        $state = $this->enumStateCast->set($state);

        // Security: mirror OptionStateCast — a non-scalar element would stringify
        // to a bogus option value ("Array"), so fail closed by dropping it.
        if (! is_scalar($state)) {
            return null;
        }

        return (string) $state;
    }
}
