<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\Concerns;

use Closure;

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
     * @return array<string|int, string>
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
            fn (string $label, string|int $value): bool => ! $this->isOptionDisabled($value, $label),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    public function isOptionDisabled(string|int $value, string $label): bool
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
