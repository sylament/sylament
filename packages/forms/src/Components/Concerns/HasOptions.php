<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\Concerns;

use BackedEnum;
use Closure;
use Sylament\Support\Contracts\HasLabel;
use Symfony\Contracts\Translation\TranslatableInterface;
use Twig\Markup;
use UnitEnum;

trait HasOptions
{
    /**
     * @var array<string|int, string|Markup|TranslatableInterface|array<string|int, string|Markup|TranslatableInterface>>|class-string<UnitEnum>|Closure|null
     */
    protected array|string|Closure|null $options = null;

    /**
     * @param array<string|int, string|Markup|TranslatableInterface|array<string|int, string|Markup|TranslatableInterface>>|class-string<UnitEnum>|Closure|null $options
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
            return $this->translateOptions(self::getOptionsFromEnum($options));
        }

        return is_array($options) ? $this->translateOptions($options) : [];
    }

    /**
     * Each label of the map as the text it renders: a message translated, a
     * string or a `Markup` as given, one group level deep. A group's label is the
     * array key, which cannot be a message, so it stays as given.
     *
     * @param array<string|int, string|Markup|TranslatableInterface|array<string|int, string|Markup|TranslatableInterface>> $options
     *
     * @return array<string|int, string|Markup|array<string|int, string|Markup>>
     */
    private function translateOptions(array $options): array
    {
        $translated = [];

        foreach ($options as $value => $label) {
            if (! is_array($label)) {
                $translated[$value] = $this->translateOptionLabel($label);

                continue;
            }

            $group = [];

            foreach ($label as $groupValue => $groupLabel) {
                $group[$groupValue] = $this->translateOptionLabel($groupLabel);
            }

            $translated[$value] = $group;
        }

        return $translated;
    }

    /**
     * A label outside its PHPDoc type, which nothing checks a closure's map
     * against, returns as given rather than throwing as `translateText()` would.
     *
     * @param string|Markup|TranslatableInterface $label
     *
     * @return string|Markup
     */
    private function translateOptionLabel(mixed $label): mixed
    {
        return $label instanceof TranslatableInterface ? $label->trans($this->getTranslator()) : $label;
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

    /**
     * The comparison vocabulary one option value speaks: an option's key as the
     * template renders it, which an enum instance or a raw boolean does not.
     * Public because a state-reading closure compares a sibling item's raw value
     * against an option key through it, from outside the field's own scope.
     */
    public function normalizeOptionComparisonValue(mixed $value): string|int|null
    {
        return match (true) {
            $value instanceof BackedEnum => $value->value,
            is_bool($value) => $value ? 1 : 0,
            is_int($value), is_string($value) => $value,
            default => null,
        };
    }

    /**
     * Each case's label as the enum gives it, for `getOptions()` to translate: a
     * `HasLabel` case's label, else the case itself when it is a message, else
     * its name.
     *
     * @param class-string<UnitEnum> $enum
     *
     * @return array<string|int, string|Markup|TranslatableInterface>
     */
    private static function getOptionsFromEnum(string $enum): array
    {
        $options = [];

        foreach ($enum::cases() as $case) {
            $options[$case instanceof BackedEnum ? $case->value : $case->name] = match (true) {
                $case instanceof HasLabel => $case->getLabel() ?? $case->name,
                $case instanceof TranslatableInterface => $case,
                default => $case->name,
            };
        }

        return $options;
    }
}
