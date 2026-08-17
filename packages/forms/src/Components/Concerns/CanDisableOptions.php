<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\Concerns;

use Closure;
use Twig\Markup;

use function Sylament\Support\wrap;

trait CanDisableOptions
{
    /**
     * @var array<bool|Closure>
     */
    protected array $isOptionDisabled = [];

    public function disableOptionWhen(bool|Closure|null $callback, bool $merge = false): static
    {
        if ($merge) {
            if ($callback !== null) {
                $this->isOptionDisabled[] = $callback;
            }

            return $this;
        }

        $this->isOptionDisabled = wrap($callback);

        return $this;
    }

    /**
     * @return array<string|int, string|Markup>
     */
    public function getEnabledOptions(): array
    {
        $flattened = [];

        foreach ($this->getOptions() as $value => $label) {
            if (is_array($label)) {
                foreach ($label as $groupedValue => $groupedLabel) {
                    $flattened[$groupedValue] = $groupedLabel;
                }

                continue;
            }

            $flattened[$value] = $label;
        }

        if (! $this->hasDisabledOptions()) {
            return $flattened;
        }

        return array_filter(
            $flattened,
            fn (string|Markup $label, string|int $value): bool => ! $this->isOptionDisabled($value, $label),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    /**
     * Every enabled option value, in both forms a request can legitimately leave in
     * the host's state: the form the field's state casts store — what `fill()` and
     * `state()` write — and the string the browser submits, which is always the
     * option's array key as the template renders it into the `value` attribute.
     * Validation runs against the raw state, and those two forms part company
     * whenever a cast leaves the array-key vocabulary. A multi-value cast casts
     * one option key to a whole selection, so the list it returns is spread:
     * `Choice(multiple: true)` compares element by element, and an option value
     * wrapped in an array would match nothing.
     *
     * @return list<mixed>
     */
    protected function getEnabledOptionValues(): array
    {
        $stateCasts = array_reverse($this->getStateCasts());

        $values = [];

        foreach (array_keys($this->getEnabledOptions()) as $value) {
            $castValue = $value;

            foreach ($stateCasts as $stateCast) {
                $castValue = $stateCast->set($castValue);
            }

            $castValues = is_array($castValue) ? array_values($castValue) : [$castValue];

            $submittedValue = (string) $value;

            if (! in_array($submittedValue, $castValues, true)) {
                $castValues[] = $submittedValue;
            }

            $values = [...$values, ...$castValues];
        }

        return $values;
    }

    public function isOptionDisabled(string|int $value, string|Markup $label): bool
    {
        foreach ($this->isOptionDisabled as $isOptionDisabled) {
            if ($this->evaluate($isOptionDisabled, [
                'label' => $label,
                'value' => $value,
            ])) {
                return true;
            }
        }

        return false;
    }

    public function hasDisabledOptions(): bool
    {
        foreach ($this->isOptionDisabled as $isOptionDisabled) {
            if ($isOptionDisabled !== false) {
                return true;
            }
        }

        return false;
    }

    public function hasDynamicDisabledOptions(): bool
    {
        foreach ($this->isOptionDisabled as $isOptionDisabled) {
            if ($isOptionDisabled instanceof Closure) {
                return true;
            }
        }

        return false;
    }
}
