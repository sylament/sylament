<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\Concerns;

use Closure;
use Sylament\Forms\Components\Field;
use Sylament\Forms\State\RepeaterSiblingContext;
use Sylament\Schemas\State\Set;

use function Sylament\Support\blank;
use function Sylament\Support\except;
use function Sylament\Support\wrap;

trait CanFixIndistinctState
{
    /**
     * The repair half of `distinct()`: picking a value in one repeater item takes
     * it away from every sibling item that held it, so two items never claim the
     * same value. `distinct()` is the server-side refusal and `live(condition:)`
     * is what makes each pick a round trip; this hook is what edits the siblings.
     *
     * The three arms are the picked value's PHP type, read raw so that both sides
     * of the comparison speak the item bag's vocabulary. An array subtracts the
     * picked values from each sibling's own selection and reindexes it; `true`
     * becomes `false` in a sibling, because a checkbox that is not ticked is
     * still a value; anything else becomes `null`.
     */
    public function fixIndistinctState(bool|Closure $condition = true): static
    {
        $this->distinct($condition);
        $this->live(condition: $condition);

        $this->afterStateUpdated(static function (Field $component, mixed $rawState, Set $set) use ($condition): void {
            if (! $component->evaluate($condition)) {
                return;
            }

            if (blank($rawState)) {
                return;
            }

            $context = $component->resolveRepeaterSiblingContext();

            if (!$context instanceof RepeaterSiblingContext) {
                return;
            }

            $accessor = $component->getPropertyAccessor();

            /** @var array<int|string, mixed> $siblingItems */
            $siblingItems = except(wrap($context->repeater->getRawState()), [$context->itemKey]);

            foreach ($siblingItems as $siblingKey => $siblingItem) {
                if (! is_array($siblingItem)) {
                    continue;
                }

                /** @var array<string, mixed> $siblingItem */
                $siblingPath = "{$context->itemsPrefix}{$siblingKey}.{$context->fieldPath}";
                $siblingValue = $accessor->getFromArray($siblingItem, $context->fieldPath);

                if (is_array($rawState)) {
                    if (! is_array($siblingValue)) {
                        continue;
                    }

                    $siblingOptionValues = array_filter($siblingValue, is_scalar(...));
                    $pickedOptionValues = array_filter($rawState, is_scalar(...));

                    if (array_intersect($siblingOptionValues, $pickedOptionValues) === []) {
                        continue;
                    }

                    $set($siblingPath, array_values(array_diff($siblingOptionValues, $pickedOptionValues)), isAbsolute: true);

                    continue;
                }

                if ($siblingValue === false || blank($siblingValue) || $siblingValue !== $rawState) {
                    continue;
                }

                $set($siblingPath, $siblingValue === true ? false : null, isAbsolute: true);
            }
        });

        return $this;
    }
}
