<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\Concerns;

use function Sylament\Support\filled;
use function Sylament\Support\wrap;

trait CanDisableOptionsWhenSelectedInSiblingRepeaterItems
{
    /**
     * The read half of `distinct()`: an option another repeater item already holds
     * is greyed out here, so the refusal never has to fire. The whole item bag is
     * plucked rather than the siblings alone, and this item's own selection is
     * subtracted from what it finds — a value this item shares with a sibling
     * stays clickable, so releasing it is still possible.
     */
    public function disableOptionsWhenSelectedInSiblingRepeaterItems(): static
    {
        $this->distinct();
        $this->live();

        $this->disableOptionWhen(static function (self $component, string|int $value, mixed $state): bool {
            $context = $component->resolveRepeaterSiblingContext();

            if ($context === null) {
                return false;
            }

            $accessor = $component->getPropertyAccessor();
            $ownValues = [];

            foreach (wrap($state) as $ownValue) {
                $normalized = $component->normalizeOptionComparisonValue($ownValue);

                if (filled($normalized)) {
                    $ownValues[] = (string) $normalized;
                }
            }

            /** @var array<int|string, mixed> $items */
            $items = wrap($context->repeater->getRawState());

            foreach ($items as $item) {
                if (! is_array($item)) {
                    continue;
                }

                /** @var array<string, mixed> $itemState */
                $itemState = $item;

                foreach (wrap($accessor->getFromArray($itemState, $context->fieldPath)) as $itemValue) {
                    $normalized = $component->normalizeOptionComparisonValue($itemValue);

                    if (! filled($normalized) || in_array((string) $normalized, $ownValues, strict: true)) {
                        continue;
                    }

                    if ((string) $normalized === (string) $value) {
                        return true;
                    }
                }
            }

            return false;
        });

        return $this;
    }
}
