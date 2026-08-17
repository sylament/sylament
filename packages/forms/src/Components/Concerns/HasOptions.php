<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\Concerns;

use BackedEnum;
use Closure;
use Sylament\Support\Contracts\HasLabel;
use Twig\Markup;
use UnitEnum;

trait HasOptions
{
    /**
     * @var array<string|int, string|Markup|array<string|int, string|Markup>>|class-string<UnitEnum>|Closure|null
     */
    protected array|string|Closure|null $options = null;

    /**
     * @param array<string|int, string|Markup|array<string|int, string|Markup>>|class-string<UnitEnum>|Closure|null $options
     */
    public function options(array|string|Closure|null $options): static
    {
        $this->options = $options;

        if (is_string($options) && enum_exists($options)) {
            $this->enum($options);
        }

        return $this;
    }

    /**
     * @return array<string|int, string|Markup|array<string|int, string|Markup>>
     */
    public function getOptions(): array
    {
        $options = $this->evaluate($this->options) ?? $this->getEnum() ?? [];

        if (is_string($options) && enum_exists($options)) {
            return self::getOptionsFromEnum($options);
        }

        return is_array($options) ? $options : [];
    }

    public function hasDynamicOptions(): bool
    {
        return $this->options instanceof Closure;
    }

    /**
     * The state an option template compares its own value against: the option
     * vocabulary is the array key the template renders, which a state carrying an
     * enum instance or a raw boolean does not speak.
     *
     * @return list<string|int>|string|int|null
     */
    public function getOptionComparisonState(): array|string|int|null
    {
        $state = $this->getState();

        if (! is_iterable($state)) {
            return $this->normalizeOptionComparisonValue($state);
        }

        $values = [];

        foreach ($state as $item) {
            $value = $this->normalizeOptionComparisonValue($item);

            if ($value !== null) {
                $values[] = $value;
            }
        }

        return $values;
    }

    protected function normalizeOptionComparisonValue(mixed $value): string|int|null
    {
        return match (true) {
            $value instanceof BackedEnum => $value->value,
            is_bool($value) => $value ? 1 : 0,
            is_int($value), is_string($value) => $value,
            default => null,
        };
    }

    /**
     * @param class-string<UnitEnum> $enum
     *
     * @return array<string|int, string|Markup>
     */
    private static function getOptionsFromEnum(string $enum): array
    {
        $options = [];

        foreach ($enum::cases() as $case) {
            $options[$case instanceof BackedEnum ? $case->value : $case->name] = $case instanceof HasLabel
                ? ($case->getLabel() ?? $case->name)
                : $case->name;
        }

        return $options;
    }
}
